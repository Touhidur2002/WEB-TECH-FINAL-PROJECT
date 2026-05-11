<?php
require_once __DIR__ . '/../config/database.php';

function placeBid($listing_id, $buyer_id, $amount) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO bids (listing_id, buyer_id, amount) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iid", $listing_id, $buyer_id, $amount);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function updateCurrentBid($listing_id, $amount) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE listings SET current_bid = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $amount, $listing_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function getRecentBids($listing_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT b.amount, b.created_at, u.name as bidder_name
                            FROM bids b
                            JOIN users u ON b.buyer_id = u.id
                                   
                            WHERE b.listing_id = ?
                                   ORDER BY b.amount DESC
                      LIMIT 10");
    mysqli_stmt_bind_param($stmt, "i", $listing_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function getBidsByBuyer($buyer_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT b.id, b.amount, b.created_at, b.listing_id,
                    l.title, l.current_bid, l.status, l.winner_bid_id
                       FROM bids b
                                   JOIN listings l ON b.listing_id = l.id
                  WHERE b.buyer_id = ?
          ORDER BY b.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $buyer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function getWinnerBid($bid_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT b.*, u.name as winner_name, u.email as winner_email
                                   FROM bids b
                                   JOIN users u ON b.buyer_id = u.id
                                   WHERE b.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $bid_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function getTotalBids() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM bids");
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'];
}

function getHighestSale() {
    global $conn;
    $result = mysqli_query($conn, "SELECT MAX(current_bid) as highest FROM listings WHERE status = 'ended' AND winner_bid_id IS NOT NULL");
    $row = mysqli_fetch_assoc($result);
    return $row['highest'] ? $row['highest'] : 0;
}
?>
