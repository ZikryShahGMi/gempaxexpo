<?php
session_start();
include 'db_connect.php';
include 'admincheck.php';

requireAdmin();

// Handle form submissions for this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['add_concert'])) {
		$concertName = $_POST['concert_name'];
		$concertDate = $_POST['concert_date'];
		$concertTime = $_POST['concert_time'];
		$ticketPrice = $_POST['ticket_price'];
		$venueID = $_POST['venue_id'];
		$description = $_POST['description'] ?? '';
		$image_url = $_POST['image_url'] ?? '';
		$total_tickets = $_POST['total_tickets'] ?? 0;
		$duration_minutes = $_POST['duration_minutes'] ?? 0;
		$age_restriction = $_POST['age_restriction'] ?? 'All ages';
		$status = $_POST['status'] ?? 'upcoming';
		$event_type = $_POST['event_type'] ?? 'concert';

		$sql = "INSERT INTO Concert (concertName, concertDate, concertTime, ticketPrice, venueID, 
                                description, image_url, total_tickets, duration_minutes, 
                                age_restriction, status, event_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param(
			"sssdisssisss",
			$concertName,
			$concertDate,
			$concertTime,
			$ticketPrice,
			$venueID,
			$description,
			$image_url,
			$total_tickets,
			$duration_minutes,
			$age_restriction,
			$status,
			$event_type
		);

		if ($stmt->execute()) {
			$success_message = "Concert added successfully!";
		} else {
			$error_message = "Error adding concert: " . $conn->error;
		}
	}

	if (isset($_POST['delete_concert'])) {
		$concertID = $_POST['concert_id'];
		$sql = "DELETE FROM Concert WHERE concertID=?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $concertID);

		if ($stmt->execute()) {
			$success_message = "Concert deleted successfully!";
		} else {
			$error_message = "Error deleting concert: " . $conn->error;
		}
	}
}

// Fetch data
$venues = $conn->query("SELECT * FROM Venue");
$concerts = $conn->query("SELECT c.*, v.venueName FROM Concert c JOIN Venue v ON c.venueID = v.venueID");
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Management - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>
	<!-- Header Section -->
	<header class="site-header">
		<div class="container nav-container">
			<h1 class="logo"><a href="index.php">GEMPAX EXPO</a></h1>

			<nav class="main-nav">
				<ul>
					<li><a href="index.php#about">About</a></li>
					<li><a href="events.php">Events</a></li>
					<li><a href="gallery.php">Gallery</a></li>
					<li><a href="booking.php">Booking</a></li>
					<li><a href="contact.php">Contact</a></li>
					<li><a href="adminpage.php">Admin Dashboard</a></li>
					<li><a href="adminmanagementpage.php" class="active">Management Page</a></li>

					<?php if (isset($_SESSION['user_id'])): ?>
						<!-- Show Dashboard only if logged in -->
						<li><a href="dashboard.php">Dashboard</a></li>
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

			<button class="lang-btn">MS</button>
		</div>
	</header>

	<div class="container-section">
		<h2>Manage Events, Gallery, and Users</h2>

		<!-- Success/Error Messages -->
		<?php if (isset($success_message)): ?>
			<div class="message success"><?php echo $success_message; ?></div>
		<?php endif; ?>
		<?php if (isset($error_message)): ?>
			<div class="message error"><?php echo $error_message; ?></div>
		<?php endif; ?>

		<!-- Quick Concert Management -->
		<div class="admin-section">
			<h2>Quick Concert Management</h2>

			<!-- Add Concert Form -->
			<form method="POST" class="admin-form">
				<h3>Add New Concert</h3>
				<div class="form-row">
					<div class="form-group">
						<label>Concert Name:</label>
						<input type="text" name="concert_name" required>
					</div>
					<div class="form-group">
						<label>Event Type:</label>
						<select name="event_type" required>
							<option value="concert">Concert</option>
							<option value="festival">Festival</option>
							<option value="show">Show</option>
						</select>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group">
						<label>Concert Date:</label>
						<input type="date" name="concert_date" required>
					</div>
					<div class="form-group">
						<label>Concert Time:</label>
						<input type="time" name="concert_time" required>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group">
						<label>Ticket Price:</label>
						<input type="number" step="0.01" name="ticket_price" required>
					</div>
					<div class="form-group">
						<label>Venue:</label>
						<select name="venue_id" required>
							<option value="">Select Venue</option>
							<?php while ($venue = $venues->fetch_assoc()): ?>
								<option value="<?php echo $venue['venueID']; ?>"><?php echo $venue['venueName']; ?></option>
							<?php endwhile; ?>
						</select>
					</div>
				</div>
				<button type="submit" name="add_concert" class="btn">Add Concert</button>
			</form>
		</div>

		<div class="admin-section">
			<h2>Manage Events</h2>
			<div class="action-grid">
				<a href="addevent.php" class="btn">Add New Event</a>
				<a href="editevent.php" class="btn">Edit/Delete Events</a>
			</div>
		</div>

		<div class="admin-section">
			<h2>Manage Gallery</h2>
			<div class="action-grid">
				<a href="addgallery.php" class="btn">Add Images</a>
				<a href="managegallery.php" class="btn">Manage Gallery</a>
			</div>
		</div>

		<div class="admin-section">
			<h2>Manage Refunds</h2>
			<div class="action-grid">
				<a href="forcerefund.php" class="btn">Force Refund</a>
				<a href="refundhistory.php" class="btn">Refund History</a>
			</div>
		</div>

		<div class="admin-section">
			<h2>User Management</h2>
			<div class="action-grid">
				<a href="manageusers.php" class="btn">View All Users</a>
				<a href="banuser.php" class="btn btn-danger">Ban User</a>
			</div>
		</div>

		<div class="admin-section">
			<h2>Reports & Analytics</h2>
			<div class="action-grid">
				<a href="salesreport.php" class="btn">Sales Report</a>
				<a href="attendance.php" class="btn">Attendance Analytics</a>
			</div>
		</div>
	</div>

	<footer class="site-footer">
		<div class="container">
			<p>© GEMPAX EXPO — 2025 | Admin Management</p>
		</div>
	</footer>

</body>

</html>
<?php $conn->close(); ?>