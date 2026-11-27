<?php
require_once __DIR__ . '/Parametre.php';
require_once __DIR__ . '/Adherent.php';
require_once __DIR__ . '/Exemplaire.php';

/**
 * Classe Emprunt (diagramme de classes) : exigences F15 à F17, règles R1 à R4 et R6.
 */
class Emprunt
{
    /**
     * Vérifie les règles de prêt (cas inclus « Vérifier les règles de prêt »).
     * Fonction « pure » : elle ne lit pas la base, ce qui permet les tests unitaires.
     * Retourne null si l'emprunt est autorisé, sinon la raison du refus.
     */
    public static function verifierRegles(
        array $adherent,
        array $exemplaire,
        int $nbEnCours,
        int $nbDvdEnCours,
        bool $aUnRetard,
        int $quotaTotal,
        int $quotaDvd,
        string $aujourdhui
    ): ?string {
        if ($adherent['carte_bloquee']) {
            return 'Carte bloquée : ' . $adherent['motif_blocage'];                     // R1
        }
        if ($adherent['date_fin_abonnement'] < $aujourdhui) {
            return 'Abonnement expiré le ' . date('d/m/Y', strtotime($adherent['date_fin_abonnement'])) . '.'; // R1
        }
        if ($aUnRetard) {
            return 'L\'adhérent a un document en retard : emprunts suspendus.';          // R6
        }
        if ($nbEnCours >= $quotaTotal) {
            return "Quota atteint : $quotaTotal emprunts en cours maximum.";              // R2
        }
        if ($exemplaire['type'] === 'DVD' && $nbDvdEnCours >= $quotaDvd) {
            return "Quota de DVD atteint : $quotaDvd DVD en cours maximum.";              // R2
        }
        if ($exemplaire['etat'] !== 'disponible') {
            return 'Exemplaire indisponible (état : ' . Exemplaire::libelle($exemplaire['etat']) . ').'; // R4
        }
        return null;
    }

    /** Calcule la date de retour prévue (F17, règle R3). */
    public static function calculerDateRetour(string $dateEmprunt, int $dureeJours): string
    {
        return date('Y-m-d', strtotime("$dateEmprunt +$dureeJours days"));
    }

    /**
     * Enregistre un emprunt après vérification (F15).
     * Retourne ['ok' => true, 'date_retour' => ...] ou ['ok' => false, 'erreur' => ...].
     */
    public static function enregistrer(int $adherentId, string $codeBarre, int $utilisateurId): array
    {
        $adherent = Adherent::trouver($adherentId);
        if (!$adherent) {
            return ['ok' => false, 'erreur' => 'Adhérent introuvable.'];
        }
        $exemplaire = Exemplaire::trouverParCode($codeBarre);
        if (!$exemplaire) {
            return ['ok' => false, 'erreur' => 'Exemplaire introuvable : vérifiez le code-barres.'];
        }

        $aujourdhui = date('Y-m-d');
        $refus = self::verifierRegles(
            $adherent,
            $exemplaire,
            Adherent::nbEmpruntsEnCours($adherentId),
            Adherent::nbEmpruntsEnCours($adherentId, 'DVD'),
            Adherent::aUnRetard($adherentId),
            Parametre::getEntier('quota_total'),
            Parametre::getEntier('quota_dvd'),
            $aujourdhui
        );
        if ($refus) {
            return ['ok' => false, 'erreur' => $refus];
        }

        $dateRetour = self::calculerDateRetour($aujourdhui, Parametre::getEntier('duree_pret_jours'));
        $db = Database::get();

        // Transaction : l'emprunt et le changement d'état sont enregistrés ensemble, ou pas du tout
        $db->beginTransaction();
        try {
            // La condition etat = 'disponible' empêche deux agents de prêter le même exemplaire en même temps
            $maj = $db->prepare("UPDATE exemplaire SET etat = 'emprunte' WHERE id = ? AND etat = 'disponible'");
            $maj->execute([$exemplaire['id']]);
            if ($maj->rowCount() === 0) {
                $db->rollBack();
                return ['ok' => false, 'erreur' => 'Exemplaire déjà emprunté.'];
            }
            $db->prepare(
                'INSERT INTO emprunt (adherent_id, exemplaire_id, utilisateur_id, date_emprunt, date_retour_prevue)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$adherentId, $exemplaire['id'], $utilisateurId, $aujourdhui, $dateRetour]);
            $db->commit();
        } catch (Throwable $ex) {
            $db->rollBack();
            throw $ex;
        }

        return ['ok' => true, 'titre' => $exemplaire['titre'], 'date_retour' => $dateRetour];
   
   
        }
        /** Emprunt en cours d'un exemplaire, avec l'adhérent et le document (pour le retour et la prolongation). */
    public static function trouverEnCoursParCode(string $codeBarre): ?array
    {
        $req = Database::get()->prepare(
            'SELECT e.*, x.code_barre, d.titre, d.type,
                    a.id AS adherent_id, a.nom, a.prenom, a.numero_carte,
                    GREATEST(DATEDIFF(CURDATE(), e.date_retour_prevue), 0) AS jours_retard
             FROM emprunt e
             JOIN exemplaire x ON x.id = e.exemplaire_id
             JOIN document d ON d.id = x.document_id
             JOIN adherent a ON a.id = e.adherent_id
             WHERE x.code_barre = ? AND e.date_retour_reelle IS NULL'
        );
        $req->execute([strtoupper(trim($codeBarre))]);
        return $req->fetch() ?: null;
    }

    /** Enregistre le retour (F18) : date réelle + exemplaire de nouveau disponible, dans une transaction. */
    public static function enregistrerRetour(int $empruntId, int $exemplaireId): void
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE emprunt SET date_retour_reelle = CURDATE() WHERE id = ? AND date_retour_reelle IS NULL')
               ->execute([$empruntId]);
            $db->prepare("UPDATE exemplaire SET etat = 'disponible' WHERE id = ?")
               ->execute([$exemplaireId]);
            $db->commit();
        } catch (Throwable $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * Vérifie la règle R5 (et R6) pour une prolongation. Fonction pure, testable.
     * Retourne null si la prolongation est autorisée, sinon la raison du refus.
     */
    public static function verifierProlongation(array $emprunt, bool $adherentEnRetard, int $nbMax, string $aujourdhui): ?string
    {
        if ($emprunt['date_retour_prevue'] < $aujourdhui) {
            return 'La date de retour est dépassée : le document doit être rendu.';
        }
        if ($emprunt['nb_prolongations'] >= $nbMax) {
            return "Nombre maximal de prolongations atteint ($nbMax).";
        }
        if ($adherentEnRetard) {
            return 'L\'adhérent a un autre document en retard.';
        }
        // La condition « document non réservé » sera ajoutée avec les réservations (évolution F25)
        return null;
    }

    /** Prolonge un emprunt (F19). Retourne null si OK, sinon la raison du refus. */
    public static function prolonger(array $emprunt): ?string
    {
        $refus = self::verifierProlongation(
            $emprunt,
            Adherent::aUnRetard((int) $emprunt['adherent_id']),
            Parametre::getEntier('nb_prolongations_max'),
            date('Y-m-d')
        );
        if ($refus) {
            return $refus;
        }
        $nouvelleDate = self::calculerDateRetour($emprunt['date_retour_prevue'], Parametre::getEntier('duree_prolongation_jours'));
        Database::get()->prepare(
            'UPDATE emprunt SET date_retour_prevue = ?, nb_prolongations = nb_prolongations + 1 WHERE id = ?'
        )->execute([$nouvelleDate, $emprunt['id']]);
        return null;
    }
}