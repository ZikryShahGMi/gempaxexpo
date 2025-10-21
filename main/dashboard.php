<?php
// ADD THESE LINES AT THE VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
	header("Location: signin.php");
	exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch user information
$user_sql = "SELECT userFullName, userEmail, userPhoneNumber FROM Users WHERE userID = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle form submission for updating user info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
	$fullname = trim($_POST['fullname']);
	$email = trim($_POST['email']);
	$phone = trim($_POST['phone']);

	// Basic validation
	if (!empty($fullname) && !empty($email) && !empty($phone)) {
		// Check if email is already taken by another user
		$check_email_sql = "SELECT userID FROM Users WHERE userEmail = ? AND userID != ?";
		$check_stmt = $conn->prepare($check_email_sql);
		$check_stmt->bind_param("si", $email, $user_id);
		$check_stmt->execute();
		$email_result = $check_stmt->get_result();

		if ($email_result->num_rows > 0) {
			$message = "Email is already taken by another user.";
		} else {
			// Update user information
			$update_sql = "UPDATE Users SET userFullName = ?, userEmail = ?, userPhoneNumber = ? WHERE userID = ?";
			$update_stmt = $conn->prepare($update_sql);
			$update_stmt->bind_param("sssi", $fullname, $email, $phone, $user_id);

			if ($update_stmt->execute()) {
				$message = "Profile updated successfully!";
				// Refresh user data
				$user['userFullName'] = $fullname;
				$user['userEmail'] = $email;
				$user['userPhoneNumber'] = $phone;
			} else {
				$message = "Error updating profile. Please try again.";
			}
		}
	} else {
		$message = "Please fill in all required fields.";
	}
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
	$current_password = $_POST['current_password'];
	$new_password = $_POST['new_password'];
	$confirm_password = $_POST['confirm_password'];

	// Validate inputs
	if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
		$message = "Please fill in all password fields.";
	} elseif ($new_password !== $confirm_password) {
		$message = "New passwords do not match.";
	} elseif (strlen($new_password) < 8) {
		$message = "New password must be at least 8 characters long.";
	} else {
		// Verify current password
		$check_password_sql = "SELECT userPassword FROM Users WHERE userID = ?";
		$check_stmt = $conn->prepare($check_password_sql);
		$check_stmt->bind_param("i", $user_id);
		$check_stmt->execute();
		$password_result = $check_stmt->get_result();

		if ($password_result->num_rows > 0) {
			$user_data = $password_result->fetch_assoc();

			if (password_verify($current_password, $user_data['userPassword'])) {
				// Update password
				$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
				$update_password_sql = "UPDATE Users SET userPassword = ? WHERE userID = ?";
				$update_stmt = $conn->prepare($update_password_sql);
				$update_stmt->bind_param("si", $hashed_password, $user_id);

				if ($update_stmt->execute()) {
					$message = "Password changed successfully!";
				} else {
					$message = "Error changing password. Please try again.";
				}
			} else {
				$message = "Current password is incorrect.";
			}
		} else {
			$message = "Password functionality not available.";
		}
	}
}

// Fetch booking history
$bookings_sql = "SELECT c.concertName, b.totalAmount, b.bookingDate, b.paymentStatus 
                 FROM Bookings b 
                 JOIN Concert c ON b.concertID = c.concertID 
                 WHERE b.userID = ? 
                 ORDER BY b.bookingDate DESC";
$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Handle account deletion
if (isset($_POST['delete_account'])) {
	// Delete user's bookings first
	$delete_bookings = $conn->prepare("DELETE FROM Bookings WHERE userID = ?");
	$delete_bookings->bind_param("i", $user_id);
	$delete_bookings->execute();

	// Delete user's payments
	$delete_payments = $conn->prepare("DELETE FROM Payment WHERE userID = ?");
	$delete_payments->bind_param("i", $user_id);
	$delete_payments->execute();

	// Delete user record
	$delete_user = $conn->prepare("DELETE FROM Users WHERE userID = ?");
	$delete_user->bind_param("i", $user_id);
	$delete_user->execute();

	session_destroy();
	header("Location: signup.php?account_deleted=true");
	exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>GEMPAX EXPO — Dashboard</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="../styling/dashboard-styles.css">
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
					<li><a href="gallery.php">Gallery</a></li>
					<li><a href="booking.php">Booking</a></li>
					<li><a href="contact.php">Contact</a></li>
					<li><a href="dashboard.php" class="active">Dashboard</a></li>
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

	<!-- Dashboard Section -->
	<section class="dashboard-section">
		<div class="dashboard-container">
			<div class="dashboard-header" data-reveal>
				<h2>Welcome, <?php echo htmlspecialchars($user['userFullName']); ?></h2>
				<p>Manage your account and view your purchase history</p>
			</div>

			<?php if (!empty($message)): ?>
				<div class="dashboard-message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>"
					data-reveal>
					<?php echo htmlspecialchars($message); ?>
				</div>
			<?php endif; ?>

			<div class="dashboard-content">
				<div class="user-info-card" data-reveal>
					<h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
					<form class="profile-form" method="POST" action="">
						<div class="form-group">
							<label for="fullname">Full Name</label>
							<input type="text" id="fullname" name="fullname"
								value="<?php echo htmlspecialchars($user['userFullName']); ?>" required>
						</div>

						<div class="form-group">
							<label for="email">Email Address</label>
							<input type="email" id="email" name="email"
								value="<?php echo htmlspecialchars($user['userEmail']); ?>" required>
						</div>

						<div class="form-group">
							<label for="phone">Phone Number</label>
							<input type="tel" id="phone" name="phone"
								value="<?php echo $user['userPhoneNumber'] ? htmlspecialchars($user['userPhoneNumber']) : ''; ?>"
								placeholder="Enter Phone Number" required>
						</div>

						<button type="submit" name="update_profile" class="update-btn">
							<i class="fas fa-save"></i> Update Profile
						</button>
					</form>

					<h3 style="margin-top: 2rem; color: var(--accent-color);"><i class="fas fa-lock"></i> Change
						Password</h3>
					<form class="profile-form" method="POST" action="">
						<div class="dashboard-form-group">
							<label for="current_password">Current Password</label>
							<input type="password" id="current_password" name="current_password" required>
						</div>

						<div class="dashboard-form-group">
							<label for="new_password">New Password</label>
							<input type="password" id="new_password" name="new_password" required>
						</div>

						<div class="dashboard-form-group">
							<label for="confirm_password">Confirm New Password</label>
							<input type="password" id="confirm_password" name="confirm_password" required>
						</div>

						<button type="submit" name="change_password" class="update-btn">
							<i class="fas fa-key"></i> Change Password
						</button>
					</form>
				</div>

				<div class="purchases-card" data-reveal>
					<h3><i class="fas fa-receipt"></i> Booking History</h3>
					<?php if ($bookings->num_rows > 0): ?>
						<table class="purchases-table">
							<thead>
								<tr>
									<th>Concert</th>
									<th>Amount (RM)</th>
									<th>Booking Date</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php while ($row = $bookings->fetch_assoc()): ?>
									<tr>
										<td><?php echo htmlspecialchars($row['concertName']); ?></td>
										<td><?php echo htmlspecialchars($row['totalAmount']); ?></td>
										<td><?php echo htmlspecialchars($row['bookingDate']); ?></td>
										<td><?php echo htmlspecialchars($row['paymentStatus']); ?></td>
									</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					<?php else: ?>
						<div class="no-purchases">
							<i class="fas fa-shopping-cart"></i>
							<h4>No bookings found</h4>
							<p>You haven't made any bookings yet</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="dashboard-actions" data-reveal>
				<a href="logout.php" class="logout-btn">
					<i class="fas fa-sign-out-alt"></i> Logout
				</a>

				<form method="POST"
					onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
					<button type="submit" name="delete_account" class="delete-btn">
						<i class="fas fa-trash-alt"></i> Delete Account
					</button>
				</form>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="site-footer">
		<div class="container">
			<p>© GEMPAX EXPO — 2025</p>
		</div>
	</footer>

	<script>
		// Scroll reveal animation
		const revealElements = document.querySelectorAll('[data-reveal]');
		const revealOptions = {
			root: null,
			rootMargin: '0px',
			threshold: 0.1
		};

		const revealObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					entry.target.classList.add('revealed');
					observer.unobserve(entry.target);
				}
			});
		}, revealOptions);

		revealElements.forEach(element => {
			revealObserver.observe(element);
		});

		// Auto-hide message after 5 seconds
		const messageElement = document.querySelector('.dashboard-message');
		if (messageElement) {
			setTimeout(() => {
				messageElement.style.opacity = '0';
				messageElement.style.transition = 'opacity 0.5s ease';
				setTimeout(() => {
					messageElement.remove();
				}, 500);
			}, 5000);
		}

		// Password strength indicator
		const newPasswordInput = document.getElementById('new_password');
		if (newPasswordInput) {
			const strengthIndicator = document.createElement('div');
			strengthIndicator.className = 'password-strength';
			newPasswordInput.parentNode.appendChild(strengthIndicator);

			newPasswordInput.addEventListener('input', function () {
				const password = this.value;
				let strength = 'Weak';
				let strengthClass = 'strength-weak';

				if (password.length >= 8) {
					strength = 'Medium';
					strengthClass = 'strength-medium';
				}

				if (password.length >= 12 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
					strength = 'Strong';
					strengthClass = 'strength-strong';
				}

				strengthIndicator.textContent = `Strength: ${strength}`;
				strengthIndicator.className = `password-strength ${strengthClass}`;
			});

			// Password confirmation check
			const confirmPasswordInput = document.getElementById('confirm_password');
			const confirmIndicator = document.createElement('div');
			confirmIndicator.className = 'password-strength';
			confirmPasswordInput.parentNode.appendChild(confirmIndicator);

			function checkPasswordMatch() {
				const newPassword = newPasswordInput.value;
				const confirmPassword = confirmPasswordInput.value;

				if (confirmPassword === '') {
					confirmIndicator.textContent = '';
				} else if (newPassword === confirmPassword) {
					confirmIndicator.textContent = '✓ Passwords match';
					confirmIndicator.className = 'password-strength strength-strong';
				} else {
					confirmIndicator.textContent = '✗ Passwords do not match';
					confirmIndicator.className = 'password-strength strength-weak';
				}
			}

			newPasswordInput.addEventListener('input', checkPasswordMatch);
			confirmPasswordInput.addEventListener('input', checkPasswordMatch);
		}
	</script>
</body>

</html>