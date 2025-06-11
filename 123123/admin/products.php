<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="../accets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="products-section">
        <div class="products-header">
            <h2 class="products-title">Products</h2>
        </div>
        <div class="products-container">
            <?php
            if (isset($product_image) && $product_image) {
                echo '<img src="' . htmlspecialchars($product_image) . '" alt="Product Image" class="product-image">';
            } else {
                echo '<img src="../accets/images/default-product.png" alt="Default Product Image" class="product-image">';
            }
            if (isset($seller_avatar) && $seller_avatar) {
                echo '<img src="' . htmlspecialchars($seller_avatar) . '" alt="Seller Avatar" class="user-avatar">';
            } else {
                echo '<img src="../accets/images/default-avatar.png" alt="Default Avatar" class="user-avatar">';
            }
            ?>
        </div>
    </div>
</body>
</html> 