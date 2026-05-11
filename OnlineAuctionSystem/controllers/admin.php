<?php

session_start();
require_once '../config/database.php';
require_once '../models/userModel.php';
require_once '../models/listingModel.php';
require_once '../models/bidModel.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../views/login.php");
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'get_stats') {
    header('Content-Type: application/json');


    $result = mysqli_query($conn,
        "SELECT c.name AS category, COUNT(l.id) AS total
         FROM listings l
         JOIN categories c ON l.category_id = c.id
         WHERE l.status = 'ended'
         GROUP BY l.category_id
         ORDER BY total DESC
         LIMIT 5");

    $labels = [];
    $data   = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['category'];
        $data[]   = (int) $row['total'];
    }

    echo json_encode(['ok' => true, 'labels' => $labels, 'data' => $data]);
    exit;
}

if ($action == 'approve_seller' || $action == 'reject_seller') {
    header('Content-Type: application/json');
    $user_id = intval($_POST['user_id']);

    if ($action == 'approve_seller') {
        approveSeller($user_id);
        echo json_encode(['ok' => true, 'msg' => 'Seller approved.']);
    } else {
        rejectSeller($user_id);
        echo json_encode(['ok' => true, 'msg' => 'Seller rejected.']);
    }
    exit;
}

if ($action == 'add_category') {
    $name = trim($_POST['cat_name']);
    if (empty($name)) {
        $_SESSION['cat_error'] = "Category name is required.";
        header("Location: ../views/admin.php");
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['cat_error'] = "Category already exists.";
        mysqli_stmt_close($stmt);
        header("Location: ../views/admin.php");
        exit;
    }
    mysqli_stmt_close($stmt);
    $stmt2 = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
    mysqli_stmt_bind_param($stmt2, "s", $name);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    $_SESSION['cat_success'] = "Category added.";
    header("Location: ../views/admin.php");
    exit;
}

if ($action == 'edit_category') {
    $cat_id = intval($_POST['cat_id']);
    $name = trim($_POST['cat_name']);

    if (empty($name)) {
        $_SESSION['cat_error'] = "Category name is required.";
        header("Location: ../views/admin.php");
        exit;
    }
    $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $name, $cat_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['cat_success'] = "Category updated.";
    header("Location: ../views/admin.php");
    exit;
}

if ($action == 'delete_category') {
    $cat_id = intval($_POST['cat_id']);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM listings WHERE category_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cat_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row['cnt'] > 0) {
        $_SESSION['cat_error'] = "Cannot delete category with existing listings.";
        header("Location: ../views/admin.php");
        exit;
    }
    $stmt2 = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $cat_id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    $_SESSION['cat_success'] = "Category deleted.";
    header("Location: ../views/admin.php");
    exit;
}

?>
