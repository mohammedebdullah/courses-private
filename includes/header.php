<?php
/**
 * User Header
 */

$styleVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time();
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png"  href="assets/img/white.png?v=<?= time() ?>">
    <link rel="icon" type="image/png"  href="assets/img/white.png?v=<?= time() ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/black.png?v=<?= time() ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/black.png?v=<?= time() ?>">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/img/black.png?v=<?= time() ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/black.png?v=<?= time() ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:image" content="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST']; ?>/assets/img/black.png?v=<?= time() ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="twitter:image" content="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST']; ?>/assets/img/black.png?v=<?= time() ?>">
    
    <!-- Apple Mobile Web App -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($pageTitle) ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $styleVersion ?>">
    <!-- Use admin FontAwesome -->
    <link rel="stylesheet" href="admin/assets/plugins/fontawesome/css/all.min.css">
    <style>
        /* Anti-recording watermark */
        .watermark-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }
        .watermark-text {
            position: absolute;
            white-space: nowrap;
            transform: rotate(-45deg);
            opacity: 0.02;
            font-size: 14px;
            color: #000;
            user-select: none;
        }
    </style>
</head>
<body>
    <!-- Anti-recording watermark -->
    <div class="watermark-overlay" id="watermark"></div>
    
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <!-- Professional Logo -->
                <a href="courses.php" class="logo">
                    <img src="assets/img/black.png?v=<?= time() ?>" alt="کۆرسێ دەنگی" class="logo-image">
                    <!-- <div class="logo-text">
                        <span class="logo-title">کۆرسێ دەنگی</span>
                        <span class="logo-subtitle">پلاتفۆرما فێربوونێ</span>
                    </div> -->
                </a>
                
                <!-- Navigation Menu -->
                <!-- <nav class="nav">
                    <a href="courses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'courses.php' ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                        <span>کۆرسەکان</span>
                    </a>
                </nav> -->
                
                <!-- User Info Badge -->
                <div class="user-badge">
                    <div class="user-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'بەکارهێنەر') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main>
