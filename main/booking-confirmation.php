<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['booking_id'])) {
	header('Location: booking.php');
	exit;
}

$bookingID = $_GET['booking_id'];

// Get booking details
$booking_sql = "SELECT b.*, c.concertName, c.concertDate, c.concertTime, t.ticketName, t.ticketPrice 
                FROM bookings b 
                JOIN concert c ON b.concertID = c.concertID 
                JOIN tickettypes t ON b.ticketTypeID = t.ticketTypeID 
                WHERE b.bookingID = ?";
$stmt = $conn->prepare($booking_sql);
$stmt->bind_param("i", $bookingID);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
	header('Location: booking.php?error=booking_not_found');
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Booking Confirmation - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<style>
		.confirmation-message {
			text-align: center;
			padding: 3rem 2rem;
			background: var(--card-bg);
			border-radius: 12px;
			margin: 2rem 0;
		}

		.confirmation-icon {
			font-size: 4rem;
			margin-bottom: 1rem;
		}

		.booking-details {
			background: white;
			padding: 2rem;
			border-radius: 8px;
			margin: 2rem 0;
			text-align: left;
			border-left: 4px solid var(--primary-color);
		}

		.confirmation-actions {
			margin-top: 2rem;
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
					<li><a href="gallery.php">Gallery</a></li>
					<li><a href="booking.php">Booking</a></li>
					<li><a href="contact.php">Contact</a></li>
					<?php if (isset($_SESSION['user_id'])): ?>
						<li><a href="dashboard.php">Dashboard</a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</div>
	</header>

	<section class="booking-section">
		<div class="container">
			<div class="confirmation-message" data-reveal>
				<div class="confirmation-icon">🎉</div>
				<h2>Booking Confirmed!</h2>
				<p>Thank you for your purchase. Your tickets have been reserved successfully.</p>

				<div class="booking-details">
					<h3>Booking Details</h3>
					<p><strong>Booking ID:</strong> #<?php echo $bookingID; ?></p>
					<p><strong>Event:</strong> <?php echo htmlspecialchars($booking['concertName']); ?></p>
					<p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['concertDate'])); ?></p>
					<p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['concertTime'])); ?></p>
					<p><strong>Ticket Type:</strong> <?php echo htmlspecialchars($booking['ticketName']); ?></p>
					<p><strong>Quantity:</strong> <?php echo $booking['quantity']; ?></p>
					<p><strong>Total Paid:</strong> RM <?php echo number_format($booking['totalAmount'], 2); ?></p>
					<p><strong>Customer:</strong> <?php echo htmlspecialchars($booking['customerName']); ?></p>
					<p><strong>Email:</strong> <?php echo htmlspecialchars($booking['customerEmail']); ?></p>
				</div>

				<div class="confirmation-actions">
					<a href="index.php" class="btn">Back to Home</a>
					<?php if (isset($_SESSION['user_id'])): ?>
						<a href="dashboard.php" class="btn btn-secondary">View in Dashboard</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="site-footer">
		<div class="container">
			<p>© GEMPAX EXPO — 2025</p>
		</div>
	</footer>
</body>

</html>