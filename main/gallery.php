<?php
session_start();
include('db_connect.php');

// Fetch gallery images from database
$gallery_sql = "SELECT g.*, c.concertName 
                FROM gallery g 
                LEFT JOIN concert c ON g.concertID = c.concertID 
                WHERE g.is_active = 1 
                ORDER BY g.uploaded_at DESC";
$gallery_result = $conn->query($gallery_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMPAX EXPO — Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styling/styles.css">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: linear-gradient(135deg, #4A154B 0%, #2D1B69 50%, #1A0B2E 100%);
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .gallery-info {
            padding: 15px;
        }

        .gallery-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #ffffffff;
        }

        .gallery-info strong {
            color: #ffffffff;
        }

        .no-images {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
        }

        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s;
        }

        .lightbox-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            max-height: 80%;
            object-fit: contain;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        .close:hover {
            color: #bbb;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
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

    <!-- Gallery Section -->
    <section id="gallery" class="container-section">
        <h2 data-i18n="gallery.title" data-reveal>Gallery</h2>
        <p data-reveal>Explore moments from our past events and experiences</p>

        <div class="gallery-grid">
            <?php if ($gallery_result->num_rows > 0): ?>
                <?php while ($image = $gallery_result->fetch_assoc()): ?>
                    <div class="gallery-item" data-reveal>
                        <img src="../<?php echo htmlspecialchars($image['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($image['caption']); ?>" onclick="openLightbox(this)"
                            onerror="this.src='../visuals/default.jpg'">
                        <div class="gallery-info">
                            <?php if (!empty($image['concertName'])): ?>
                                <p><strong>Event:</strong> <?php echo htmlspecialchars($image['concertName']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($image['caption'])): ?>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($image['caption']); ?></p>
                            <?php endif; ?>
                            <p><strong>Date Uploaded:</strong> <?php echo date('M j, Y', strtotime($image['uploaded_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-images" data-reveal>
                    <p>No gallery images available yet.</p>
                    <p>Check back soon for updates!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lightbox Modal -->
        <div id="lightbox" class="lightbox">
            <span class="close" onclick="closeLightbox()">&times;</span>
            <img class="lightbox-content" id="lightbox-img">
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p data-i18n="footer">© GEMPAX EXPO — 2025</p>
        </div>
    </footer>

    <script>



        // Lightbox functionality
        function openLightbox(img) {
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            lightbox.style.display = 'block';
            lightboxImg.src = img.src;
            lightboxImg.alt = img.alt;
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }

        // Close lightbox when clicking outside the image
        document.getElementById('lightbox').addEventListener('click', function (e) {
            if (e.target !== document.getElementById('lightbox-img')) {
                closeLightbox();
            }
        });

        // Close lightbox with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });

        // Reveal animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('[data-reveal]').forEach((el) => {
            el.style.opacity = 0;
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>

</html>