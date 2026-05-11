<?php
session_start();
header('Content-Type: application/json');

require_once '../models/listingModel.php';
require_once '../models/bidModel.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'You must be logged in to bid.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'place_bid') {
    $id     = intval($_POST['listing_id']);
    $amount = floatval($_POST['amount']);
    $buyer  = $_SESSION['user_id'];

    $listing = getListingById($id);

    if (!$listing) {
        echo json_encode(['ok' => false, 'msg' => 'Listing not found.']);
        exit;
    }
    if ($listing['status'] != 'active') {
        echo json_encode(['ok' => false, 'msg' => 'Auction is not active.']);
        exit;
    }
    if (strtotime($listing['end_datetime']) <= time()) {
        echo json_encode(['ok' => false, 'msg' => 'Auction has expired.']);
        exit;
    }
    if ($amount <= $listing['current_bid']) {
        echo json_encode(['ok' => false, 'msg' => 'Bid must be higher than $' . number_format($listing['current_bid'], 2)]);
        exit;
    }
    if ($listing['seller_id'] == $buyer) {
        echo json_encode(['ok' => false, 'msg' => 'You cannot bid on your own listing.']);
        exit;
    }

    if (placeBid($id, $buyer, $amount)) {
        updateCurrentBid($id, $amount);

        $bids = getRecentBids($id);
        $html = '';
        foreach ($bids as $b) {
            $html .= '<tr><td>' . htmlspecialchars($b['bidder_name']) . '</td><td>$' . number_format($b['amount'], 2) . '</td><td>' . $b['created_at'] . '</td></tr>';
        }

        echo json_encode(['ok' => true, 'msg' => 'Bid placed!', 'new_bid' => number_format($amount, 2), 'html' => $html]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Failed to place bid.']);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Invalid action.']);
?>
