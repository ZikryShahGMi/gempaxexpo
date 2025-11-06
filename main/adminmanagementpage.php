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

// Fetch data for display
$concerts = $conn->query("SELECT c.*, v.venueName FROM Concert c JOIN Venue v ON c.venueID = v.venueID");
$bookings = $conn->query("SELECT b.*, u.userFullName, c.concertName FROM Bookings b JOIN Users u ON b.userID = u.userID JOIN Concert c ON b.concertID = c.concertID");
$users = $conn->query("SELECT * FROM Users");
$payments = $conn->query("SELECT p.*, u.userFullName, b.bookingID FROM Payment p JOIN Users u ON p.userID = u.userID JOIN Bookings b ON p.bookingID = b.bookingID");
$venues = $conn->query("SELECT * FROM Venue");
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

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-container">
		<!-- Admin Header -->
		<div class="admin-header">
			<h1>Admin Dashboard</h1>
		</div>

		<!-- Success/Error Messages -->
		<?php if (isset($success_message)): ?>
			<div class="message success"><?php echo $success_message; ?></div>
		<?php endif; ?>

		<?php if (isset($error_message)): ?>
			<div class="message error"><?php echo $error_message; ?></div>
		<?php endif; ?>

		<!-- Quick Stats Section -->
		<div class="admin-section">
			<h2>Dashboard Overview</h2>
			<div class="stats-grid">
				<div class="stat-card">
					<h3>Total Users</h3>
					<p class="stat-number"><?php echo $users->num_rows; ?></p>
				</div>
				<div class="stat-card">
					<h3>Total Concerts</h3>
					<p class="stat-number"><?php echo $concerts->num_rows; ?></p>
				</div>
				<div class="stat-card">
					<h3>Total Bookings</h3>
					<p class="stat-number"><?php echo $bookings->num_rows; ?></p>
				</div>
				<div class="stat-card">
					<h3>Total Payments</h3>
					<p class="stat-number"><?php echo $payments->num_rows; ?></p>
				</div>
			</div>
		</div>

		<!-- Management Sections Grid -->
		<div class="admin-sections-grid">
			<!-- Concert Management -->
			<div class="admin-section">
				<div class="section-header">
					<h2>Concert Management</h2>
					<a href="../backend/manageevents.php" class="btn btn-primary">Manage Events</a>
				</div>
				<div class="admin-table-container">
					<table class="admin-table">
						<thead>
							<tr>
								<th>ID</th>
								<th>Concert Name</th>
								<th>Date</th>
								<th>Time</th>
								<th>Price</th>
								<th>Venue</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$concerts->data_seek(0);
							while ($concert = $concerts->fetch_assoc()): ?>
								<tr>
									<td><?php echo $concert['concertID']; ?></td>
									<td>
										<strong><?php echo $concert['concertName']; ?></strong>
										<?php if (!empty($concert['event_type'])): ?>
											<br><small>Type: <?php echo ucfirst($concert['event_type']); ?></small>
										<?php endif; ?>
									</td>
									<td><?php echo $concert['concertDate']; ?></td>
									<td><?php echo $concert['concertTime']; ?></td>
									<td>$<?php echo $concert['ticketPrice']; ?></td>
									<td><?php echo $concert['venueName']; ?></td>
									<td>
										<span class="status-badge status-<?php echo $concert['status'] ?? 'upcoming'; ?>">
											<?php echo ucfirst($concert['status'] ?? 'upcoming'); ?>
										</span>
									</td>
									<td>
										<div class="action-buttons">
											<form method="POST" class="inline-form">
												<input type="hidden" name="concert_id"
													value="<?php echo $concert['concertID']; ?>">
												<button type="submit" name="delete_concert" class="btn btn-danger btn-small"
													onclick="return confirm('Are you sure you want to delete this concert?')">
													Delete
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Payments Management -->
			<div class="admin-section">
				<div class="section-header">
					<h2>Payments Management</h2>
				</div>
				<div class="admin-table-container">
					<table class="admin-table">
						<thead>
							<tr>
								<th>Payment ID</th>
								<th>User</th>
								<th>Booking ID</th>
								<th>Date</th>
								<th>Amount</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$payments->data_seek(0);
							while ($payment = $payments->fetch_assoc()): ?>
								<tr>
									<td><?php echo $payment['paymentID']; ?></td>
									<td><?php echo $payment['userFullName']; ?></td>
									<td><?php echo $payment['bookingID']; ?></td>
									<td><?php echo $payment['paymentDate']; ?></td>
									<td>$<?php echo $payment['totalAmount']; ?></td>
									<td>
										<span
											class="status-badge status-<?php echo strtolower($payment['paymentStatus']); ?>">
											<?php echo ucfirst($payment['paymentStatus']); ?>
										</span>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Quick Actions Grid -->
		<div class="admin-section">
			<h2>Quick Management</h2>
			<div class="action-grid">
				<div class="action-card">
					<h3>Events</h3>
					<p>Manage concerts and events</p>
					<a href="../backend/manageevents.php" class="btn btn-primary">Manage Events</a>
				</div>
				<div class="action-card">
					<h3>Gallery</h3>
					<p>Manage event photos</p>
					<a href="../backend/managegallery.php" class="btn btn-primary">Manage Gallery</a>
				</div>
				<div class="action-card">
					<h3>Bookings</h3>
					<p>View and manage bookings</p>
					<a href="../backend/managebooking.php" class="btn btn-primary">Manage Bookings</a>
				</div>
				<div class="action-card">
					<h3>Users</h3>
					<p>Manage user accounts</p>
					<a href="../backend/manageusers.php" class="btn btn-primary">Manage Users</a>
				</div>
				<div class="action-card">
					<h3>Reports</h3>
					<p>View analytics & reports</p>
					<a href="../backend/reports.php" class="btn btn-primary">View Reports</a>
				</div>
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