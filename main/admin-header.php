<?php
// admin-header.php - Reusable admin header
?>
<!-- Header Section -->
<header class="site-header">
	<div class="container nav-container">
		<h1 class="logo"><a href="index.php">GEMPAX EXPO</a></h1>

		<nav class="main-nav">
			<ul>
				<li><a href="../main/index.php#about">About</a></li>
				<li><a href="../main/events.php">Events</a></li>
				<li><a href="../main/gallery.php">Gallery</a></li>
				<li><a href="../main/booking.php">Booking</a></li>
				<li><a href="../main/contact.php">Contact</a></li>

				<?php if (isset($_SESSION['user_id'])): ?>
					<!-- Show Dashboard for all logged-in users -->
					<li><a href="../main/dashboard.php">Dashboard</a></li>

					<!-- Show Admin links only for admin users -->
					<?php if (isset($_SESSION['user_id']) && $_SESSION['userType'] === 'admin'): ?>
						<li><a href="../main/adminmanagementpage.php" class="active">Admin Management Page</a></li>
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
	</div>
</header>