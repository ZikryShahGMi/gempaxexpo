<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../main/db_connect.php';

// Get parameters from PayPal return or session
$bookingID = isset($_GET['item_number']) ? $_GET['item_number'] : (isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null);

if (!$bookingID) {
	header('Location: ../main/booking.php?error=invalid_booking');
	exit;
}

// Update booking status to paid
$update_sql = "UPDATE bookings SET paymentStatus = 'paid', bookingStatus = 'confirmed' WHERE bookingID = ?";
$stmt = $conn->prepare($update_sql);

if ($stmt) {
	$stmt->bind_param("i", $bookingID);

	if ($stmt->execute()) {
		// Insert payment record
		$payment_sql = "INSERT INTO payment (bookingID, userID, paymentStatus, paymentDate, totalAmount) 
                        VALUES (?, ?, 'completed', NOW(), 
                        (SELECT totalAmount FROM bookings WHERE bookingID = ?))";
		$payment_stmt = $conn->prepare($payment_sql);
		$userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
		$payment_stmt->bind_param("iii", $bookingID, $userID, $bookingID);
		$payment_stmt->execute();
		$payment_stmt->close();

		// Clear session
		unset($_SESSION['pending_booking']);
		unset($_SESSION['booking_amount']);
		unset($_SESSION['booking_details']);

		// Get booking details for display
		$booking_sql = "SELECT b.*, c.concertName, c.concertDate, c.concertTime, t.ticketName 
                        FROM bookings b 
                        JOIN concert c ON b.concertID = c.concertID 
                        JOIN tickettypes t ON b.ticketTypeID = t.ticketTypeID 
                        WHERE b.bookingID = ?";
		$booking_stmt = $conn->prepare($booking_sql);
		$booking_stmt->bind_param("i", $bookingID);
		$booking_stmt->execute();
		$booking_result = $booking_stmt->get_result();
		$booking_data = $booking_result->fetch_assoc();
		$booking_stmt->close();
	} else {
		header('Location: ../main/booking.php?error=payment_update_failed');
		exit;
	}
	$stmt->close();
} else {
	header('Location: ../main/booking.php?error=database_error');
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Payment Successful - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="../styling/payment-style.css">
	<style>
		.success-icon {
			font-size: 4rem;
			color: #28a745;
			margin-bottom: 1rem;
		}

		.confirmation-details {
			background: #f8f9fa;
			padding: 2rem;
			border-radius: 12px;
			margin: 2rem 0;
			border-left: 4px solid #28a745;
		}

		.detail-row {
			display: flex;
			justify-content: space-between;
			margin-bottom: 0.75rem;
			padding-bottom: 0.75rem;
			border-bottom: 1px solid #dee2e6;
		}

		.detail-row:last-child {
			border-bottom: none;
			margin-bottom: 0;
		}

		.next-steps {
			background: #d4edda;
			border: 1px solid #c3e6cb;
			border-radius: 8px;
			padding: 1.5rem;
			margin: 2rem 0;
		}
	</style>
</head>

<body>
	<!-- Header Section -->
	<header class="site-header">
		<div class="container header">
			<a class="logo" href="../main/index.php">GEMPAX EXPO</a>
			<nav class="main-nav">
				<ul>
					<li><a href="../main/index.php">Home</a></li>
					<li><a href="../main/booking.php">Booking</a></li>
				</ul>
			</nav>
		</div>
	</header>

	<!-- Success Section -->
	<section class="payment-section">
		<div class="container">
			<div class="payment-container">
				<div class="payment-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
					<div class="success-icon">✅</div>
					<h2>Payment Successful!</h2>
					<p>Thank you for your purchase. Your tickets have been confirmed.</p>
				</div>

				<div class="payment-content">
					<!-- Confirmation Details -->
					<div class="confirmation-details">
						<h3 style="text-align: center; margin-bottom: 1.5rem; color: #28a745;">Booking Confirmation</h3>

						<div class="detail-row">
							<strong>Confirmation Number:</strong>
							<span
								style="font-family: monospace; font-weight: bold; color: #333;">#<?php echo $bookingID; ?></span>
						</div>

						<?php if ($booking_data): ?>
							<div class="detail-row">
								<strong>Event:</strong>
								<span><?php echo htmlspecialchars($booking_data['concertName']); ?></span>
							</div>

							<div class="detail-row">
								<strong>Date & Time:</strong>
								<span>
									<?php echo date('F j, Y', strtotime($booking_data['concertDate'])); ?> at
									<?php echo date('g:i A', strtotime($booking_data['concertTime'])); ?>
								</span>
							</div>

							<div class="detail-row">
								<strong>Ticket Type:</strong>
								<span><?php echo htmlspecialchars($booking_data['ticketName']); ?></span>
							</div>

							<div class="detail-row">
								<strong>Quantity:</strong>
								<span><?php echo $booking_data['quantity']; ?> tickets</span>
							</div>

							<div class="detail-row">
								<strong>Total Paid:</strong>
								<span style="font-size: 1.2rem; font-weight: bold; color: #28a745;">
									RM <?php echo number_format($booking_data['totalAmount'], 2); ?>
								</span>
							</div>

							<div class="detail-row">
								<strong>Customer Name:</strong>
								<span><?php echo htmlspecialchars($booking_data['customerName']); ?></span>
							</div>

							<div class="detail-row">
								<strong>Email:</strong>
								<span><?php echo htmlspecialchars($booking_data['customerEmail']); ?></span>
							</div>
						<?php endif; ?>
					</div>

					<!-- Next Steps -->
					<div class="next-steps">
						<h4 style="color: #155724; margin-bottom: 1rem;">What's Next?</h4>
						<ul style="text-align: left; color: #155724;">
							<li>You will receive a confirmation email shortly</li>
							<li>Keep your confirmation number (#<?php echo $bookingID; ?>) for reference</li>
							<li>Present your confirmation at the venue entrance</li>
							<li>Doors open 1 hour before the event starts</li>
						</ul>
					</div>

					<!-- Action Buttons -->
					<div class="payment-actions">
						<a href="../main/index.php" class="btn btn-primary">Back to Home</a>
						<?php if (isset($_SESSION['user_id'])): ?>
							<a href="../main/dashboard.php" class="btn btn-secondary">View in Dashboard</a>
						<?php endif; ?>
						<a href="../main/booking.php" class="btn btn-secondary">Book More Tickets</a>
					</div>

					<!-- Support Info -->
					<div
						style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #dee2e6;">
						<p style="color: #666; font-size: 0.9rem;">
							Need help? Contact our support team at
							<a href="mailto:support@gempaxexpo.com" style="color: #667eea;">support@gempaxexpo.com</a>
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="site-footer">
		<div class="container">
			<p>&copy; 2025 GEMPAX EXPO. All rights reserved.</p>
		</div>
	</footer>
</body>

</html>