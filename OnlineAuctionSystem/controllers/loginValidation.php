<?php
session_start();
require_once '../models/userModel.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'register') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $bio      = trim($_POST['bio']);
    $password = $_POST['password'];
    $errors   = [];

    if (empty($name))    $errors[] = "Name is required.";
    if (empty($phone))   $errors[] = "Phone is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && emailExists($email)) {
        $errors[] = "Email already registered.";
    }

    if (!empty($errors)) {
        $_SESSION['reg_errors'] = $errors;
        $_SESSION['reg_old'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'bio' => $bio];
        header("Location: ../views/registration.php");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (registerUser($name, $email, $phone, $bio, $hash)) {
        $_SESSION['reg_success'] = "Registration successful. Please login.";
        header("Location: ../views/login.php");
    } else {
        $_SESSION['reg_errors'] = ["Registration failed. Try again."];
        header("Location: ../views/registration.php");
    }
    exit;

} elseif ($action == 'login') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_errors'] = ["Email and password are required."];
        header("Location: ../views/login.php");
        exit;
    }

    $user = getUserByEmail($email);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['name']            = $user['name'];
        $_SESSION['role']            = $user['role'];
        $_SESSION['seller_verified'] = $user['seller_verified'];

        if ($user['role'] == 'admin') {
            header("Location: ../views/admin.php");
        } else {
            header("Location: ../views/home.php");
        }
        exit;
    }

    $_SESSION['login_errors'] = ["Invalid email or password."];
    header("Location: ../views/login.php");
    exit;

} elseif ($action == 'logout') {
    session_destroy();
    header("Location: ../views/login.php");
    exit;

} elseif ($action == 'seller_request') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../views/login.php");
        exit;
    }
    $motivation = trim($_POST['motivation']);
    if (empty($motivation)) {
        $_SESSION['seller_req_error'] = "Please explain why you want to be a seller.";
        header("Location: ../views/dashboard.php");
        exit;
    }
    submitSellerRequest($_SESSION['user_id'], $motivation);
    $_SESSION['seller_req_success'] = "Seller request submitted.";
    header("Location: ../views/dashboard.php");
    exit;
}
?>
