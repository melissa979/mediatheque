<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Document.php';
exigerConnexion();

$recherche = $_GET['q'] ?? '';
$type = $_GET['type'] ?? '';
$documents = Document::rechercher($recherche, $type);

$titre = 'Documents';
require __DIR__ . '/../templates/header.php';
?>
<h1>Documents</h1>
<form method="get" class="ligne">
  <input name="q" value="<?= e($recherche) ?>" placeholder="Titre, auteur ou genre">
  <select name="type" style="width:auto">
    <option value="">Tous les types</option>
    <?php foreach (Document::TYPES as $t): ?>
      <option value="<?= e($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= e($t) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Rechercher</button>
  <a class="bouton" href="document_form.php">+ Ajouter un document</a>
</form>
<table>
  <tr><th>Titre</th><th>Type</th><th>Auteur</th><th>Cote</th><th>Disponibles</th><th></th></tr>
  <?php foreach ($documents as $d): ?>
  <tr>
    <td><?= e($d['titre']) ?></td>
    <td><?= e($d['type']) ?></td>
    <td><?= e($d['auteur']) ?></td>
    <td><?= e($d['cote']) ?></td>
    <td>
      <?php $dispo = (int) $d['nb_disponibles']; ?>
      <span class="etiquette <?= $dispo > 0 ? 'vert' : 'rouge' ?>"><?= $dispo ?> / <?= (int) $d['nb_exemplaires'] ?></span>
    </td>
    <td><a href="document.php?id=<?= (int) $d['id'] ?>">Voir la fiche</a></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$documents): ?><tr><td colspan="6">Aucun document trouvé.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../templates/footer.php'; ?>