<?php
// Sellix/index.php

session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── Fetch banners ──────────────────────────────────────────
$banners = [];
try {
    $stmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 5");
    $banners = $stmt->fetchAll();
} catch (Exception $e) {}

// ── Fetch parent categories ────────────────────────────────
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC LIMIT 8");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {}

// ── Featured products ──────────────────────────────────────
$featured = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND p.is_featured = 1
        ORDER BY p.created_at DESC LIMIT 8
    ");
    $featured = $stmt->fetchAll();
} catch (Exception $e) {}

// ── Newest products ────────────────────────────────────────
$newest = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC LIMIT 10
    ");
    $newest = $stmt->fetchAll();
} catch (Exception $e) {}

// ── Site settings ──────────────────────────────────────────
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$site_name = $settings['site_name']       ?? 'Sellix';
$currency  = $settings['currency_symbol'] ?? 'Rs.';

// ── Cart count ─────────────────────────────────────────────
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
} elseif (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

$logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';

// ── Helpers ────────────────────────────────────────────────
function fmt($price, $cur) {
    return $cur . ' ' . number_format((float)$price, 2);
}
function discount($orig, $sale) {
    if (!$sale || $sale >= $orig) return 0;
    return round((($orig - $sale) / $orig) * 100);
}

// ── Category icons map ─────────────────────────────────────
$cat_icons = [
    'electronics'   => '&#x26A1;',
    'clothing'      => '&#x1F457;',
    'home-garden'   => '&#x1FAB4;',
    'sports'        => '&#x26BD;',
    'books'         => '&#x1F4DA;',
    'beauty'        => '&#x2728;',
    'toys'          => '&#x1F3AE;',
    'automotive'    => '&#x1F697;',
    'mobile-phones' => '&#x1F4F1;',
    'laptops'       => '&#x1F4BB;',
    'men-clothing'  => '&#x1F454;',
    'women-clothing'=> '&#x1F452;',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($site_name) ?> &mdash; Find Everything You Love</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=Pacifico&display=swap" rel="stylesheet"/>
<style>
:root {
    --bg:      #0e0e12;
    --bg2:     #16161d;
    --bg3:     #1e1e28;
    --card:    #1a1a24;
    --border:  #2a2a38;
    --accent:  #ff6b9d;
    --accent2: #c44dff;
    --accent3: #ffb347;
    --text:    #f0f0f5;
    --muted:   #8888aa;
    --r:       14px;
    --rs:      8px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
a{color:inherit;text-decoration:none;}

/* TOPBAR */
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:0 2rem;height:64px;display:flex;align-items:center;gap:1.2rem;position:sticky;top:0;z-index:100;}
.logo{font-family:'Pacifico',cursive;font-size:1.7rem;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.search-wrap{flex:1;max-width:520px;position:relative;}
.search-wrap input{width:100%;background:var(--bg3);border:1.5px solid var(--border);color:var(--text);border-radius:50px;padding:.55rem 3.5rem .55rem 1.2rem;font-family:'Nunito',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;}
.search-wrap input:focus{border-color:var(--accent);}
.search-wrap input::placeholder{color:var(--muted);}
.search-wrap button{position:absolute;right:4px;top:50%;transform:translateY(-50%);background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:50px;color:#fff;padding:.38rem 1rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;transition:opacity .2s;}
.search-wrap button:hover{opacity:.85;}
.nav-actions{display:flex;align-items:center;gap:.7rem;margin-left:auto;}
.nbtn{background:none;border:1.5px solid var(--border);color:var(--text);border-radius:50px;padding:.4rem 1.1rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;display:inline-flex;align-items:center;gap:6px;transition:border-color .2s,background .2s;}
.nbtn:hover{border-color:var(--accent);background:rgba(255,107,157,.07);}
.nbtn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#fff;}
.nbtn.primary:hover{opacity:.88;}
.cart-btn{position:relative;background:var(--bg3);border:1.5px solid var(--border);color:var(--text);border-radius:50px;padding:.4rem 1.1rem .4rem .9rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;display:inline-flex;align-items:center;gap:6px;transition:border-color .2s;}
.cart-btn:hover{border-color:var(--accent);}
.cbadge{background:var(--accent);color:#fff;border-radius:50px;font-size:.7rem;font-weight:800;padding:1px 7px;min-width:20px;text-align:center;}
.user-chip{display:inline-flex;align-items:center;gap:6px;background:var(--bg3);border:1.5px solid var(--border);border-radius:50px;padding:.35rem 1rem;font-size:.85rem;color:var(--muted);}
.user-chip b{color:var(--text);}

/* CAT NAV */
.cat-nav{background:var(--bg2);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;gap:.3rem;overflow-x:auto;scrollbar-width:none;}
.cat-nav::-webkit-scrollbar{display:none;}
.cat-nav a{display:inline-flex;align-items:center;gap:5px;padding:.65rem 1rem;font-size:.82rem;font-weight:700;color:var(--muted);white-space:nowrap;border-bottom:2px solid transparent;transition:color .2s,border-color .2s;}
.cat-nav a:hover{color:var(--accent);border-bottom-color:var(--accent);}

/* HERO */
.hero-wrap{margin:1.5rem 2rem;}
.hero{border-radius:var(--r);overflow:hidden;min-height:300px;background:linear-gradient(135deg,#1a0a2e 0%,#16213e 45%,#0f3460 100%);display:flex;align-items:center;position:relative;}
.hero-slide{display:none;padding:3rem;width:100%;animation:fadeUp .5s ease;}
.hero-slide.active{display:flex;align-items:center;justify-content:space-between;}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.hero-tag{display:inline-block;background:rgba(255,107,157,.18);border:1px solid var(--accent);color:var(--accent);border-radius:50px;padding:4px 14px;font-size:.78rem;font-weight:800;letter-spacing:.05em;margin-bottom:1rem;text-transform:uppercase;}
.hero-h{font-size:clamp(1.7rem,3.5vw,2.7rem);font-weight:900;line-height:1.15;margin-bottom:.7rem;}
.hero-h span{background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero-sub{color:var(--muted);font-size:.95rem;margin-bottom:1.5rem;line-height:1.6;}
.hero-cta{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:50px;padding:.7rem 2rem;font-size:1rem;font-weight:800;cursor:pointer;font-family:'Nunito',sans-serif;transition:opacity .2s,transform .2s;}
.hero-cta:hover{opacity:.88;transform:translateY(-2px);}
.hero-emoji{font-size:clamp(4rem,8vw,7rem);user-select:none;flex-shrink:0;}
.hero-dots{position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);display:flex;gap:6px;}
.hdot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.25);cursor:pointer;border:none;transition:background .2s,width .2s;}
.hdot.active{background:var(--accent);width:24px;border-radius:50px;}

/* SECTION */
.section{padding:0 2rem 2rem;}
.sec-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;padding-top:.5rem;}
.sec-title{font-size:1.2rem;font-weight:900;display:flex;align-items:center;gap:8px;}
.sec-title .dot{width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));flex-shrink:0;}
.see-all{font-size:.82rem;font-weight:700;color:var(--accent);border:1.5px solid rgba(255,107,157,.3);border-radius:50px;padding:4px 14px;transition:background .2s;}
.see-all:hover{background:rgba(255,107,157,.1);}

/* PROMO STRIP */
.promo-strip{margin:0 2rem 2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;}
.promo-card{border-radius:var(--r);padding:1.3rem 1.4rem;display:flex;align-items:center;gap:1rem;transition:transform .2s;}
.promo-card:hover{transform:translateY(-3px);}
.promo-card.pink{background:linear-gradient(135deg,#2d0a1e,#4a1030);border:1px solid #5a1a40;}
.promo-card.purple{background:linear-gradient(135deg,#1a0a2e,#2d1060);border:1px solid #3d1a70;}
.promo-card.amber{background:linear-gradient(135deg,#2e1800,#4a2e00);border:1px solid #6a4500;}
.promo-icon{font-size:2.2rem;flex-shrink:0;}
.promo-title{font-weight:900;font-size:1rem;}
.promo-sub{font-size:.78rem;color:var(--muted);margin-top:2px;}

/* CATEGORY GRID */
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:.9rem;}
.cat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:1.2rem .5rem .9rem;text-align:center;display:flex;flex-direction:column;align-items:center;gap:8px;transition:border-color .2s,transform .2s,background .2s;}
.cat-card:hover{border-color:var(--accent);background:rgba(255,107,157,.05);transform:translateY(-3px);}
.cat-icon{font-size:1.8rem;line-height:1;width:50px;height:50px;background:var(--bg3);border-radius:50%;display:flex;align-items:center;justify-content:center;}
.cat-label{font-size:.75rem;font-weight:700;color:var(--muted);}

/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:1rem;}
.pcard{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;display:flex;flex-direction:column;transition:border-color .25s,transform .25s,box-shadow .25s;position:relative;}
.pcard:hover{border-color:var(--accent);transform:translateY(-4px);box-shadow:0 12px 40px rgba(255,107,157,.12);}
.pimg{width:100%;aspect-ratio:1/1;background:var(--bg3);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.pimg img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.pcard:hover .pimg img{transform:scale(1.06);}
.pplaceholder{font-size:3rem;opacity:.3;}
.badge-sale{position:absolute;top:10px;left:10px;background:var(--accent);color:#fff;border-radius:50px;font-size:.72rem;font-weight:800;padding:3px 10px;}
.badge-new{position:absolute;top:10px;left:10px;background:var(--accent2);color:#fff;border-radius:50px;font-size:.72rem;font-weight:800;padding:3px 10px;}
.badge-feat{position:absolute;top:10px;right:42px;background:var(--accent3);color:#1a1a24;border-radius:50px;font-size:.7rem;font-weight:800;padding:3px 10px;}
.wbtn{position:absolute;top:8px;right:8px;background:rgba(14,14,18,.75);border:1px solid var(--border);color:var(--muted);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;z-index:2;transition:color .2s,border-color .2s;}
.wbtn:hover{color:var(--accent);border-color:var(--accent);}
.pinfo{padding:.85rem;flex:1;display:flex;flex-direction:column;gap:4px;}
.pcat{font-size:.7rem;color:var(--accent2);font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.pname{font-size:.87rem;font-weight:700;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.pprices{display:flex;align-items:center;gap:8px;margin-top:auto;padding-top:8px;flex-wrap:wrap;}
.price-sale{font-size:1rem;font-weight:900;color:var(--accent);}
.price-orig{font-size:.78rem;color:var(--muted);text-decoration:line-through;}
.price-only{font-size:1rem;font-weight:900;color:var(--text);}
.acbtn{margin:0 .85rem .85rem;background:var(--bg3);border:1.5px solid var(--border);color:var(--text);border-radius:50px;padding:.45rem;font-size:.82rem;font-weight:800;cursor:pointer;font-family:'Nunito',sans-serif;transition:background .2s,border-color .2s,color .2s;text-align:center;display:block;}
.acbtn:hover{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#fff;}

/* HORIZONTAL SCROLL */
.hscroll{display:flex;gap:1rem;overflow-x:auto;padding-bottom:8px;scrollbar-width:thin;scrollbar-color:var(--border) transparent;}
.hscroll::-webkit-scrollbar{height:4px;}
.hscroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:50px;}
.hscroll .pcard{min-width:185px;max-width:185px;flex-shrink:0;}

/* FOOTER */
footer{background:var(--bg2);border-top:1px solid var(--border);padding:2.5rem 2rem 1.5rem;margin-top:2rem;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:2rem;margin-bottom:2rem;}
.flogo{font-family:'Pacifico',cursive;font-size:1.5rem;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:inline-block;margin-bottom:.8rem;}
.fbrand p{font-size:.85rem;color:var(--muted);line-height:1.6;max-width:240px;}
.fcol h4{font-size:.88rem;font-weight:800;margin-bottom:.8rem;}
.fcol a{display:block;font-size:.82rem;color:var(--muted);margin-bottom:6px;transition:color .2s;}
.fcol a:hover{color:var(--accent);}
.fbot{border-top:1px solid var(--border);padding-top:1rem;display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:var(--muted);flex-wrap:wrap;gap:.5rem;}
.fbot .acc{color:var(--accent);}

/* RESPONSIVE */
@media(max-width:768px){
    .topbar{padding:0 1rem;gap:.7rem;}
    .section,.hero-wrap,.promo-strip{margin-left:1rem;margin-right:1rem;padding-left:0;padding-right:0;}
    .section{padding:0 1rem 1.5rem;}
    .hero-emoji{display:none;}
    .hero-slide{padding:1.5rem;}
    .footer-grid{grid-template-columns:1fr 1fr;}
    .cat-nav{padding:0 1rem;}
}
@media(max-width:480px){
    .product-grid{grid-template-columns:repeat(2,1fr);}
    .cat-grid{grid-template-columns:repeat(4,1fr);}
    .footer-grid{grid-template-columns:1fr;}
    .nav-actions .nbtn:not(.cart-btn){display:none;}
}
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <a href="index.php" class="logo"><?= htmlspecialchars($site_name) ?></a>

    <form class="search-wrap" action="pages/shop.php" method="GET">
        <input type="text" name="q" placeholder="Search for products, brands and more&hellip;" autocomplete="off"/>
        <button type="submit">Search</button>
    </form>

    <nav class="nav-actions">
        <a href="pages/cart.php" class="cart-btn nbtn">
            &#x1F6D2; Cart
            <?php if ($cart_count > 0): ?>
                <span class="cbadge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <?php if ($logged_in): ?>
            <a href="pages/orders.php" class="nbtn">&#x1F4E6; Orders</a>
            <a href="pages/profile.php" class="user-chip">&#x1F464; <b><?= htmlspecialchars($user_name) ?></b></a>
            <a href="auth/logout.php" class="nbtn">Logout</a>
        <?php else: ?>
            <a href="auth/login.php"    class="nbtn">Sign In</a>
            <a href="auth/register.php" class="nbtn primary">Join Free</a>
        <?php endif; ?>
    </nav>
</header>

<!-- CATEGORY NAV -->
<nav class="cat-nav">
    <a href="pages/shop.php">&#x1F3E0; All</a>
    <?php foreach ($categories as $cat):
        $icon = $cat_icons[$cat['slug']] ?? '&#x1F6CD;';
    ?>
        <a href="pages/shop.php?category=<?= urlencode($cat['slug']) ?>">
            <?= $icon ?> <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- HERO BANNER -->
<?php
if (empty($banners)) {
    $banners = [
        ['title'=>'Find Everything You Love','subtitle'=>'Thousands of products at the best prices','link_url'=>'pages/shop.php','image'=>''],
        ['title'=>'New Arrivals Every Day','subtitle'=>'Stay on trend with the freshest picks','link_url'=>'pages/shop.php?sort=new','image'=>''],
    ];
}
$slide_emojis = ['&#x1F6CD;','&#x2728;','&#x1F381;','&#x26A1;','&#x1F4AB;'];
?>
<div class="hero-wrap">
    <div class="hero">
        <div id="heroSlides" style="width:100%;">
            <?php foreach ($banners as $i => $b): ?>
            <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
                <div class="hero-text">
                    <div class="hero-tag">&#x2728; Featured Deal</div>
                    <h1 class="hero-h">
                        <?= htmlspecialchars($b['title']) ?><br/>
                        <span><?= htmlspecialchars($b['subtitle'] ?? '') ?></span>
                    </h1>
                    <p class="hero-sub">Curated products from top sellers &mdash; delivered fast, priced right.</p>
                    <a href="<?= htmlspecialchars($b['link_url'] ?? 'pages/shop.php') ?>" class="hero-cta">Shop Now &rarr;</a>
                </div>
                <div class="hero-emoji"><?= $slide_emojis[$i % count($slide_emojis)] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-dots" id="heroDots">
            <?php foreach ($banners as $i => $_): ?>
                <button class="hdot <?= $i === 0 ? 'active' : '' ?>" onclick="goSlide(<?= $i ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- PROMO STRIP -->
<div class="promo-strip">
    <a href="pages/shop.php?sort=sale" class="promo-card pink">
        <div class="promo-icon">&#x1F525;</div>
        <div><div class="promo-title">Hot Deals</div><div class="promo-sub">Up to 50% off today</div></div>
    </a>
    <a href="pages/shop.php?sort=new" class="promo-card purple">
        <div class="promo-icon">&#x1F195;</div>
        <div><div class="promo-title">New Arrivals</div><div class="promo-sub">Fresh drops every week</div></div>
    </a>
    <a href="pages/shop.php?featured=1" class="promo-card amber">
        <div class="promo-icon">&#x2B50;</div>
        <div><div class="promo-title">Top Rated</div><div class="promo-sub">Loved by customers</div></div>
    </a>
</div>

<!-- CATEGORIES -->
<?php if (!empty($categories)): ?>
<section class="section">
    <div class="sec-head">
        <div class="sec-title"><span class="dot"></span> Shop by Category</div>
        <a href="pages/shop.php" class="see-all">See All &rarr;</a>
    </div>
    <div class="cat-grid">
        <?php foreach ($categories as $cat):
            $icon = $cat_icons[$cat['slug']] ?? '&#x1F6CD;';
        ?>
        <a href="pages/shop.php?category=<?= urlencode($cat['slug']) ?>" class="cat-card">
            <div class="cat-icon"><?= $icon ?></div>
            <div class="cat-label"><?= htmlspecialchars($cat['name']) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FEATURED PRODUCTS -->
<?php if (!empty($featured)): ?>
<section class="section">
    <div class="sec-head">
        <div class="sec-title"><span class="dot"></span> &#x2B50; Featured Picks</div>
        <a href="pages/shop.php?featured=1" class="see-all">See All &rarr;</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $p):
            $has_sale = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
            $pct      = $has_sale ? discount($p['price'], $p['sale_price']) : 0;
            $img      = !empty($p['image']) ? 'assets/images/' . htmlspecialchars($p['image']) : '';
        ?>
        <a href="pages/product.php?slug=<?= urlencode($p['slug']) ?>" class="pcard">
            <div class="pimg">
                <?php if ($img): ?>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy"/>
                <?php else: ?>
                    <span class="pplaceholder">&#x1F6CD;</span>
                <?php endif; ?>
                <?php if ($has_sale): ?><span class="badge-sale">-<?= $pct ?>%</span><?php endif; ?>
                <span class="badge-feat">&#x2B50; Pick</span>
                <a href="pages/wishlist.php?add=<?= (int)$p['id'] ?>" class="wbtn" title="Wishlist" onclick="event.stopPropagation()">&#x2665;</a>
            </div>
            <div class="pinfo">
                <div class="pcat"><?= htmlspecialchars($p['category_name'] ?? '') ?></div>
                <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
                <div class="pprices">
                    <?php if ($has_sale): ?>
                        <span class="price-sale"><?= fmt($p['sale_price'], $currency) ?></span>
                        <span class="price-orig"><?= fmt($p['price'], $currency) ?></span>
                    <?php else: ?>
                        <span class="price-only"><?= fmt($p['price'], $currency) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="pages/cart.php?add=<?= (int)$p['id'] ?>" class="acbtn" onclick="handleCart(event, this)">
                &#x1F6D2; Add to Cart
            </a>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- NEW ARRIVALS -->
<?php if (!empty($newest)): ?>
<section class="section">
    <div class="sec-head">
        <div class="sec-title"><span class="dot"></span> &#x1F195; New Arrivals</div>
        <a href="pages/shop.php?sort=new" class="see-all">See All &rarr;</a>
    </div>
    <div class="hscroll">
        <?php foreach ($newest as $p):
            $has_sale = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
            $pct      = $has_sale ? discount($p['price'], $p['sale_price']) : 0;
            $img      = !empty($p['image']) ? 'assets/images/' . htmlspecialchars($p['image']) : '';
        ?>
        <a href="pages/product.php?slug=<?= urlencode($p['slug']) ?>" class="pcard">
            <div class="pimg">
                <?php if ($img): ?>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy"/>
                <?php else: ?>
                    <span class="pplaceholder">&#x1F6CD;</span>
                <?php endif; ?>
                <?php if ($has_sale): ?>
                    <span class="badge-sale">-<?= $pct ?>%</span>
                <?php else: ?>
                    <span class="badge-new">New</span>
                <?php endif; ?>
                <a href="pages/wishlist.php?add=<?= (int)$p['id'] ?>" class="wbtn" title="Wishlist" onclick="event.stopPropagation()">&#x2665;</a>
            </div>
            <div class="pinfo">
                <div class="pcat"><?= htmlspecialchars($p['category_name'] ?? '') ?></div>
                <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
                <div class="pprices">
                    <?php if ($has_sale): ?>
                        <span class="price-sale"><?= fmt($p['sale_price'], $currency) ?></span>
                        <span class="price-orig"><?= fmt($p['price'], $currency) ?></span>
                    <?php else: ?>
                        <span class="price-only"><?= fmt($p['price'], $currency) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="pages/cart.php?add=<?= (int)$p['id'] ?>" class="acbtn" onclick="handleCart(event, this)">
                &#x1F6D2; Add to Cart
            </a>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="fbrand">
            <div class="flogo"><?= htmlspecialchars($site_name) ?></div>
            <p>Your one-stop marketplace for everything you love. Fast delivery, great prices, curated just for you.</p>
        </div>
        <div class="fcol">
            <h4>Shop</h4>
            <a href="pages/shop.php">All Products</a>
            <a href="pages/shop.php?featured=1">Featured</a>
            <a href="pages/shop.php?sort=sale">On Sale</a>
            <a href="pages/shop.php?sort=new">New Arrivals</a>
        </div>
        <div class="fcol">
            <h4>Account</h4>
            <a href="auth/login.php">Sign In</a>
            <a href="auth/register.php">Register</a>
            <a href="pages/orders.php">My Orders</a>
            <a href="pages/profile.php">My Profile</a>
        </div>
        <div class="fcol">
            <h4>Help</h4>
            <a href="#">Contact Us</a>
            <a href="#">FAQ</a>
            <a href="#">Returns Policy</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>
    <div class="fbot">
        <span>&copy; <?= date('Y') ?> <span class="acc"><?= htmlspecialchars($site_name) ?></span>. All rights reserved.</span>
        <span>Made with <span class="acc">&#x2665;</span> in Sri Lanka</span>
    </div>
</footer>

<script>
// Hero slideshow
const slides = document.querySelectorAll('.hero-slide');
const dots   = document.querySelectorAll('.hdot');
let cur = 0, timer;

function goSlide(n) {
    slides[cur].classList.remove('active');
    dots[cur].classList.remove('active');
    cur = (n + slides.length) % slides.length;
    slides[cur].classList.add('active');
    dots[cur].classList.add('active');
    clearInterval(timer);
    if (slides.length > 1) timer = setInterval(() => goSlide(cur + 1), 4500);
}
if (slides.length > 1) timer = setInterval(() => goSlide(cur + 1), 4500);

// Cart / Wishlist — redirect to login if guest
const loggedIn = <?= $logged_in ? 'true' : 'false' ?>;

function handleCart(e, el) {
    e.stopPropagation();
    if (!loggedIn) {
        e.preventDefault();
        window.location.href = 'auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
    }
}

document.querySelectorAll('.wbtn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!loggedIn) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'auth/login.php?redirect=' + encodeURIComponent(window.location.pathname);
        }
    });
});
</script>
</body>
</html>