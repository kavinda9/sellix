<?php
// index.php - Smart Marketplace Homepage

require_once 'config/db.php';
require_once 'config/constants.php';
require_once 'includes/auth.php';
require_once 'utils/functions.php';

// Fetch featured products
$featured_products = [];
$stmt = $pdo->query("SELECT p.*, u.username, u.profile_pic 
                     FROM products p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.status = 'active' 
                     ORDER BY p.created_at DESC LIMIT 8");
$featured_products = $stmt->fetchAll();

// Fetch categories (you can adjust this query later)
$categories = [];
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name LIMIT 6");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Buy & Sell Smartly</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="icon" href="assets/images/logo.png" type="image/png">
</head>
<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Buy & Sell with Confidence</h1>
            <p>Discover amazing products from trusted sellers. Our smart scam detection keeps you safe.</p>
            
            <div class="search-bar">
                <form action="product/search.php" method="GET">
                    <input type="text" name="q" placeholder="Search products..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="hero-buttons">
                <a href="product/category.php" class="btn btn-primary">Browse Categories</a>
                <a href="user/add_product.php" class="btn btn-secondary">Sell Your Product</a>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="assets/images/hero-bg.jpg" alt="Smart Marketplace">
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2>Browse by Category</h2>
            <div class="category-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="product/category.php?id=<?= $cat['id'] ?>" class="category-card">
                        <img src="assets/images/categories/<?= $cat['image'] ?? 'default.jpg' ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                        <h3><?= htmlspecialchars($cat['name']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="product/search.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="products-grid">
                <?php if (empty($featured_products)): ?>
                    <p>No products available yet.</p>
                <?php else: ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['title']) ?>">
                                <?php else: ?>
                                    <img src="assets/images/no-image.jpg" alt="No image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['title']) ?></h3>
                                <p class="seller">by <?= htmlspecialchars($product['username']) ?></p>
                                
                                <div class="price">
                                    ₹<?= number_format($product['price'], 2) ?>
                                </div>
                                
                                <a href="product/view.php?id=<?= $product['id'] ?>" class="btn btn-sm">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Trust Features -->
    <section class="trust-section">
        <div class="container">
            <h2>Why Choose ?</h2>
            <div class="trust-grid">
                <div class="trust-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Smart Scam Detection</h3>
                    <p>Our AI-powered system helps protect you from scams.</p>
                </div>
                <div class="trust-card">
                    <i class="fas fa-comments"></i>
                    <h3>Secure Chat</h3>
                    <p>Communicate safely with buyers and sellers.</p>
                </div>
                <div class="trust-card">
                    <i class="fas fa-star"></i>
                    <h3>Trusted Community</h3>
                    <p>Verified users and transparent ratings.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>