<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['seller_verified'] != 1) {
    header("Location: home.php");
    exit;
}

require_once '../models/listingModel.php';

$categories = getAllCategories();

$errors = [];
if (isset($_SESSION['listing_errors'])) {
    $errors = $_SESSION['listing_errors'];
    unset($_SESSION['listing_errors']);
}
$old = [];
if (isset($_SESSION['listing_old'])) {
    $old = $_SESSION['listing_old'];
    unset($_SESSION['listing_old']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Listing - Online Auction System</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
<div class="nav">
    <a href="home.php">Browse Auctions</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="createListing.php">Create Listing</a>
    <a href="myBids.php">My Bids</a>
    <a href="#" onclick="document.getElementById('logout-form').submit(); return false;">Logout</a>
</div>
<form id="logout-form" method="POST" action="../controllers/loginValidation.php" style="display:none;">
    <input type="hidden" name="action" value="logout">
</form>

<h2>Create New Auction Listing</h2>

<?php if (!empty($errors)): ?>
    <ul class="error">
        <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div style="max-width:600px;">
<form method="POST" action="../controllers/listing.php" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">

    <label>Title *</label>
    <input type="text" name="title" value="<?php echo isset($old['title']) ? htmlspecialchars($old['title']) : ''; ?>">

    <label>Description *</label>
    <textarea name="description" rows="4"><?php echo isset($old['description']) ? htmlspecialchars($old['description']) : ''; ?></textarea>

    <label>Category *</label>
    <select name="category_id">
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Starting Price ($) *</label>
    <input type="number" name="starting_price" step="0.01" min="0.01" value="<?php echo isset($old['starting_price']) ? $old['starting_price'] : ''; ?>">

    <label>Reserve Price ($) (optional)</label>
    <input type="number" name="reserve_price" step="0.01" min="0" value="<?php echo isset($old['reserve_price']) ? $old['reserve_price'] : ''; ?>">

    <label>Item Image * (JPEG or PNG, max 3MB)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png">

    <label>Auction End Date &amp; Time * (at least 1 hour from now)</label>
    <input type="datetime-local" name="end_datetime" value="<?php echo isset($old['end_datetime']) ? $old['end_datetime'] : ''; ?>">

    <br>
    <input type="submit" value="Create Listing">
    <a href="dashboard.php" style="margin-left:15px;">Cancel</a>
</form>
</div>

</body>
</html>
