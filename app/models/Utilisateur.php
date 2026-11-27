<?php
/**
 * Classe Utilisateur (diagramme de classes, étape 6.2).
 */
class Utilisateur
{
    public static function trouverParLogin(string $login): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM utilisateur WHERE login = ?');
        $req->execute([$login]);
        return $req->fetch() ?: null;
    }

    /** Retourne l'utilisateur si identifiant et mot de passe sont corrects et le compte actif (F1, F23). */
    public static function authentifier(string $login, string $motDePasse): ?array
    {
        $u = self::trouverParLogin($login);
        if ($u && $u['actif'] && password_verify($motDePasse, $u['mot_de_passe_hash'])) {
            return $u;
        }
        return null;
    }

    public static function creer(string $login, string $motDePasse, string $nom, string $role): int
    {
        $req = Database::get()->prepare(
            'INSERT INTO utilisateur (login, mot_de_passe_hash, nom, role) VALUES (?, ?, ?, ?)'
        );
        $req->execute([$login, password_hash($motDePasse, PASSWORD_DEFAULT), $nom, $role]);
        return (int) Database::get()->lastInsertId();
    }

    public static function estAdministrateur(array $u): bool
    {
        return $u['role'] === 'admin';
    }
}
