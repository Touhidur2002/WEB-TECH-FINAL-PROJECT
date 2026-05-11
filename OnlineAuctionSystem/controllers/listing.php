<?php
session_start();
require_once '../models/listingModel.php';
require_once '../models/userModel.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'create') {

    if ($_SESSION['seller_verified'] != 1) {
        header("Location: ../views/home.php");
        exit;
    }

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $starting_price = floatval($_POST['starting_price']);
    $reserve_price = isset($_POST['reserve_price']) && $_POST['reserve_price'] !== '' ? floatval($_POST['reserve_price']) : null;
    $end_datetime = trim($_POST['end_datetime']);
    $errors = [];

    if (empty($title)) $errors[] = "Title is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($category_id <= 0) $errors[] = "Category is required.";
    if ($starting_price <= 0) $errors[] = "Starting price must be positive.";
    if ($reserve_price !== null && $reserve_price < $starting_price) {
        $errors[] = "Reserve price must be greater than or equal to starting price.";
    }
    if (empty($end_datetime)) {
        $errors[] = "End date and time is required.";
    } else {
        $end_ts = strtotime($end_datetime);
        if ($end_ts === false || $end_ts <= time() + 3600) {
            $errors[] = "End date must be at least 1 hour from now.";
        }
    }

  
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 3 * 1024 * 1024; // 3MB
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            $errors[] = "Image must be JPEG or PNG.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image must be less than 3MB.";
        } else {
            $ext = ($mime == 'image/jpeg') ? 'jpg' : 'png';
            $filename = 'img_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_path = '../public/uploads/listings/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        $errors[] = "Image is required.";
    }

    if (!empty($errors)) {
        $_SESSION['listing_errors'] = $errors;
        $_SESSION['listing_old'] = compact('title', 'description', 'category_id', 'starting_price', 'reserve_price', 'end_datetime');
        header("Location: ../views/createListing.php");
        exit;
    }

    if (createListing($_SESSION['user_id'], $category_id, $title, $description, $starting_price, $reserve_price, $image_path, $end_datetime)) {
        $_SESSION['listing_success'] = "Listing created successfully.";
        header("Location: ../views/dashboard.php");
        exit;
    } else {
        $_SESSION['listing_errors'] = ["Failed to create listing."];
        header("Location: ../views/createListing.php");
        exit;
    }

} elseif ($action == 'edit') {
    if ($_SESSION['seller_verified'] != 1) {
        header("Location: ../views/home.php");
        exit;
    }

    $listing_id = intval($_POST['listing_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $errors = [];

    $listing = getListingById($listing_id);
    if (!$listing || $listing['seller_id'] != $_SESSION['user_id']) {
        header("Location: ../views/dashboard.php");
        exit;
    }

    $bid_count = getBidCount($listing_id);
    if ($bid_count > 0) {
        $_SESSION['edit_errors'] = ["Cannot edit listing with existing bids."];
        header("Location: ../views/editListing.php?id=" . $listing_id);
        exit;
    }

    if (empty($title)) $errors[] = "Title is required.";
    if (empty($description)) $errors[] = "Description is required.";


    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 3 * 1024 * 1024;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            $errors[] = "Image must be JPEG or PNG.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image must be less than 3MB.";
        } else {
            $ext = ($mime == 'image/jpeg') ? 'jpg' : 'png';
            $filename = 'img_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_path = '../public/uploads/listings/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['edit_errors'] = $errors;
        header("Location: ../views/editListing.php?id=" . $listing_id);
        exit;
    }

    updateListing($listing_id, $title, $description, $image_path);
    $_SESSION['edit_success'] = "Listing updated successfully.";
    header("Location: ../views/dashboard.php");
    exit;

} elseif ($action == 'search') {
    header('Content-Type: application/json');
    $keyword     = isset($_POST['keyword'])     ? trim($_POST['keyword'])     : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $listings    = getActiveListings($category_id ?: null, $keyword ?: null);
    echo json_encode(['ok' => true, 'listings' => $listings]);
    exit;

} elseif ($action == 'cancel') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'msg' => 'Not logged in.']);
        exit;
    }

    $id = intval($_POST['listing_id']);
    $listing = getListingById($id);

    if (!$listing || $listing['seller_id'] != $_SESSION['user_id']) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
        exit;
    }

    if (getBidCount($id) > 0) {
        echo json_encode(['ok' => false, 'msg' => 'Cannot cancel a listing with bids.']);
        exit;
    }

    cancelListing($id);
    echo json_encode(['ok' => true, 'msg' => 'Listing cancelled.']);
    exit;
}

?>
