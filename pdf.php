<?php
session_start();
require 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé.");
}

$user_id = $_SESSION['user_id'];
$livre_id = intval($_GET['id'] ?? 0);

if (!$livre_id) {
    die("Livre invalide.");
}

// Vérifier si l'utilisateur a une réservation valide
$stmt = $pdo->prepare("
    SELECT pdf, pdf_type 
    FROM livres l
    JOIN reservations r ON l.id = r.livre_id
    WHERE l.id = ? AND r.user_id = ? AND r.statut = 'Réservé'
");
$stmt->execute([$livre_id, $user_id]);
$livre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$livre || !$livre['pdf']) {
    die("Accès refusé ou PDF inexistant.");
}

// Envoyer le PDF
header('Content-Type: ' . $livre['pdf_type']);
header('Content-Disposition: inline; filename="livre.pdf"');
echo $livre['pdf'];
exit;
