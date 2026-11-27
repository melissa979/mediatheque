<?php
/** Retour (F18) et prolongation (F19) d'un emprunt. */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Emprunt.php';
exigerConnexion();

$code = trim($_GET['code'] ?? '');
$emprunt = null;

if ($code !== '') {
    $emprunt = Emprunt::trouverEnCoursParCode($code);
    if (!$emprunt) {
        $x = Exemplaire::trouverParCode($code);
        flash('erreur', $x ? 'Cet exemplaire n\'est pas emprunté.' : 'Exemplaire introuvable : vérifiez le code-barres.');
        header('Location: retour.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $emprunt) {
    verifierCsrf();
    if (($_POST['action'] ?? '') === 'retour') {
        Emprunt::enregistrerRetour((int) $emprunt['id'], (int) $emprunt['exemplaire_id']);
        $msg = '« ' . $emprunt['titre'] . ' » rendu par ' . $emprunt['prenom'] . ' ' . $emprunt['nom'] . '.';
        if ($emprunt['jours_retard'] > 0) {
            $msg .= ' Retour avec ' . $emprunt['jours_retard'] . ' jour(s) de retard.';
        }
        flash('succes', $msg);
        header('Location: retour.php');   // prêt pour le document suivant
        exit;
    }
    if (($_POST['action'] ?? '') === 'prolonger') {
        $refus = Emprunt::prolonger($emprunt);
        if ($refus) {
            flash('erreur', 'Prolongation refusée : ' . $refus);
        } else {
            $maj = Emprunt::trouverEnCoursParCode($code);
            flash('succes', 'Emprunt prolongé jusqu\'au ' . date('d/m/Y', strtotime($maj['date_retour_prevue'])) . '.');
        }
        header('Location: retour.php?code=' . urlencode($code));
        exit;
    }
}

$titre = 'Retour';
require __DIR__ . '/../templates/header.php';
?>
<h1>Retour et prolongation</h1>

<form method="get" class="ligne">
  <input name="code" value="<?= e($code) ?>" placeholder="Scanner ou saisir le code-barres de l'exemplaire" required autofocus>
  <button type="submit">Rechercher</button>
</form>

<?php if ($emprunt): ?>
  <table class="fiche">
    <tr><th>Document</th><td><?= e($emprunt['titre']) ?> (<?= e($emprunt['type']) ?>)</td></tr>
    <tr><th>Exemplaire</th><td><?= e($emprunt['code_barre']) ?></td></tr>
    <tr><th>Adhérent</th><td>
      <a href="adherent.php?id=<?= (int) $emprunt['adherent_id'] ?>"><?= e($emprunt['prenom'] . ' ' . $emprunt['nom']) ?></a>
      (<?= e($emprunt['numero_carte']) ?>)
    </td></tr>
    <tr><th>Emprunté le</th><td><?= e(date('d/m/Y', strtotime($emprunt['date_emprunt']))) ?></td></tr>
    <tr><th>Retour prévu</th><td><?= e(date('d/m/Y', strtotime($emprunt['date_retour_prevue']))) ?></td></tr>
    <tr><th>Prolongations</th><td><?= (int) $emprunt['nb_prolongations'] ?> / <?= Parametre::getEntier('nb_prolongations_max') ?></td></tr>
    <tr><th>Statut</th><td>
      <?php if ($emprunt['jours_retard'] > 0): ?>
        <span class="etiquette rouge">Retard <?= (int) $emprunt['jours_retard'] ?> j</span>
      <?php else: ?>
        <span class="etiquette vert">Dans les délais</span>
      <?php endif; ?>
    </td></tr>
  </table>

  <div class="actions">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
      <button name="action" value="retour">Enregistrer le retour</button>
    </form>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
      <button name="action" value="prolonger">Prolonger</button>
    </form>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>