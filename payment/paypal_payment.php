<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../main/db_connect.php';

// Get booking ID from URL or session
$bookingID = isset($_GET['booking_id']) ? $_GET['booking_id'] : (isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null);

if (!$bookingID) {
	header('Location: ../main/booking.php?error=no_booking_id');
	exit;
}

// Get booking details
$booking_sql = "SELECT b.*, c.concertName, t.ticketName, t.ticketPrice 
                FROM bookings b 
                JOIN concert c ON b.concertID = c.concertID 
                JOIN tickettypes t ON b.ticketTypeID = t.ticketTypeID 
                WHERE b.bookingID = ?";

$stmt = $conn->prepare($booking_sql);
if (!$stmt) {
	die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $bookingID);
if (!$stmt->execute()) {
	die("Failed to get booking: " . $stmt->error);
}

$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
	header('Location: ../main/booking.php?error=booking_not_found');
	exit;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Complete Payment - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="../styling/payment-style.css">
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

	<!-- Payment Section -->
	<section class="payment-section">
		<div class="container">
			<div class="payment-container">
				<div class="payment-header">
					<h2>Complete Your Payment</h2>
					<p>You're almost there! Complete your payment to secure your tickets.</p>
				</div>

				<div class="payment-content">
					<!-- Debug Info -->
					<div class="debug-info">
						<strong>Debug Info:</strong> Booking ID: <?php echo $bookingID; ?> |
						Amount: RM <?php echo number_format($booking['totalAmount'], 2); ?>
					</div>

					<!-- Booking Summary -->
					<div class="booking-summary">
						<h3>Booking Summary</h3>
						<div class="booking-detail">
							<strong>Booking ID:</strong>
							<span class="value">#<?php echo $bookingID; ?></span>
						</div>
						<div class="booking-detail">
							<strong>Event:</strong>
							<span class="value"><?php echo htmlspecialchars($booking['concertName']); ?></span>
						</div>
						<div class="booking-detail">
							<strong>Ticket Type:</strong>
							<span class="value"><?php echo htmlspecialchars($booking['ticketName']); ?></span>
						</div>
						<div class="booking-detail">
							<strong>Quantity:</strong>
							<span class="value"><?php echo $booking['quantity']; ?></span>
						</div>
						<div class="booking-detail">
							<strong>Unit Price:</strong>
							<span class="value">RM <?php echo number_format($booking['ticketPrice'], 2); ?></span>
						</div>
						<div class="booking-detail">
							<strong>Total Amount:</strong>
							<span class="value" style="color: #667eea; font-size: 1.2rem;">
								RM <?php echo number_format($booking['totalAmount'], 2); ?>
							</span>
						</div>
					</div>

					<!-- PayPal Payment Section -->
					<div class="paypal-section">
						<h3>Pay with PayPal</h3>
						<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" id="paypal-form">
							<input type="hidden" name="cmd" value="_xclick">
							<input type="hidden" name="business" value="sb-40cis47358906@business.example.com">
							<input type="hidden" name="item_name"
								value="GEMPAX EXPO - <?php echo htmlspecialchars($booking['concertName']); ?>">
							<input type="hidden" name="item_number" value="<?php echo $bookingID; ?>">
							<input type="hidden" name="amount"
								value="<?php echo number_format($booking['totalAmount'], 2); ?>">
							<input type="hidden" name="currency_code" value="MYR">
							<input type="hidden" name="return"
								value="http://localhost/draft-mini-project/payment/payment_success.php">
							<input type="hidden" name="cancel_return"
								value="http://localhost/draft-mini-project/payment/payment_cancel.php">
							<!-- Remove the notify_url for testing -->
							<button type="submit" class="paypal-button">
								<i class="fab fa-paypal"></i>
								Pay RM <?php echo number_format($booking['totalAmount'], 2); ?>
							</button>
						</form>

						<div class="test-credentials">
							<strong>PayPal Sandbox Test Credentials:</strong>
							<p>Email: sb-40cis47358906@business.example.com</p>
							<p>Password: q?luF#L6</p>
						</div>
					</div>

					<!-- Alternative Payment Methods -->
					<div class="alternative-payment">
						<strong>Alternative Payment Methods</strong>
						<p>For bank transfer or other payment methods, please contact us at payments@gempaxexpo.com</p>
					</div>

					<!-- Action Buttons -->
					<div class="payment-actions">
						<a href="../main/booking.php" class="btn btn-secondary">Cancel Booking</a>
						<a href="../main/index.php" class="btn btn-primary">Back to Home</a>
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

	<script>
		console.log("Payment page loaded successfully");
		console.log("Booking ID: <?php echo $bookingID; ?>");
		console.log("Amount: RM <?php echo number_format($booking['totalAmount'], 2); ?>");
	</script>
</body>

</html>