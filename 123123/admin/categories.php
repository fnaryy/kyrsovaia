<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="../accets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="categories-section">
        <div class="categories-header">
            <h2 class="categories-title">Categories</h2>
        </div>
        <div class="categories-container">
            <?php
            if (isset($category_image) && $category_image) {
                echo '<img src="' . htmlspecialchars($category_image) . '" alt="Category Image" class="category-image">';
            } else {
                echo '<img src="../accets/images/default-category.png" alt="Default Category Image" class="category-image">';
            }
            ?>
        </div>
    </div>
</body>
</html> 