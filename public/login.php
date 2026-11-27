<?php
require_once __DIR__ . '/../app/auth.php';

$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = Utilisateur::authentifier(trim($_POST['login'] ?? ''), $_POST['mot_de_passe'] ?? '');
    if ($u) {
        session_regenerate_id(true);          // évite la fixation de session
        unset($u['mot_de_passe_hash']);
        $_SESSION['utilisateur'] = $u;
        $_SESSION['derniere_activite'] = time();
        header('Location: index.php');
        exit;
    }
    // Message volontairement vague (F1) : on ne dit pas lequel des deux est faux
    $erreur = 'Identifiant ou mot de passe incorrect.';
}
$titre = 'Connexion';
require __DIR__ . '/../templates/header.php';
?>
<div class="carte-connexion">
  <h1>Connexion</h1>
  <?php if (isset($_GET['expire'])): ?><p class="message info">Session expirée, reconnectez-vous.</p><?php endif; ?>
  <?php if ($erreur): ?><p class="message erreur"><?= e($erreur) ?></p><?php endif; ?>
  <form method="post">
    <label>Identifiant <input name="login" required autofocus></label>
    <label>Mot de passe <input type="password" name="mot_de_passe" required></label>
    <button type="submit">Se connecter</button>
  </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
