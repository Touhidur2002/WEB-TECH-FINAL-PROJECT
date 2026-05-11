<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../models/bidModel.php';
require_once '../models/listingModel.php';

$user_id = $_SESSION['user_id'];
close_expired_auctions(); //requirment 

$my_bids = getBidsByBuyer($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bids - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
<div class="nav">
    <a href="home.php">Browse Auctions</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="myBids.php">My Bids</a>
    <?php if ($_SESSION['seller_verified'] == 1): ?>
        <a href="createListing.php">Create Listing</a>
    <?php endif; ?>
    <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="admin.php">Admin Panel</a>
    <?php endif; ?>
    <a href="#" onclick="document.getElementById('logout-form').submit(); return false;">Logout (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a>
</div>
<form id="logout-form" method="POST" action="../controllers/loginValidation.php" style="display:none;">
    <input type="hidden" name="action" value="logout">
</form>

<h2>My Bids</h2>

<?php if (empty($my_bids)): ?>
    <p>You have not placed any bids yet.</p>
<?php else: ?>
<table>
    <tr>
        <th>Auction Title</th>
        <th>My Bid</th>
        <th>Current Highest Bid</th>
        <th>Auction Status</th>
        <th>My Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($my_bids as $bid): ?>
    <tr>
        <td><?php echo htmlspecialchars($bid['title']); ?></td>
        <td>$<?php echo number_format($bid['amount'], 2); ?></td>
        <td>$<?php echo number_format($bid['current_bid'], 2); ?></td>
        <td><?php echo strtoupper($bid['status']); ?></td>
        <td>
            <?php
            $my_status = '';
            $css_class = '';
            if ($bid['status'] == 'active') {
                if ($bid['amount'] >= $bid['current_bid']) {
                    $my_status = 'Leading';
                    $css_class = 'status-leading';
                } else {
                    $my_status = 'Outbid';
                    $css_class = 'status-outbid';
                }
            } elseif ($bid['status'] == 'ended') {
                if ($bid['winner_bid_id'] && abs($bid['amount'] - $bid['current_bid']) < 0.001) {
                    $my_status = 'Won';
                    $css_class = 'status-won';
                } else {
                    $my_status = 'Lost';
                    $css_class = 'status-lost';
                }
            } elseif ($bid['status'] == 'cancelled') {
                $my_status = 'Cancelled';
                $css_class = 'status-lost';
            }
            ?>
            <span class="<?php echo $css_class; ?>"><?php echo $my_status; ?></span>
        </td>
        <td><a class="btn" href="listingDetails.php?id=<?php echo $bid['listing_id']; ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>
