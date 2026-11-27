<?php
/**
 * Classe Document (diagramme de classes) : exigences F10 à F12.
 */
class Document
{
    public const TYPES = ['livre', 'revue', 'CD', 'DVD'];

    public static function valider(array $d): array
    {
        $erreurs = [];
        if (trim($d['titre'] ?? '') === '') {
            $erreurs[] = 'Titre obligatoire.';
        }
        if (!in_array($d['type'] ?? '', self::TYPES, true)) {
            $erreurs[] = 'Type de document invalide.';
        }
        if (($d['annee'] ?? '') !== '' && (!ctype_digit((string) $d['annee']) || $d['annee'] > date('Y'))) {
            $erreurs[] = 'Année invalide.';
        }
        return $erreurs;
    }

    public static function creer(array $d): int
    {
        $req = Database::get()->prepare(
            'INSERT INTO document (titre, type, auteur, editeur, annee, genre, cote) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $req->execute(self::valeurs($d));
        return (int) Database::get()->lastInsertId();
    }

    public static function modifier(int $id, array $d): void
    {
        $req = Database::get()->prepare(
            'UPDATE document SET titre = ?, type = ?, auteur = ?, editeur = ?, annee = ?, genre = ?, cote = ? WHERE id = ?'
        );
        $req->execute([...self::valeurs($d), $id]);
    }

    private static function valeurs(array $d): array
    {
        return [trim($d['titre']), $d['type'], trim($d['auteur'] ?? ''), trim($d['editeur'] ?? ''),
            ($d['annee'] ?? '') === '' ? null : (int) $d['annee'], trim($d['genre'] ?? ''), trim($d['cote'] ?? '')];
    }

    public static function trouver(int $id): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM document WHERE id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    /** Recherche par titre, auteur ou genre, filtre par type (F11), avec le nombre d'exemplaires disponibles. */
    public static function rechercher(string $texte, string $type = ''): array
    {
        $sql = "SELECT d.*,
                       COUNT(x.id) AS nb_exemplaires,
                       SUM(x.etat = 'disponible') AS nb_disponibles
                FROM document d
                LEFT JOIN exemplaire x ON x.document_id = d.id
                WHERE (d.titre LIKE ? OR d.auteur LIKE ? OR d.genre LIKE ?)";
        $motif = '%' . trim($texte) . '%';
        $params = [$motif, $motif, $motif];
        if ($type !== '') {
            $sql .= ' AND d.type = ?';
            $params[] = $type;
        }
        $sql .= ' GROUP BY d.id ORDER BY d.titre';
        $req = Database::get()->prepare($sql);
        $req->execute($params);
        return $req->fetchAll();
    }
}