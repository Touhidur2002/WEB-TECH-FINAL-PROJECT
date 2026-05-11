<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

require_once '../models/userModel.php';
require_once '../models/listingModel.php';
require_once '../models/bidModel.php';
require_once '../config/database.php';

close_expired_auctions();

$pending_requests = getPendingSellerRequests();
$categories = getAllCategories();

$total_active = 0;
$total_ended = 0;
$result = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM listings GROUP BY status");
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] == 'active') $total_active = $row['cnt'];
    if ($row['status'] == 'ended') $total_ended = $row['cnt'];
}
$total_bids = getTotalBids();
$highest_sale = getHighestSale();

$cat_error = '';
$cat_success = '';
if (isset($_SESSION['cat_error'])) { $cat_error = $_SESSION['cat_error']; unset($_SESSION['cat_error']); }
if (isset($_SESSION['cat_success'])) { $cat_success = $_SESSION['cat_success']; unset($_SESSION['cat_success']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="nav">
    <a href="home.php">Browse Auctions</a>
    <a href="admin.php">Admin Panel</a>
    <a href="#" onclick="document.getElementById('logout-form').submit(); return false;">Logout (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a>
</div>
<form id="logout-form" method="POST" action="../controllers/loginValidation.php" style="display:none;">
    <input type="hidden" name="action" value="logout">
</form>

<h2>Admin Panel</h2>

<div class="section">
    <h3>Dashboard Statistics</h3>
    <table class="stats-table" style="max-width:400px;">
        <tr><td>Total Active Auctions</td><td><?php echo $total_active; ?></td></tr>
        <tr><td>Total Ended Auctions</td><td><?php echo $total_ended; ?></td></tr>
        <tr><td>Total Bids Placed</td><td><?php echo $total_bids; ?></td></tr>
        <tr><td>Pending Seller Requests</td><td><?php echo count($pending_requests); ?></td></tr>
        <tr><td>Highest Sale Amount</td><td>$<?php echo number_format($highest_sale, 2); ?></td></tr>
    </table>

    <h4 style="margin-top:16px;">Top 5 Categories by Completed Auctions</h4>
    <div style="max-width:500px;">
        <canvas id="categoryChart"></canvas>
    </div>
</div>

<div class="section">
    <h3>Pending Seller Requests</h3>
    <?php if (empty($pending_requests)): ?>
        <p>No pending seller requests.</p>
    <?php else: ?>
    <table id="seller-requests-table">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Motivation</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($pending_requests as $req): ?>
        <tr id="req-row-<?php echo $req['id']; ?>">
            <td><?php echo htmlspecialchars($req['name']); ?></td>
            <td><?php echo htmlspecialchars($req['email']); ?></td>
            <td><?php echo htmlspecialchars($req['seller_request_motivation']); ?></td>
            <td>
                <button class="btn-approve" onclick="handleSeller(<?php echo $req['id']; ?>, 'approve_seller')">Approve</button>
                <button class="btn-reject" onclick="handleSeller(<?php echo $req['id']; ?>, 'reject_seller')">Reject</button>
                <span id="seller-msg-<?php echo $req['id']; ?>"></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<div class="section">
    <h3>Category Management</h3>

    <?php if ($cat_error): ?><p class="error"><?php echo htmlspecialchars($cat_error); ?></p><?php endif; ?>
    <?php if ($cat_success): ?><p class="success"><?php echo htmlspecialchars($cat_success); ?></p><?php endif; ?>

    <form method="POST" action="../controllers/admin.php" style="margin-bottom:15px;">
        <input type="hidden" name="action" value="add_category">
        <input type="text" name="cat_name" placeholder="New category name" required style="width:200px;">
        <input type="submit" value="Add Category" style="margin-top:0; margin-left:6px;">
    </form>

    <?php if (empty($categories)): ?>
        <p>No categories found.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td><?php echo $cat['id']; ?></td>
            <td><?php echo htmlspecialchars($cat['name']); ?></td>
            <td>
                <form method="POST" action="../controllers/admin.php" style="display:inline;">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                    <input type="text" name="cat_name" value="<?php echo htmlspecialchars($cat['name']); ?>" style="width:130px;">
                    <button class="btn-edit" type="submit">Update</button>
                </form>
                <form method="POST" action="../controllers/admin.php" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                    <button class="btn-delete" type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<script>

function handleSeller(uid, action) {
    if (!confirm('Are you sure?')) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controllers/admin.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var data = JSON.parse(xhr.responseText);
            if (data.ok) {
                var row = document.getElementById('req-row-' + uid);
                if (action == 'approve_seller') {
                    document.getElementById('seller-msg-' + uid).textContent = 'Approved ✓';
                    document.getElementById('seller-msg-' + uid).className = 'msg-approved';
                    var btns = row.querySelectorAll('button');
                    btns.forEach(function(b) { b.remove(); });
                } else {
                    if (row) row.remove();
                }
            } else {
                alert(data.msg);
            }
        }
    };
    xhr.send('action=' + action + '&user_id=' + uid);
}

(function() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controllers/admin.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var data = JSON.parse(xhr.responseText);
            if (!data.ok || data.labels.length === 0) return;
            var ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Completed Auctions',
                        data: data.data,
                        backgroundColor: 'rgba(42, 109, 181, 0.7)'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
    };
    xhr.send('action=get_stats');
})();
</script>

</body>
</html>
