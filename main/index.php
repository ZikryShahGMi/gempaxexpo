<?php
include('db_connect.php'); // Add this line to connect to database
session_start();

// Fetch latest gallery images from database (limit to 4 for the homepage)
$gallery_sql = "SELECT g.*, c.concertName 
                FROM gallery g 
                LEFT JOIN concert c ON g.concertID = c.concertID 
                WHERE g.is_active = 1 
                ORDER BY g.uploaded_at DESC 
                LIMIT 4";
$gallery_result = $conn->query($gallery_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMPAX EXPO — Official Website</title>
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
                    <li><a href="index.php#about" class="active">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Show Dashboard for all logged-in users -->
                        <li><a href="dashboard.php">Dashboard</a></li>

                        <!-- Show Admin links only for admin users -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['userType'] === 'admin'): ?>
                            <li><a href="adminmanagementpage.php">Admin Management Page</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
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


    <!-- Hero Section -->
    <section class="hero">
        <video autoplay muted loop class="hero-video">
            <source src="../visuals/video.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 data-reveal>GEMPAX EXPO <span class="muted">2025</span></h1>
            <p class="lead-reveal" data-reveal>A festival of music, light, and unforgettable experiences.</p>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="container-section">
        <h2 data-i18n="about.title" data-reveal>About GEMPAX EXPO</h2>
        <p data-reveal>GEMPAX EXPO is a global music festival experience, combining live performances, immersive
            visuals,
            and fan culture. Inspired by worldwide concerts like Miku Expo, GEMPAX EXPO brings Malaysia and
            the world into one unforgettable night.</p>
    </section>

    <!-- Events Section -->
    <section id="events" class="container-section">
        <h2 data-i18n="events.title" data-reveal>Upcoming Events</h2>
        <div class="event-grid">
            <article class="event-card" data-reveal>
                <h3>Kuala Lumpur</h3>
                <p class="venue">Bukit Jalil Stadium</p>
                <p class="date">Nov 15, 2025</p>
            </article>
            <article class="event-card" data-reveal>
                <h3>Tokyo</h3>
                <p class="venue">Tokyo Dome</p>
                <p class="date">Nov 22, 2025</p>
            </article>
            <article class="event-card" data-reveal>
                <h3>Singapore</h3>
                <p class="venue">Marina Bay Sands</p>
                <p class="date">Dec 29, 2025</p>
            </article>
        </div>
        <div class="center">
            <a href="events.php" class="btn">View All Events</a>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="container-section">
        <h2 data-i18n="gallery.title" data-reveal>Gallery</h2>
        <div class="gallery-grid">
            <?php if ($gallery_result->num_rows > 0): ?>
                <?php while ($image = $gallery_result->fetch_assoc()): ?>
                    <img src="../<?php echo htmlspecialchars($image['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($image['caption']); ?>" class="thumb" loading="lazy" data-reveal
                        onerror="this.src='../visuals/default.jpg'">
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Fallback to static images if no database images -->
                <img src="../visuals/gallery1.jpg" alt="Gallery Image 1" class="thumb" loading="lazy" data-reveal>
                <img src="../visuals/gallery2.jpg" alt="Gallery Image 2" class="thumb" loading="lazy" data-reveal>
                <img src="../visuals/gallery3.jpg" alt="Gallery Image 3" class="thumb" loading="lazy" data-reveal>
                <img src="../visuals/gallery4.jpg" alt="Gallery Image 4" class="thumb" loading="lazy" data-reveal>
            <?php endif; ?>
        </div>
        <div class="center">
            <a href="gallery.php" class="btn">View Full Gallery</a>
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