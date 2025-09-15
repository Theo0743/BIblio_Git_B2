<?php
require 'db/db.php';
include 'header.php';

// ====================== GESTION RETOUR LIVRE ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rendre'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $livre_id = intval($_POST['livre_id']);

    $pdo->prepare("UPDATE reservations SET statut = 'Rendu' WHERE id = ? AND user_id = ?")
        ->execute([$reservation_id, $_SESSION['user_id']]);

    $pdo->prepare("UPDATE livres SET statut = 'Disponible' WHERE id = ?")
        ->execute([$livre_id]);

    header("Location: books.php");
    exit;
}

// ====================== GESTION RÉSERVATION ======================
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    $livre_id = intval($_POST['livre_id']);

    $stmt = $pdo->prepare("SELECT statut FROM livres WHERE id = ? AND visible = 1");
    $stmt->execute([$livre_id]);
    $livre = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($livre && $livre['statut'] === 'Disponible') {
        $pdo->prepare("INSERT INTO reservations (user_id, livre_id, statut) VALUES (?, ?, 'Réservé')")
            ->execute([$_SESSION['user_id'], $livre_id]);

        $pdo->prepare("UPDATE livres SET statut = 'Réservé' WHERE id = ?")
            ->execute([$livre_id]);

        $message = "Réservation réussie !";
    } else {
        $message = "Ce livre n'est pas disponible.";
    }
}

// ====================== FILTRES ======================
$filtreAuteur = $_GET['auteur'] ?? '';
$filtreCategorie = $_GET['categories'] ?? '';
$filtreDate = $_GET['date'] ?? '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// ====================== RÉCUPÉRATION DES LIVRES ======================
$query = "
SELECT l.*, c.nom AS categorie_nom,
       r.id AS reservation_id, r.date_reservation, r.statut AS statut_reservation,
       u.nom AS utilisateur_nom
FROM livres l
LEFT JOIN categories c ON l.id_categorie = c.id
LEFT JOIN reservations r ON l.id = r.livre_id AND r.statut = 'Réservé'
LEFT JOIN utilisateurs u ON r.user_id = u.id
WHERE l.visible = 1
";

$params = [];
if ($filtreAuteur) { $query .= " AND l.auteur LIKE ?"; $params[] = "%$filtreAuteur%"; }
if ($filtreCategorie) { $query .= " AND c.id = ?"; $params[] = $filtreCategorie; }
if ($filtreDate) { $query .= " AND l.date_publication = ?"; $params[] = $filtreDate; }

$query .= "ORDER BY l.titre ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="css/style.css">
<link rel="shortcut icon" href="img/icon.png" type="image/x-icon">
<title>Bibliotèque B2</title>
<div class="container">
    <h2>Liste des livres</h2>
    <?php if ($message) echo "<p class='success'>$message</p>"; ?>

    <div class="books-grid">
        <?php foreach ($livres as $livre): ?>
            <div class="book-card">
                <div class="book-image">
                    <?php if ($livre['image']): ?>
                        <img src="image.php?id=<?= $livre['id']; ?>" alt="<?= htmlspecialchars($livre['titre']); ?>">
                    <?php else: ?>
                        <img src="img/livre-default.webp" alt="Livre par défaut">
                    <?php endif; ?>
                </div>
                <div class="book-content">
                    <h3><?= htmlspecialchars($livre['titre']); ?></h3>
                    <p><strong>Auteur :</strong> <?= htmlspecialchars($livre['auteur']); ?></p>
                    <p><strong>Catégorie :</strong> <?= htmlspecialchars($livre['categorie_nom'] ?? '-'); ?></p>
                    <p><strong>Date :</strong> <?= htmlspecialchars($livre['date_publication']); ?></p>
                    <p class="status <?= strtolower(str_replace(' ', '-', $livre['statut'])); ?>">
                        <?= htmlspecialchars($livre['statut']); ?>
                    </p>
                    <div class="book-actions">
                        <?php if ($livre['statut'] === 'Disponible'): ?>
                            <form method="post">
                                <input type="hidden" name="livre_id" value="<?= $livre['id']; ?>">
                                <button type="submit" name="reserver" class="btn">Réserver</button>
                            </form>
                        <?php else: ?>
                            <button class="btn" disabled>Indisponible</button>
                        <?php endif; ?>

                        <?php
                        $reserved = false;
                        if ($livre['reservation_id'] && $livre['statut_reservation'] === 'Réservé' && $livre['utilisateur_nom'] === $_SESSION['user_name']) {
                            $reserved = true;
                        }
                        ?>
                        <?php if ($reserved && !empty($livre['pdf'])): ?>
                            <a href="pdf.php?id=<?= $livre['id']; ?>" target="_blank" class="btn" style="background:#3b82f6;">Lire</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include('footer.php'); ?>
