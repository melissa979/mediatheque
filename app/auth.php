<?php
/**
 * Fonctions communes : session, contrôle d'accès (F2, F3), sécurité.
 * À inclure en haut de chaque page.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/models/Utilisateur.php';

session_start();

/** Échappe une valeur avant affichage (protection XSS). */
function e(?string $valeur): string
{
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

/** Utilisateur connecté, ou null. */
function utilisateurConnecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

/** Bloque la page si personne n'est connecté ; gère l'expiration de session. */
function exigerConnexion(): void
{
    if (!utilisateurConnecte()) {
        header('Location: login.php');
        exit;
    }
    if (time() - ($_SESSION['derniere_activite'] ?? 0) > SESSION_DUREE_MAX) {
        session_unset();
        session_destroy();
        header('Location: login.php?expire=1');
        exit;
    }
    $_SESSION['derniere_activite'] = time();
}

/** Bloque la page si l'utilisateur n'est pas administrateur. */
function exigerAdmin(): void
{
    exigerConnexion();
    if (utilisateurConnecte()['role'] !== 'admin') {
        http_response_code(403);
        exit('Accès refusé : page réservée à l\'administrateur.');
    }
}

/** Jeton anti-CSRF pour les formulaires. */
function jetonCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifierCsrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Requête invalide.');
    }
}

/** Message à afficher une fois sur la page suivante. */
function flash(string $type, string $texte): void
{
    $_SESSION['flash'] = ['type' => $type, 'texte' => $texte];
}
