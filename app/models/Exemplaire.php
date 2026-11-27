<?php
/**
 * Classe Exemplaire (diagramme de classes) : exigences F13 et F14.
 */
class Exemplaire
{
    public const ETATS = ['disponible', 'emprunte', 'reserve', 'perdu', 'abime'];
    /** États que l'agent peut choisir à la main ; « emprunté » est géré par les emprunts. */
    public const ETATS_MANUELS = ['disponible', 'perdu', 'abime'];

    public static function ajouter(int $documentId, string $codeBarre): ?string
    {
        $codeBarre = strtoupper(trim($codeBarre));
        if ($codeBarre === '') {
            return 'Code-barres obligatoire.';
        }
        if (self::trouverParCode($codeBarre)) {
            return 'Ce code-barres existe déjà.';
        }
        Database::get()->prepare('INSERT INTO exemplaire (code_barre, document_id) VALUES (?, ?)')
            ->execute([$codeBarre, $documentId]);
        return null; // pas d'erreur
    }

    public static function trouver(int $id): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM exemplaire WHERE id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    /** Exemplaire + informations de son document (utilisé par l'emprunt et le retour). */
    public static function trouverParCode(string $codeBarre): ?array
    {
        $req = Database::get()->prepare(
            'SELECT x.*, d.titre, d.type FROM exemplaire x JOIN document d ON d.id = x.document_id WHERE x.code_barre = ?'
        );
        $req->execute([strtoupper(trim($codeBarre))]);
        return $req->fetch() ?: null;
    }

    public static function parDocument(int $documentId): array
    {
        $req = Database::get()->prepare('SELECT * FROM exemplaire WHERE document_id = ? ORDER BY code_barre');
        $req->execute([$documentId]);
        return $req->fetchAll();
    }

    public static function estDisponible(array $x): bool
    {
        return $x['etat'] === 'disponible';
    }

    public static function changerEtat(int $id, string $etat): void
    {
        if (!in_array($etat, self::ETATS, true)) {
            throw new InvalidArgumentException("État inconnu : $etat");
        }
        Database::get()->prepare('UPDATE exemplaire SET etat = ? WHERE id = ?')->execute([$etat, $id]);
    }

    /** Libellé lisible pour l'affichage. */
    public static function libelle(string $etat): string
    {
        return ['disponible' => 'Disponible', 'emprunte' => 'Emprunté', 'reserve' => 'Réservé',
                'perdu' => 'Perdu', 'abime' => 'Abîmé'][$etat] ?? $etat;
    }
}