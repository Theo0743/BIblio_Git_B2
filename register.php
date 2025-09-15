<?php
session_start();
require 'db/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $message = "Cet email est déjà utilisé.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$nom, $email, $hash])) {
            $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } else {
            $message = "Erreur lors de l'inscription.";
        }
    }
}
?>
<link rel="stylesheet" href="css/login.css">
<link rel="shortcut icon" href="img/icon.png" type="image/x-icon">
<title>Bibliotèque B2</title>
<div class="register-wrapper">
    <div class="register-card">
        <h2><i class="fa-solid fa-user-plus"></i> Inscription</h2>

        <?php if (!empty($message)): ?>
            <p class="<?php echo (str_contains($message, 'réussie')) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="nom" placeholder="Nom" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <button type="submit" class="btn-register">
                <i class="fa-solid fa-user-check"></i> S'inscrire
            </button>
        </form>

        <p class="login-text">
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Déjà inscrit ? Connectez-vous</a>
        </p>
    </div>
</div>
