<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../models/listingModel.php';
require_once '../models/userModel.php';

$categories = getAllCategories();
$listings   = getActiveListings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Auctions - Online Auction System</title>
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

<h2>Browse Active Auctions</h2>

<form id="search-form" class="filter-form">
    <select id="category_id" name="category_id">
        <option value="">-- All Categories --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="text" id="keyword" name="keyword" placeholder="Search keyword..." value="">
    <button type="button" onclick="doSearch()" style="margin-top:0;">Filter</button>
    <button type="button" onclick="clearSearch()">Clear</button>
</form>

<div id="listings-container">
<?php if (empty($listings)): ?>
    <p>No active auctions found.</p>
<?php else: ?>
<table>
    <tr>
        <th>Image</th>
        <th>Title</th>
        <th>Category</th>
        <th>Current Bid</th>
        <th>End Time</th>
        <th>Action</th>
    </tr>
    <?php foreach ($listings as $listing): ?>
    <tr>
        <td>
            <?php if ($listing['image_path']): ?>
                <img class="thumb" src="../public/uploads/listings/<?php echo htmlspecialchars($listing['image_path']); ?>" alt="img">
            <?php else: ?>
                <span class="no-img">No Image</span>
            <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($listing['title']); ?></td>
        <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
        <td>$<?php echo number_format($listing['current_bid'], 2); ?></td>
        <td><span class="countdown" data-end="<?php echo $listing['end_datetime']; ?>">Loading...</span></td>
        <td><a class="btn" href="listingDetails.php?id=<?php echo $listing['id']; ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<script>

document.getElementById('search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    doSearch();
});

function clearSearch() {
    document.getElementById('keyword').value = '';
    document.getElementById('category_id').value = '';
    doSearch();
}

function doSearch() {
    var keyword    = document.getElementById('keyword').value;
    var cat_id     = document.getElementById('category_id').value;
    var container  = document.getElementById('listings-container');

    container.innerHTML = '<p>Searching...</p>';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controllers/listing.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (!data.ok) {
                    container.innerHTML = '<p>Error: ' + data.msg + '</p>';
                    return;
                }
                renderListings(data.listings);
            } catch (e) {
                container.innerHTML = '<p>Search error. Please try again.</p>';
            }
        }
    };
    xhr.send('action=search&keyword=' + encodeURIComponent(keyword) + '&category_id=' + encodeURIComponent(cat_id));
}

function renderListings(listings) {
    var container = document.getElementById('listings-container');

    if (listings.length === 0) {
        container.innerHTML = '<p>No active auctions found.</p>';
        return;
    }

    var html = '<table>';
    html += '<tr>';
    html += '<th>Image</th><th>Title</th><th>Category</th>';
    html += '<th>Current Bid</th><th>End Time</th><th>Action</th>';
    html += '</tr>';

    for (var i = 0; i < listings.length; i++) {
        var l = listings[i];
        var imgHtml = l.image_path
            ? '<img class="thumb" src="../public/uploads/listings/' + escHtml(l.image_path) + '" alt="img">'
            : '<span class="no-img">No Image</span>';

        html += '<tr>';
        html += '<td>' + imgHtml + '</td>';
        html += '<td>' + escHtml(l.title) + '</td>';
        html += '<td>' + escHtml(l.category_name) + '</td>';
        html += '<td>$' + parseFloat(l.current_bid).toFixed(2) + '</td>';
        html += '<td><span class="countdown" data-end="' + escHtml(l.end_datetime) + '">Loading...</span></td>';
        html += '<td><a class="btn" href="listingDetails.php?id=' + l.id + '">View</a></td>';
        html += '</tr>';
    }
    html += '</table>';
    container.innerHTML = html;

    
    updateCountdowns();
}

function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

function updateCountdowns() {
    var spans = document.querySelectorAll('.countdown');
    var now   = Math.floor(Date.now() / 1000);
    spans.forEach(function(span) {
        var end  = Math.floor(new Date(span.getAttribute('data-end')).getTime() / 1000);
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
</script>

</body>
</html>
