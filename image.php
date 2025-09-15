<?php
require 'db/db.php';
$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT image, image_type FROM livres WHERE id = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch(PDO::FETCH_ASSOC);
if ($livre && $livre['image']) {
    header("Content-Type: ".$livre['image_type']);
    echo $livre['image'];
    exit;
}
