<?php
/** Enregistrer un emprunt : suit le diagramme de séquence système (étape 6.3). */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Emprunt.php';
exigerConnexion();

$carte = trim($_GET['carte'] ?? '');
$adherent = null;

// Étapes 1 à 3 : saisie de la carte -> fiche adhérent ou erreur
if ($carte !== '') {
    $adherent = Adherent::trouverParCarte($carte);
    if (!$adherent) {
        flash('erreur', 'Adhérent introuvable : vérifiez le numéro de carte.');
        header('Location: emprunt.php');
        exit;
    }
}

// Étapes 4 à 7 : saisie d'un exemplaire -> refus ou confirmation (répétable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adherent) {
    verifierCsrf();
    $resultat = Emprunt::enregistrer((int) $adherent['id'], $_POST['code_barre'] ?? '', (int) utilisateurConnecte()['id']);
    if ($resultat['ok']) {
        flash('succes', '« ' . $resultat['titre'] . ' » prêté. Retour prévu le '
            . date('d/m/Y', strtotime($resultat['date_retour'])) . '.');
    } else {
        flash('erreur', 'Emprunt refusé : ' . $resultat['erreur']);
    }
    header('Location: emprunt.php?carte=' . urlencode($carte));
    exit;
}

$emprunts = $adherent ? Adherent::empruntsEnCours((int) $adherent['id']) : [];
$titre = 'Emprunt';
require __DIR__ . '/../templates/header.php';
?>
<h1>Enregistrer un emprunt</h1>

<?php if (!$adherent): ?>
  <form method="get" class="formulaire">
    <label>Numéro de carte de l'adhérent
      <input name="carte" placeholder="ex. C0001" required autofocus>
    </label>
    <button type="submit">Valider</button>
  </form>

<?php else: ?>
  <div class="tuiles">
    <div class="tuile">
      <span><?= e($adherent['prenom'] . ' ' . $adherent['nom']) ?></span>
      carte <?= e($adherent['numero_carte']) ?>
    </div>
    <div class="tuile">
      <span><?= count($emprunts) ?> / <?= Parametre::getEntier('quota_total') ?></span>
      emprunts en cours
    </div>
    <div class="tuile <?= Adherent::abonnementValide($adherent) ? '' : 'alerte' ?>">
      <span><?= e(date('d/m/Y', strtotime($adherent['date_fin_abonnement']))) ?></span>
      fin d'abonnement
    </div>
  </div>

  <form method="post" class="ligne">
    <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
    <input name="code_barre" placeholder="Scanner ou saisir le code-barres de l'exemplaire" required autofocus>
    <button type="submit">Prêter</button>
    <a class="bouton" href="emprunt.php">Terminer / autre adhérent</a>
  </form>

  <h2>Emprunts en cours</h2>
  <table>
    <tr><th>Titre</th><th>Type</th><th>Exemplaire</th><th>Emprunté le</th><th>Retour prévu</th><th>Statut</th></tr>
    <?php foreach ($emprunts as $em): ?>
    <tr>
      <td><?= e($em['titre']) ?></td>
      <td><?= e($em['type']) ?></td>
      <td><?= e($em['code_barre']) ?></td>
      <td><?= e(date('d/m/Y', strtotime($em['date_emprunt']))) ?></td>
      <td><?= e(date('d/m/Y', strtotime($em['date_retour_prevue']))) ?></td>
      <td>
        <?php if ($em['jours_retard'] > 0): ?>
          <span class="etiquette rouge">Retard <?= (int) $em['jours_retard'] ?> j</span>
        <?php else: ?>
          <span class="etiquette vert">En cours</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$emprunts): ?><tr><td colspan="6">Aucun emprunt en cours.</td></tr><?php endif; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>