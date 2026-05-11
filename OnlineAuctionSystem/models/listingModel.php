<?php

require_once __DIR__ . '/../config/database.php';

function getAllCategories() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getCategoryById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function createListing($seller_id, $category_id, $title, $description, $starting_price, $reserve_price, $image_path, $end_datetime) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO listings (seller_id, category_id, title, description, starting_price, reserve_price, current_bid, image_path, end_datetime, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    mysqli_stmt_bind_param($stmt, "iissddsss", $seller_id, $category_id, $title, $description, $starting_price, $reserve_price, $starting_price, $image_path, $end_datetime);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function getActiveListings($category_id = null, $keyword = null) {
    global $conn;

    close_expired_auctions();

    $sql = "SELECT l.*, c.name as category_name, u.name as seller_name 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN users u ON l.seller_id = u.id 
            WHERE l.status = 'active'";
    $params = [];
    $types = "";

    if (!empty($category_id)) {
        $sql .= " AND l.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    if (!empty($keyword)) {
        $sql .= " AND (l.title LIKE ? OR l.description LIKE ?)";
        $kw = "%" . $keyword . "%";
        $params[] = $kw;
        $params[] = $kw;
        $types .= "ss";
    }
    $sql .= " ORDER BY l.created_at DESC";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getListingById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT l.*, c.name as category_name, u.name as seller_name, u.email as seller_email 
                                   FROM listings l 
                                   JOIN categories c ON l.category_id = c.id 
                                   JOIN users u ON l.seller_id = u.id 
                                   WHERE l.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function getListingsBySeller($seller_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT l.*, c.name as category_name,
                                   (SELECT COUNT(*) FROM bids WHERE listing_id = l.id) as bid_count
                                   FROM listings l
                                   JOIN categories c ON l.category_id = c.id
                                   WHERE l.seller_id = ?
                                   ORDER BY l.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function getBidCount($listing_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM bids WHERE listing_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $listing_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['cnt'];
}

function updateListing($id, $title, $description, $image_path) {
    global $conn;
    if ($image_path) {
        $stmt = mysqli_prepare($conn, "UPDATE listings SET title = ?, description = ?, image_path = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $image_path, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE listings SET title = ?, description = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $title, $description, $id);
    }
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function cancelListing($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE listings SET status = 'cancelled' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function close_expired_auctions() {
    global $conn;


    $stmt = mysqli_prepare($conn,
        "SELECT id, reserve_price, current_bid
         FROM listings
         WHERE status = 'active' AND end_datetime < NOW()");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($listing = mysqli_fetch_assoc($result)) {
        $listing_id  = (int) $listing['id'];
        $reserve     = $listing['reserve_price'];
        $current_bid = (float) $listing['current_bid'];

       
        $bid_stmt = mysqli_prepare($conn,
            "SELECT id FROM bids WHERE listing_id = ? ORDER BY amount DESC LIMIT 1");
        mysqli_stmt_bind_param($bid_stmt, 'i', $listing_id);
        mysqli_stmt_execute($bid_stmt);
        $bid_result = mysqli_stmt_get_result($bid_stmt);
        $top_bid    = mysqli_fetch_assoc($bid_result);
        mysqli_stmt_close($bid_stmt);

        if ($top_bid) {
            $winner_bid_id  = (int) $top_bid['id'];
            $reserve_met    = ($reserve === null || $current_bid >= (float) $reserve);
            $upd = mysqli_prepare($conn,
                "UPDATE listings
                 SET status = 'ended', winner_bid_id = ?
                 WHERE id = ? AND status = 'active'");
            $w = $reserve_met ? $winner_bid_id : null;
            mysqli_stmt_bind_param($upd, 'ii', $w, $listing_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        } else {
        
            $upd = mysqli_prepare($conn,
                "UPDATE listings
                 SET status = 'ended', winner_bid_id = NULL
                 WHERE id = ? AND status = 'active'");
            mysqli_stmt_bind_param($upd, 'i', $listing_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        }
    }
    mysqli_stmt_close($stmt);
}


function getEndedListingsBySeller($seller_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT l.*, c.name as category_name,
                                   b.amount as winning_amount, b.buyer_id as winner_id,
                                   u.name as winner_name, u.email as winner_email
                                   FROM listings l
                                   JOIN categories c ON l.category_id = c.id
                                   LEFT JOIN bids b ON l.winner_bid_id = b.id
                                   LEFT JOIN users u ON b.buyer_id = u.id
                                   WHERE l.seller_id = ? AND l.status = 'ended'
                                   ORDER BY l.end_datetime DESC");
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

?>
