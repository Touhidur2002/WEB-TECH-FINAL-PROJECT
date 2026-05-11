<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../models/listingModel.php';
require_once '../models/userModel.php';
require_once '../models/bidModel.php';

$user_id = $_SESSION['user_id'];
$seller_verified = $_SESSION['seller_verified'];

$my_listings = getListingsBySeller($user_id);
$ended_listings = getEndedListingsBySeller($user_id);

$seller_req_error = '';
$seller_req_success = '';
$listing_success = '';

if (isset($_SESSION['seller_req_error'])) { $seller_req_error = $_SESSION['seller_req_error']; unset($_SESSION['seller_req_error']); }
if (isset($_SESSION['seller_req_success'])) { $seller_req_success = $_SESSION['seller_req_success']; unset($_SESSION['seller_req_success']); }
if (isset($_SESSION['listing_success'])) { $listing_success = $_SESSION['listing_success']; unset($_SESSION['listing_success']); }
if (isset($_SESSION['edit_success'])) { $listing_success = $_SESSION['edit_success']; unset($_SESSION['edit_success']); }

$user = getUserById($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>

<div class="nav">
    <a href="home.php">Browse Auctions</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="myBids.php">My Bids</a>
    <?php if ($seller_verified == 1): ?>
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

<h2>My Dashboard</h2>

<?php if ($listing_success): ?>
    <p class="success"><?php echo htmlspecialchars($listing_success); ?></p>
<?php endif; ?>

<?php if ($seller_verified == 0 && $_SESSION['role'] == 'buyer'): ?>
<div class="section">
    <h3>Become a Seller</h3>
    <?php if ($user['seller_request_motivation']): ?>
        <p class="success">Your seller request is pending admin approval.</p>
    <?php else: ?>
        <?php if ($seller_req_error): ?><p class="error"><?php echo htmlspecialchars($seller_req_error); ?></p><?php endif; ?>
        <?php if ($seller_req_success): ?><p class="success"><?php echo htmlspecialchars($seller_req_success); ?></p><?php endif; ?>
        <form method="POST" action="../controllers/loginValidation.php">
            <input type="hidden" name="action" value="seller_request">
            <label>Why do you want to be a seller?</label>
            <textarea name="motivation" rows="3" placeholder="Enter your motivation..."></textarea>
            <input type="submit" value="Submit Request">
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($seller_verified == 1): ?>
<div class="section">
    <h3>My Listings</h3>
    <a class="btn" href="createListing.php">+ Create New Listing</a>
    <br><br>
    <?php if (empty($my_listings)): ?>
        <p>No listings yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Starting Price</th>
            <th>Current Bid</th>
            <th>Bids</th>
            <th>Status</th>
            <th>Time Remaining</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($my_listings as $listing): ?>
        <?php if ($listing['status'] != 'ended'): ?>
        <tr id="row-<?php echo $listing['id']; ?>">
            <td><?php echo htmlspecialchars($listing['title']); ?></td>
            <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
            <td>$<?php echo number_format($listing['starting_price'], 2); ?></td>
            <td>$<?php echo number_format($listing['current_bid'], 2); ?></td>
            <td><?php echo $listing['bid_count']; ?></td>
            <td id="status-<?php echo $listing['id']; ?>"><?php echo $listing['status']; ?></td>
            <td>
                <?php if ($listing['status'] == 'active'): ?>
                    <span class="countdown" data-end="<?php echo $listing['end_datetime']; ?>">Loading...</span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>
                <a class="btn" href="editListing.php?id=<?php echo $listing['id']; ?>">Edit</a>
                <?php if ($listing['status'] == 'active' && $listing['bid_count'] == 0): ?>
                    <button class="btn-cancel" onclick="cancelListing(<?php echo $listing['id']; ?>)">Cancel</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<div class="section">
    <h3>Auction Results</h3>
    <?php if (empty($ended_listings)): ?>
        <p>No ended auctions yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Winning Amount</th>
            <th>Winner</th>
            <th>Winner Email</th>
            <th>Reserve Met</th>
        </tr>
        <?php foreach ($ended_listings as $listing): ?>
        <tr>
            <td><?php echo htmlspecialchars($listing['title']); ?></td>
            <td>Ended</td>
            <td><?php echo $listing['winning_amount'] ? '$' . number_format($listing['winning_amount'], 2) : 'No bids'; ?></td>
            <td><?php echo $listing['winner_name'] ? htmlspecialchars($listing['winner_name']) : '-'; ?></td>
            <td><?php echo $listing['winner_email'] ? htmlspecialchars($listing['winner_email']) : '-'; ?></td>
            <td>
                <?php
                if (!$listing['winner_bid_id'] && !$listing['winning_amount']) {
                    echo 'No bids';
                } elseif (!$listing['winner_bid_id']) {
                    echo 'Reserve Not Met';
                } else {
                    echo 'Yes';
                }
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>

function updateCountdowns() {
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
setInterval(updateCountdowns, 1000);
updateCountdowns();

function cancelListing(id) {
    if (!confirm('Cancel this listing?')) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controllers/listing.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var data = JSON.parse(xhr.responseText);
            if (data.ok) {
                document.getElementById('status-' + id).textContent = 'cancelled';
                alert(data.msg);
            } else {
                alert(data.msg);
            }
        }
    };
    xhr.send('action=cancel&listing_id=' + id);
}
</script>

</body>
</html>
