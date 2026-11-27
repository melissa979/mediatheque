<?php
/**
 * À exécuter UNE SEULE FOIS après l'import du script SQL, puis À SUPPRIMER.
 */
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/models/Utilisateur.php';

$db = Database::get();
if ($db->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn() > 0) {
    exit('Installation déjà faite. Supprimez ce fichier.');
}

$admin = Utilisateur::creer('admin', 'Admin2026!', 'Sophie Admin', 'admin');
$agent = Utilisateur::creer('agent', 'Agent2026!', 'Paul Agent', 'agent');

// Emprunts de test : EX0002 par Léa (en cours), EX0003 par Karim (en retard de 5 jours)
$req = $db->prepare('INSERT INTO emprunt (adherent_id, exemplaire_id, utilisateur_id, date_emprunt, date_retour_prevue)
    VALUES (?, ?, ?, CURDATE() - INTERVAL ? DAY, CURDATE() + INTERVAL ? DAY)');
$req->execute([1, 2, $agent, 10, 18]);
$req->execute([4, 3, $agent, 33, -5]);

echo 'Installation terminée. Comptes : admin / Admin2026! et agent / Agent2026!<br>';
echo '<strong>Supprimez maintenant le fichier installer.php.</strong> <a href="login.php">Se connecter</a>';