<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../models/listingModel.php';
require_once '../models/bidModel.php';

$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// close expired auctions
close_expired_auctions();

$listing = getListingById($listing_id);

if (!$listing) {
    header("Location: home.php");
    exit;
}

$recent_bids = getRecentBids($listing_id);
$winner = null;
if ($listing['status'] == 'ended' && $listing['winner_bid_id']) {
    $winner = getWinnerBid($listing['winner_bid_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($listing['title']); ?> - Online Auction System</title>
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

<h2><?php echo htmlspecialchars($listing['title']); ?></h2>

<div class="listing-details">
    <div class="listing-img">
        <?php if ($listing['image_path']): ?>
            <img src="../public/uploads/listings/<?php echo htmlspecialchars($listing['image_path']); ?>" alt="Listing Image">
        <?php else: ?>
            <div class="no-img">No Image</div>
        <?php endif; ?>
    </div>

    <div class="listing-info">
        <table>
            <tr><td>Category</td><td><?php echo htmlspecialchars($listing['category_name']); ?></td></tr>
            <tr><td>Seller</td><td><?php echo htmlspecialchars($listing['seller_name']); ?></td></tr>
            <tr><td>Seller Email</td><td><?php echo htmlspecialchars($listing['seller_email']); ?></td></tr>
            <tr><td>Starting Price</td><td>$<?php echo number_format($listing['starting_price'], 2); ?></td></tr>
            <tr>
                <td>Current Bid</td>
                <td><strong id="current-bid">$<?php echo number_format($listing['current_bid'], 2); ?></strong></td>
            </tr>
            <tr><td>Status</td><td><?php echo strtoupper($listing['status']); ?></td></tr>
            <tr>
                <td>Time Remaining</td>
                <td>
                    <?php if ($listing['status'] == 'active'): ?>
                        <span class="countdown" data-end="<?php echo $listing['end_datetime']; ?>">Loading...</span>
                    <?php else: ?>
                        Auction Ended
                    <?php endif; ?>
                </td>
            </tr>
            <tr><td>End Date</td><td><?php echo $listing['end_datetime']; ?></td></tr>
        </table>

        <br>
        <strong>Description:</strong>
        <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
    </div>
</div>

<?php if ($listing['status'] == 'ended'): ?>
    <?php if ($winner): ?>
        <div class="winner-box">
            <strong>Winner:</strong> <?php echo htmlspecialchars($winner['winner_name']); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($winner['winner_email']); ?><br>
            <strong>Winning Amount:</strong> $<?php echo number_format($winner['amount'], 2); ?>
        </div>
    <?php else: ?>
        <div class="reserve-not-met">
            <?php
            if (count($recent_bids) == 0) {
                echo "Auction ended with no bids.";
            } else {
                echo "Auction ended - Reserve price not met.";
            }
            ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($listing['status'] == 'active' && $_SESSION['user_id'] != $listing['seller_id']): ?>
<div class="bid-section">
    <h3>Place a Bid</h3>
    <p>Current bid: <strong>$<?php echo number_format($listing['current_bid'], 2); ?></strong> — your bid must be higher.</p>
    <input type="number" id="bid-amount" step="0.01" min="<?php echo $listing['current_bid'] + 0.01; ?>" placeholder="Enter bid amount">
    <button onclick="placeBid()" style="margin-left:6px; padding:7px 14px; background:#2a6db5; color:#fff; border:none; border-radius:3px; cursor:pointer;">Place Bid</button>
    <div id="bid-message"></div>
</div>
<?php elseif ($listing['status'] == 'active' && $_SESSION['user_id'] == $listing['seller_id']): ?>
    <p><em>You cannot bid on your own listing.</em></p>
<?php endif; ?>

<div style="margin-top:20px;">
    <h3>Recent Bids (Last 10)</h3>
    <table id="bids-table">
        <tr>
            <th>Bidder</th>
            <th>Amount</th>
            <th>Time</th>
        </tr>
        <tbody id="bids-body">
        <?php if (empty($recent_bids)): ?>
            <tr id="no-bids-row"><td colspan="3">No bids yet.</td></tr>
        <?php else: ?>
            <?php foreach ($recent_bids as $bid): ?>
            <tr>
                <td><?php echo htmlspecialchars($bid['bidder_name']); ?></td>
                <td>$<?php echo number_format($bid['amount'], 2); ?></td>
                <td><?php echo $bid['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>

function updateCountdown() {
    var spans = document.querySelectorAll('.countdown');
    var now = Math.floor(Date.now() / 1000);
    spans.forEach(function(span) {
        var end = Math.floor(new Date(span.getAttribute('data-end')).getTime() / 1000);
        var diff = end - now;
        if (diff <= 0) {
            span.textContent = 'Ended';
        } else {
            var d = Math.floor(diff / 86400);
            var h = Math.floor((diff % 86400) / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            span.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
        }
    });
}
setInterval(updateCountdown, 1000);
updateCountdown();


function placeBid() {
    var amount = document.getElementById('bid-amount').value;
    var msg = document.getElementById('bid-message');

    if (!amount || parseFloat(amount) <= 0) {
        msg.className = 'error-msg';
        msg.textContent = 'Please enter a valid bid amount.';
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controllers/bid.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var data = JSON.parse(xhr.responseText);
            if (data.ok) {
                msg.className = 'success-msg';
                msg.textContent = data.msg;
                document.getElementById('current-bid').textContent = '$' + data.new_bid;
                var tbody = document.getElementById('bids-body');
                var noRow = document.getElementById('no-bids-row');
                if (noRow) noRow.remove();
                tbody.innerHTML = data.html;
                document.getElementById('bid-amount').value = '';
            } else {
                msg.className = 'error-msg';
                msg.textContent = data.msg;
            }
        }
    };
    xhr.send('action=place_bid&listing_id=<?php echo $listing_id; ?>&amount=' + encodeURIComponent(amount));
}
</script>

</body>
</html>
