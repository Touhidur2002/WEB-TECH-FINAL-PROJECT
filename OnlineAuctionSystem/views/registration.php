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
if (isset($_SESSION['reg_errors'])) {
    $errors = $_SESSION['reg_errors'];
    unset($_SESSION['reg_errors']);
}
$old = [];
if (isset($_SESSION['reg_old'])) {
    $old = $_SESSION['reg_old'];
    unset($_SESSION['reg_old']);
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
    <title>Register - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
<div class="nav">
    <a href="login.php">Login</a>
    <a href="registration.php">Register</a>
</div>

<div class="container">
    <h2>Register</h2>

    <?php if (!empty($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p> //XSS protection
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="../controllers/loginValidation.php">
        <input type="hidden" name="action" value="register">

        <label>Name *</label>
        <input type="text" name="name" value="<?php echo isset($old['name']) ? htmlspecialchars($old['name']) : ''; ?>">

        <label>Email *</label>
        <input type="email" name="email" value="<?php echo isset($old['email']) ? htmlspecialchars($old['email']) : ''; ?>">

        <label>Phone *</label>
        <input type="text" name="phone" value="<?php echo isset($old['phone']) ? htmlspecialchars($old['phone']) : ''; ?>">

        <label>Bio</label>
        <textarea name="bio" rows="3"><?php echo isset($old['bio']) ? htmlspecialchars($old['bio']) : ''; ?></textarea>

        <label>Password * (min 8 characters)</label>
        <input type="password" name="password">

        <br>
        <input type="submit" value="Register">
    </form>

    <p style="margin-top:12px;">Already have an account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
