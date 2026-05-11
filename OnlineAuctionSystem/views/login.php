<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

$errors = [];
if (isset($_SESSION['login_errors'])) {
    $errors = $_SESSION['login_errors'];
    unset($_SESSION['login_errors']);
}
$success = '';
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
<div class="nav">
    <a href="login.php">Login</a>
    <a href="registration.php">Register</a>
</div>

<div class="container">
    <h2>Login</h2>

    <?php if (!empty($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="../controllers/loginValidation.php">
        <input type="hidden" name="action" value="login">

        <label>Email *</label>
        <input type="email" name="email">

        <label>Password *</label>
        <input type="password" name="password">

        <br>
        <input type="submit" value="Login">
    </form>

    <p style="margin-top:12px;">Don't have an account? <a href="registration.php">Register here</a></p>
</div>
</body>
</html>
