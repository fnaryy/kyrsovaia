<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="../accets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="reviews-section">
        <div class="reviews-header">
            <h2 class="reviews-title">Reviews</h2>
        </div>
        <div class="reviews-container">
            <?php
            if (isset($reviewer_avatar) && $reviewer_avatar) {
                echo '<img src="' . htmlspecialchars($reviewer_avatar) . '" alt="Reviewer Avatar" class="reviewer-avatar">';
            } else {
                echo '<img src="../accets/images/default-avatar.png" alt="Default Avatar" class="reviewer-avatar">';
            }
            if (isset($reviewer_name)) {
                echo '<span class="reviewer-name">' . htmlspecialchars($reviewer_name) . '</span>';
            }
            ?>
        </div>
    </div>
</body>
</html> 