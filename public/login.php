<?php
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom  = trim($_POST['nom'] ?? '');
    $pass = $_POST['mot_de_passe'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE nom = :nom LIMIT 1');
    $stmt->execute(['nom' => $nom]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['statut']  = $user['statut'];
        header('Location: index.php?page=' .
            ($user['statut'] === 'prof' ? 'prof/dashboard' : 'eleve/dashboard'));
        exit;
    }
    $erreur = 'Identifiants invalides !';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
</head>

<body>
    <h1>Connexion</h1>
    <?php if (!empty($erreur)) echo '<p style="color:red">' . $erreur . '</p>'; ?>
    <form method="post">
        <label>Nom <input name="nom" required></label><br>
        <label>Mot de passe <input type="password" name="mot_de_passe" required></label><br>
        <button type="submit">Se connecter</button>
    </form>
    <p><a href="index.php?page=register">Créer un compte</a></p>
</body>

</html>