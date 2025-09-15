<?php
session_start();
require 'db/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        header("Location: books.php");
        exit;
    } else {
        $message = "Email ou mot de passe incorrect.";
    }
}
?>
<link rel="stylesheet" href="css/login.css">
<link rel="shortcut icon" href="img/icon.png" type="image/x-icon">
<title>Bibliotèque B2</title>
<div class="register-wrapper">
    <div class="register-card">
        <h2><i class="fa-solid fa-right-to-bracket"></i> Connexion</h2>

        <?php if (!empty($message)): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <button type="submit" class="btn-register">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </button>
        </form>

        <p class="login-text">
            <a href="register.php"><i class="fa-solid fa-user-plus"></i> Pas encore inscrit ?</a>
        </p>
    </div>
</div>
