<?php
require_once __DIR__ . '/../config/database.php';

function registerUser($name, $email, $phone, $bio, $hash) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, bio, password_hash, role, seller_verified) VALUES (?, ?, ?, ?, ?, 'buyer', 0)");
    mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $bio, $hash);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function emailExists($email) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? true : false;
}

function getUserByEmail($email) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function getUserById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function submitSellerRequest($user_id, $motivation) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET seller_request_motivation = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $motivation, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function getPendingSellerRequests() {
    global $conn;
    $result = mysqli_query($conn, "SELECT id, name, email, seller_request_motivation FROM users WHERE seller_request_motivation IS NOT NULL AND seller_verified = 0 AND role = 'buyer'");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function approveSeller($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET seller_verified = 1, seller_request_motivation = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function rejectSeller($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET seller_request_motivation = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
