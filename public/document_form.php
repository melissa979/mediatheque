<?php
/** Ajout (F10) et modification (F12) d'un document. */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Document.php';
exigerConnexion();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$document = $id ? Document::trouver($id) : null;
if ($id && !$document) {
    exit('Document introuvable.');
}
$d = $document ?? ['titre' => '', 'type' => 'livre', 'auteur' => '', 'editeur' => '', 'annee' => '', 'genre' => '', 'cote' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $d = $_POST;
    $erreurs = Document::valider($d);
    if (!$erreurs) {
        if ($id) {
            Document::modifier($id, $d);
            flash('succes', 'Document modifié.');
        } else {
            $id = Document::creer($d);
            flash('succes', 'Document ajouté. Ajoutez maintenant ses exemplaires.');
        }
        header('Location: document.php?id=' . $id);
        exit;
    }
}

$titre = $document ? 'Modifier un document' : 'Ajouter un document';
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e($titre) ?></h1>
<?php foreach ($erreurs as $err): ?><p class="message erreur"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="formulaire">
  <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
  <label>Titre * <input name="titre" value="<?= e($d['titre']) ?>" required></label>
  <label>Type *
    <select name="type">
      <?php foreach (Document::TYPES as $t): ?>
        <option value="<?= e($t) ?>" <?= $t === $d['type'] ? 'selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Auteur / réalisateur / artiste <input name="auteur" value="<?= e($d['auteur']) ?>"></label>
  <label>Éditeur <input name="editeur" value="<?= e($d['editeur']) ?>"></label>
  <label>Année <input type="number" name="annee" value="<?= e((string) $d['annee']) ?>"></label>
  <label>Genre <input name="genre" value="<?= e($d['genre']) ?>"></label>
  <label>Cote <input name="cote" value="<?= e($d['cote']) ?>"></label>
  <button type="submit">Enregistrer</button>
  <a href="<?= $document ? 'document.php?id=' . (int) $id : 'documents.php' ?>">Annuler</a>
</form>
<?php require __DIR__ . '/../templates/footer.php'; ?>