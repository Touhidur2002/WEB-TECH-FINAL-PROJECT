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

$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$listing = getListingById($listing_id);

if (!$listing || $listing['seller_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit;
}

$bid_count = getBidCount($listing_id);
$read_only = $bid_count > 0;

$errors = [];
if (isset($_SESSION['edit_errors'])) {
    $errors = $_SESSION['edit_errors'];
    unset($_SESSION['edit_errors']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Listing - Online Auction System</title>
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

<div style="max-width:600px;">
    <h2>Edit Listing</h2>

    <?php if ($read_only): ?>
        <div class="notice">This listing has <?php echo $bid_count; ?> bid(s). Title and description are read-only. You can still update the image.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="../controllers/listing.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">

        <label>Title *</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" <?php echo $read_only ? 'readonly' : ''; ?>>

        <label>Description *</label>
        <textarea name="description" rows="4" <?php echo $read_only ? 'readonly' : ''; ?>><?php echo htmlspecialchars($listing['description']); ?></textarea>

        <label>Current Image</label><br>
        <?php if ($listing['image_path']): ?>
            <img class="preview" src="../public/uploads/listings/<?php echo htmlspecialchars($listing['image_path']); ?>" alt="Current Image">
        <?php else: ?>
            <p>No image uploaded.</p>
        <?php endif; ?>

        <label>Replace Image (optional, JPEG or PNG, max 3MB)</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png">

        <br>
        <input type="submit" value="Save Changes">
        <a href="dashboard.php" style="margin-left:15px;">Cancel</a>
    </form>
</div>

</body>
</html>
