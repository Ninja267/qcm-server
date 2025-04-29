<?php
require_once __DIR__ . '/../config/db.php';

$erreur = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération et nettoyage
    $nom      = trim($_POST['nom']      ?? '');
    $email    = trim($_POST['email']    ?? '');
    $statut   = $_POST['statut']        ?? 'eleve';          // valeur par défaut
    $pass     = $_POST['mot_de_passe']  ?? '';
    $pass2    = $_POST['mot_de_passe2'] ?? '';

    // 2. Validation minimale
    if ($nom === '' || $email === '' || $pass === '') {
        $erreur = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Email invalide.';
    } elseif (!in_array($statut, ['prof', 'eleve'], true)) {
        $erreur = 'Statut invalide.';
    } elseif ($pass !== $pass2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        // 3. Vérifier unicité nom OU email
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE nom = :nom OR email = :email');
        $stmt->execute(['nom' => $nom, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = 'Cet utilisateur existe déjà.';
        } else {
            // 4. Insertion
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (nom, mot_de_passe, statut, email)
                VALUES (:nom, :pass, :statut, :email)
            ');
            $stmt->execute([
                'nom'    => $nom,
                'pass'   => $hash,
                'statut' => $statut,
                'email'  => $email,
            ]);
            $ok = 'Compte créé ! Connecte-toi maintenant.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
</head>
<body>
    <h1>Créer un compte</h1>

    <?php if ($erreur): ?>
        <p style="color:red"><?= htmlspecialchars($erreur) ?></p>
    <?php elseif ($ok): ?>
        <p style="color:green"><?= htmlspecialchars($ok) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Nom  
            <input name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
        </label><br>

        <label>Email  
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </label><br>

        <label>Statut  
            <select name="statut">
                <option value="prof"  <?= (($_POST['statut'] ?? '') === 'prof')  ? 'selected' : '' ?>>Professeur</option>
                <option value="eleve" <?= (($_POST['statut'] ?? '') === 'eleve') ? 'selected' : '' ?>>Élève</option>
            </select>
        </label><br>

        <label>Mot de passe  
            <input type="password" name="mot_de_passe" required>
        </label><br>

        <label>Confirmer le mot de passe  
            <input type="password" name="mot_de_passe2" required>
        </label><br>

        <button type="submit">Créer le compte</button>
    </form>

    <p>Déjà inscrit ? <a href="index.php?page=login">Connexion</a></p>
</body>
</html>