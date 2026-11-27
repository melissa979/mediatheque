<?php
require_once __DIR__ . '/../app/auth.php';
exigerConnexion();

$db = Database::get();
$enCours = $db->query('SELECT COUNT(*) FROM emprunt WHERE date_retour_reelle IS NULL')->fetchColumn();
$retards = $db->query('SELECT COUNT(*) FROM emprunt WHERE date_retour_reelle IS NULL AND date_retour_prevue < CURDATE()')->fetchColumn();
$adherents = $db->query('SELECT COUNT(*) FROM adherent')->fetchColumn();
$documents = $db->query('SELECT COUNT(*) FROM document')->fetchColumn();

$titre = 'Tableau de bord';
require __DIR__ . '/../templates/header.php';
?>
<h1>Tableau de bord</h1>
<div class="tuiles">
  <div class="tuile"><span><?= (int) $enCours ?></span>emprunts en cours</div>
  <div class="tuile alerte"><span><?= (int) $retards ?></span>retards</div>
  <div class="tuile"><span><?= (int) $adherents ?></span>adhérents</div>
  <div class="tuile"><span><?= (int) $documents ?></span>documents</div>
</div>
<p><a class="bouton" href="emprunt.php">Nouvel emprunt</a> <a class="bouton" href="retour.php">Enregistrer un retour</a></p>
<?php require __DIR__ . '/../templates/footer.php'; ?>
