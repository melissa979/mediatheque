<?php
/** Fiche document + gestion de ses exemplaires (F13, F14). */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Document.php';
require_once __DIR__ . '/../app/models/Exemplaire.php';
exigerConnexion();

$id = (int) ($_GET['id'] ?? 0);
$doc = Document::trouver($id);
if (!$doc) {
    exit('Document introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    if (($_POST['action'] ?? '') === 'ajouter') {
        $erreur = Exemplaire::ajouter($id, $_POST['code_barre'] ?? '');
        if ($erreur) {
            flash('erreur', $erreur);
        } else {
            flash('succes', 'Exemplaire ajouté.');
        }
    } elseif (($_POST['action'] ?? '') === 'etat') {
        $x = Exemplaire::trouver((int) $_POST['exemplaire_id']);
        $etat = $_POST['etat'] ?? '';
        if (!$x || (int) $x['document_id'] !== $id) {
            flash('erreur', 'Exemplaire introuvable.');
        } elseif ($x['etat'] === 'emprunte') {
            flash('erreur', 'Exemplaire emprunté : enregistrez d\'abord son retour.');
        } elseif (!in_array($etat, Exemplaire::ETATS_MANUELS, true)) {
            flash('erreur', 'État non autorisé.');
        } else {
            Exemplaire::changerEtat((int) $x['id'], $etat);
            flash('succes', 'État mis à jour.');
        }
    }
    header('Location: document.php?id=' . $id);
    exit;
}

$exemplaires = Exemplaire::parDocument($id);
$titre = 'Fiche document';
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e($doc['titre']) ?> <small>(<?= e($doc['type']) ?>)</small></h1>
<table class="fiche">
  <tr><th>Auteur</th><td><?= e($doc['auteur']) ?></td></tr>
  <tr><th>Éditeur</th><td><?= e($doc['editeur']) ?></td></tr>
  <tr><th>Année</th><td><?= e((string) $doc['annee']) ?></td></tr>
  <tr><th>Genre</th><td><?= e($doc['genre']) ?></td></tr>
  <tr><th>Cote</th><td><?= e($doc['cote']) ?></td></tr>
</table>
<div class="actions"><a class="bouton" href="document_form.php?id=<?= $id ?>">Modifier</a></div>

<h2>Exemplaires (<?= count($exemplaires) ?>)</h2>
<table>
  <tr><th>Code-barres</th><th>État</th><th>Changer l'état</th></tr>
  <?php foreach ($exemplaires as $x): ?>
  <tr>
    <td><?= e($x['code_barre']) ?></td>
    <td><span class="etiquette <?= $x['etat'] === 'disponible' ? 'vert' : ($x['etat'] === 'emprunte' ? 'orange' : 'rouge') ?>"><?= e(Exemplaire::libelle($x['etat'])) ?></span></td>
    <td>
      <?php if ($x['etat'] !== 'emprunte'): ?>
      <form method="post" class="ligne" style="margin:0">
        <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
        <input type="hidden" name="action" value="etat">
        <input type="hidden" name="exemplaire_id" value="<?= (int) $x['id'] ?>">
        <select name="etat" style="width:auto">
          <?php foreach (Exemplaire::ETATS_MANUELS as $et): ?>
            <option value="<?= e($et) ?>" <?= $et === $x['etat'] ? 'selected' : '' ?>><?= e(Exemplaire::libelle($et)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">OK</button>
      </form>
      <?php else: ?>-<?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$exemplaires): ?><tr><td colspan="3">Aucun exemplaire.</td></tr><?php endif; ?>
</table>

<h2>Ajouter un exemplaire</h2>
<form method="post" class="ligne">
  <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
  <input type="hidden" name="action" value="ajouter">
  <input name="code_barre" placeholder="Code-barres (ex. EX0009)" required>
  <button type="submit">Ajouter</button>
</form>
<p><a href="documents.php">← Retour à la liste</a></p>
<?php require __DIR__ . '/../templates/footer.php'; ?>