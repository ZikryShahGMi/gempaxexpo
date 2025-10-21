<?php
session_start();
include('db_connect.php')
    ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMPAX EXPO — Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styling/styles.css">
</head>

<body>
    <!-- Header Section -->
    <header class="site-header">
        <div class="container header">
            <a class="logo" href="index.php">GEMPAX EXPO</a>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="gallery.php" class="active">Gallery</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- If user is logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">
                            <?= htmlspecialchars($_SESSION['fullname']) ?> ▾
                        </button>
                        <div class="dropdown-content">
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- If user is NOT logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">Account ▾</button>
                        <div class="dropdown-content">
                            <a href="signin.php">Sign In</a>
                            <a href="signup.php">Sign Up</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="language-switcher">
                <button class="lang-btn">EN</button>
            </div>
        </div>
    </header>

    <!-- Gallery Section -->
    <section id="gallery" class="container-section">
        <h2 data-i18n="gallery.title" data-reveal>Gallery</h2>
        <div class="gallery-grid">
            <img class="thumb" src="../visuals/gallery1.jpg" alt="Gallery Image 1" loading="lazy" data-reveal>
            <img class="thumb" src="../visuals/gallery2.jpg" alt="Gallery Image 2" loading="lazy" data-reveal>
            <img class="thumb" src="../visuals/gallery3.jpg" alt="Gallery Image 3" loading="lazy" data-reveal>
            <img class="thumb" src="../visuals/gallery4.jpg" alt="Gallery Image 4" loading="lazy" data-reveal>
        </div>

        <!-- Lightbox Modal -->
        <div id="lightbox" class="lightbox">
            <span class="close">&times;</span>
            <img class="lightbox-content" id="lightbox-img">
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p data-i18n="footer">© GEMPAX EXPO — 2025</p>
        </div>
    </footer>

    <div class="modal" id="gallery-modal" aria-hidden="true"></div>
    <script src="script.js"></script>
</body>

</html>