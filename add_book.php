<?php
require 'db/db.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

$add_message = '';
$edit_livre = null;

// Modifier un livre
if ($role === 'admin' && isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_livre = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Gestion des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    $titre = $_POST['titre'] ?? '';
    $auteur = $_POST['auteur'] ?? '';
    $date_pub = $_POST['date_publication'] ?? '';
    $categorie = $_POST['categorie'] ?? null;
    $image_blob = !empty($_FILES['image']['tmp_name']) ? file_get_contents($_FILES['image']['tmp_name']) : null;
    $image_type = $_FILES['image']['type'] ?? null;
    $pdf_blob = !empty($_FILES['pdf']['tmp_name']) ? file_get_contents($_FILES['pdf']['tmp_name']) : null;
    $pdf_type = $_FILES['pdf']['type'] ?? null;

    if (isset($_POST['ajouter_livre']) || isset($_POST['modifier_livre'])) {
        if (isset($_POST['modifier_livre'])) {
            $livre_id = intval($_POST['livre_id']);
            $stmt = $pdo->prepare("
                UPDATE livres SET titre=?, auteur=?, date_publication=?, id_categorie=?,
                image=COALESCE(?, image), image_type=COALESCE(?, image_type),
                pdf=COALESCE(?, pdf), pdf_type=COALESCE(?, pdf_type)
                WHERE id=?
            ");
            $stmt->execute([$titre,$auteur,$date_pub,$categorie,$image_blob,$image_type,$pdf_blob,$pdf_type,$livre_id]);
            $add_message = "Livre modifié avec succès !";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO livres (titre, auteur, date_publication, id_categorie, statut, visible, image, image_type, pdf, pdf_type)
                VALUES (?, ?, ?, ?, 'Disponible', 1, ?, ?, ?, ?)
            ");
            $stmt->execute([$titre,$auteur,$date_pub,$categorie,$image_blob,$image_type,$pdf_blob,$pdf_type]);
            $add_message = "Livre ajouté avec succès !";
        }
        header("Location: add_book.php");
        exit;
    }

    if (isset($_POST['rendre_admin'])) {
        $pdo->prepare("UPDATE reservations SET statut='Rendu' WHERE id=?")->execute([intval($_POST['reservation_id'])]);
        $pdo->prepare("UPDATE livres SET statut='Disponible' WHERE id=?")->execute([intval($_POST['livre_id'])]);
        header("Location: add_book.php");
        exit;
    }

    if (isset($_POST['toggle_visibility'])) {
        $pdo->prepare("UPDATE livres SET visible = NOT visible WHERE id=?")->execute([intval($_POST['livre_id'])]);
        header("Location: add_book.php");
        exit;
    }

    if (isset($_POST['delete_livre'])) {
        $pdo->prepare("DELETE FROM livres WHERE id=?")->execute([intval($_POST['livre_id'])]);
        $pdo->prepare("DELETE FROM reservations WHERE livre_id=?")->execute([intval($_POST['livre_id'])]);
        header("Location: add_book.php");
        exit;
    }
}

// Catégories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// Livres
$livres_admin = [];
if ($role==='admin'){
    $stmt = $pdo->query("SELECT l.*, c.nom AS categorie_nom FROM livres l LEFT JOIN categories c ON l.id_categorie=c.id ORDER BY l.titre ASC");
    $livres_admin = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Réservations
$reservations = [];
if ($role==='admin'){
    $stmt = $pdo->query("
        SELECT r.id AS reservation_id, r.date_reservation,
               l.id AS livre_id, l.titre, l.auteur,
               u.nom AS utilisateur_nom
        FROM reservations r
        JOIN livres l ON r.livre_id = l.id
        JOIN utilisateurs u ON r.user_id = u.id
        WHERE r.statut='Réservé'
        ORDER BY r.date_reservation DESC
    ");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="css/style.css">
<title>Bibliothèque B2</title>

<div class="container">
    <h2><?= $edit_livre ? "Modifier le livre" : "Ajouter un nouveau livre" ?></h2>
    <?php if($add_message): ?><p class="status disponible"><?= htmlspecialchars($add_message) ?></p><?php endif; ?>

    <?php if($role==='admin'): ?>

    <!-- FORMULAIRE AJOUT / MODIF -->
    <form method="post" enctype="multipart/form-data" class="add-book-form">
        <input type="hidden" name="livre_id" value="<?= $edit_livre['id'] ?? '' ?>">
        <label for="titre">Titre</label>
        <input type="text" name="titre" id="titre" required value="<?= htmlspecialchars($edit_livre['titre'] ?? '') ?>">

        <label for="auteur">Auteur</label>
        <input type="text" name="auteur" id="auteur" required value="<?= htmlspecialchars($edit_livre['auteur'] ?? '') ?>">

        <label for="date_publication">Date de publication</label>
        <input type="date" name="date_publication" id="date_publication" required value="<?= $edit_livre['date_publication'] ?? '' ?>">

        <label for="categorie">Catégorie</label>
        <select name="categorie" id="categorie" required>
            <option value="">-- Catégorie --</option>
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($edit_livre['id_categorie']) && $edit_livre['id_categorie']==$cat['id'])?'selected':'' ?>>
                <?= htmlspecialchars($cat['nom']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if(!empty($edit_livre['image'])): ?>
        <div class="book-image"><img src="image.php?id=<?= $edit_livre['id'] ?>" alt="Image actuelle"></div>
        <?php endif; ?>
        <label for="image">Changer l'image</label>
        <input type="file" name="image" id="image" accept="image/*">

        <?php if(!empty($edit_livre['pdf'])): ?>
        <p>PDF actuel : <a href="pdf.php?id=<?= $edit_livre['id'] ?>" target="_blank">Voir / Télécharger</a></p>
        <?php endif; ?>
        <label for="pdf">Changer le PDF</label>
        <input type="file" name="pdf" id="pdf" accept="application/pdf">

        <button type="submit" name="<?= $edit_livre?'modifier_livre':'ajouter_livre' ?>">
            <?= $edit_livre?'Modifier Livre':'Ajouter Livre' ?>
        </button>
        <?php if($edit_livre): ?><a href="add_book.php" class="btn">Annuler</a><?php endif; ?>
    </form>

    <!-- RÉSERVATIONS -->
    <h2>Livres réservés</h2>
    <div class="books-grid">
    <?php if($reservations): ?>
        <?php foreach($reservations as $res): ?>
        <div class="book-card">
            <div class="book-content">
                <h3><?= htmlspecialchars($res['titre']) ?></h3>
                <p>Auteur: <?= htmlspecialchars($res['auteur']) ?></p>
                <p>Utilisateur: <?= htmlspecialchars($res['utilisateur_nom']) ?></p>
                <p>Date: <?= htmlspecialchars($res['date_reservation']) ?></p>
                <form method="post">
                    <input type="hidden" name="reservation_id" value="<?= $res['reservation_id'] ?>">
                    <input type="hidden" name="livre_id" value="<?= $res['livre_id'] ?>">
                    <button type="submit" name="rendre_admin" class="btn">Rendre</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune réservation en cours.</p>
    <?php endif; ?>
    </div>

    <!-- GESTION LIVRES -->
    <h2>Gestion des livres</h2>
    <div class="books-grid">
    <?php if($livres_admin): ?>
        <?php foreach($livres_admin as $livre): ?>
        <div class="book-card">
            <?php if($livre['image']): ?>
            <div class="book-image"><img src="image.php?id=<?= $livre['id'] ?>" alt="<?= htmlspecialchars($livre['titre']) ?>"></div>
            <?php endif; ?>
            <div class="book-content">
                <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                <p>Auteur: <?= htmlspecialchars($livre['auteur']) ?></p>
                <p>Catégorie: <?= htmlspecialchars($livre['categorie_nom'] ?? '-') ?></p>
                <span class="status <?= strtolower($livre['statut']) ?>"><?= htmlspecialchars($livre['statut']) ?></span>
                <div class="book-actions">
<form method="get" action="add_book.php" style="display:inline;">
    <input type="hidden" name="edit" value="<?= $livre['id'] ?>">
    <button type="submit" class="btn">Modifier</button>
</form>
                    
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="livre_id" value="<?= $livre['id'] ?>">
                        <button type="submit" name="toggle_visibility" class="btn">
                            <?= $livre['visible']?'Masquer':'Afficher' ?>
                        </button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce livre ?');">
                        <input type="hidden" name="livre_id" value="<?= $livre['id'] ?>">
                        <button type="submit" name="delete_livre" class="btn">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun livre dans la bibliothèque.</p>
    <?php endif; ?>
    </div>

    <?php else: ?>
    <p class="status en-attente">Accès refusé : seuls les administrateurs peuvent gérer la bibliothèque.</p>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
