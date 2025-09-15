<?php
require 'db/db.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

// Gestion du retour de livre depuis l'historique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rendre'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $livre_id = intval($_POST['livre_id']);

    $pdo->prepare("UPDATE reservations SET statut = 'Rendu' WHERE id = ? AND user_id = ?")
        ->execute([$reservation_id, $user_id]);

    $pdo->prepare("UPDATE livres SET statut = 'Disponible' WHERE id = ?")
        ->execute([$livre_id]);

    header("Location: historique.php");
    exit;
}

// Récupération des livres réservés par l'utilisateur
$livres = $pdo->prepare("
    SELECT r.id AS reservation_id, l.id AS livre_id, l.titre, l.auteur, l.pdf, l.image, c.nom AS categorie
    FROM reservations r
    JOIN livres l ON r.livre_id = l.id
    LEFT JOIN categories c ON l.id_categorie = c.id
    WHERE r.user_id = ? AND r.statut = 'Réservé'
    ORDER BY r.date_reservation DESC
");
$livres->execute([$user_id]);
$livres = $livres->fetchAll(PDO::FETCH_ASSOC);


// Récupération des réservations de l'utilisateur
$query = "
    SELECT r.id AS reservation_id, l.id AS livre_id, l.titre, l.auteur, l.date_publication, 
           l.pdf, r.date_reservation, r.statut, c.nom AS categorie
    FROM reservations r
    JOIN livres l ON r.livre_id = l.id
    LEFT JOIN categories c ON l.id_categorie = c.id
    WHERE r.user_id = ?
    ORDER BY r.date_reservation DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="css/style.css">
<link rel="shortcut icon" href="img/icon.png" type="image/x-icon">
<title>Bibliotèque B2</title>
<div class="container">

<!-- LIVRES RÉSERVÉS -->
<?php if (count($livres) > 0): ?>
    <h2>Mes livres réservés</h2>
    <div class="books-grid">
        <?php foreach ($livres as $livre): ?>
            <div class="book-card">
                <div class="book-image">
                    <?php if (!empty($livre['image'])): ?>
                        <img src="image.php?id=<?= $livre['livre_id']; ?>" alt="<?= htmlspecialchars($livre['titre']); ?>">
                    <?php else: ?>
                        <img src="livre-default.webp" alt="Livre par défaut">
                    <?php endif; ?>
                </div>
                <div class="book-content">
                    <h3><?= htmlspecialchars($livre['titre']); ?></h3>
                    <p><strong>Auteur :</strong> <?= htmlspecialchars($livre['auteur']); ?></p>
                    <p><strong>Catégorie :</strong> <?= htmlspecialchars($livre['categorie'] ?? '-'); ?></p>

                    <!-- Bouton Lire -->
                    <?php if (!empty($livre['pdf'])): ?>
                        <a href="pdf.php?id=<?= $livre['livre_id']; ?>" class="btn" target="_blank" style="background:#3b82f6; display:block; margin-bottom:5px;">Lire</a>
                    <?php endif; ?>

                    <!-- Bouton Rendre -->
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="reservation_id" value="<?= $livre['reservation_id']; ?>">
                        <input type="hidden" name="livre_id" value="<?= $livre['livre_id']; ?>">
                        <button type="submit" name="rendre" class="btn" style="background:#10b981; display:block;">Rendre</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Aucun livre réservé actuellement.</p>
<?php endif; ?>

    <h2>Historique de vos réservations</h2>
    <?php if (count($reservations) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Auteur</th>
                    <th>Catégorie</th>
                    <th>Date de publication</th>
                    <th>Date de réservation</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['titre']); ?></td>
                        <td><?= htmlspecialchars($res['auteur']); ?></td>
                        <td><?= htmlspecialchars($res['categorie'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($res['date_publication']); ?></td>
                        <td><?= htmlspecialchars($res['date_reservation']); ?></td>
                        <td class="status <?= strtolower(str_replace(' ', '-', $res['statut'])); ?>">
                            <?= htmlspecialchars($res['statut']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune réservation passée.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
