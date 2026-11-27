<?php
$u = utilisateurConnecte();
$page = basename($_SERVER['PHP_SELF']);
/** Ajoute la classe "actif" au lien de l'onglet courant (fiches et formulaires compris). */
function actif(string $prefixe, string $page): string
{
    return str_starts_with($page, $prefixe) ? ' class="actif"' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titre ?? 'Médiathèque') ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php if ($u): ?>
<nav>
  <strong>Médiathèque</strong>
  <a href="index.php"<?= actif('index', $page) ?>>Tableau de bord</a>
  <a href="adherents.php"<?= actif('adherent', $page) ?>>Adhérents</a>
  <a href="documents.php"<?= actif('document', $page) ?>>Documents</a>
  <a href="emprunt.php"<?= actif('emprunt', $page) ?>>Emprunt</a>
  <a href="retour.php"<?= actif('retour', $page) ?>>Retour</a>
  <a href="retards.php"<?= actif('retards', $page) ?>>Retards</a>
  <?php if ($u['role'] === 'admin'): ?><a href="parametres.php"<?= actif('parametres', $page) ?>>Paramètres</a><?php endif; ?>
  <span class="droite"><?= e($u['nom']) ?> (<?= e($u['role']) ?>) · <a href="logout.php">Déconnexion</a></span>
</nav>
<?php endif; ?>
<main>
<?php if (!empty($_SESSION['flash'])): ?>
  <p class="message <?= e($_SESSION['flash']['type']) ?>"><?= e($_SESSION['flash']['texte']) ?></p>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>