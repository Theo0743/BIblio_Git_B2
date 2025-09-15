<?php
session_start();
require 'db/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer le rôle de l'utilisateur
$stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$role = $user['role'] ?? null;
?>

<!-- Font Awesome CDN pour les icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pbq+e+Wc3S6V8OEtvDh+DlN60C0/Gw6K2X+eUo1YoX13qY1Q7iFzWvN+60pp1hXc5zMj1z7dD+UuRz5dJ9e9Xg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<link rel="stylesheet" href="css/header.css">
<header class="site-header">
    <div class="container">
        <h1><i class="fas fa-book"></i> Bibliothèque en ligne</h1>
        <nav>
            <a href="books.php" class="btn"><i class="fas fa-home"></i> Accueil</a>
            <a href="historique.php" class="btn"><i class="fas fa-book-reader"></i> Mes livres</a>

            <?php if ($role === 'admin'): ?>
                <a href="add_book.php" class="btn"><i class="fas fa-cogs"></i> Gestion bibliothèque</a>
            <?php endif; ?>

            <a href="logout.php" class="btn logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </nav>
        <p>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name']); ?></strong></p>
    </div>
</header>
