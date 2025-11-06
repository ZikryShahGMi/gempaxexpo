<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .message { background: #fff3f3; border: 1px solid #ffcccc; padding: 20px; border-radius: 5px; }
        .success { background: #f3fff3; border: 1px solid #ccffcc; }
    </style>
</head>
<body>";

echo "<div class='message'>";
echo "<h2>Payment Cancelled</h2>";

try {
	// Include database connection
	include '../main/db_connect.php';

	// Get booking ID from URL, session, or POST
	$bookingID = 0;

	if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
		$bookingID = intval($_GET['booking_id']);
	} elseif (isset($_SESSION['booking_id'])) {
		$bookingID = intval($_SESSION['booking_id']);
	} elseif (isset($_POST['item_number'])) {
		$bookingID = intval($_POST['item_number']);
	}

	if ($bookingID > 0) {
		echo "<p><strong>Booking Reference: #$bookingID</strong></p>";

		// Update booking status to cancelled
		$update_sql = "UPDATE bookings SET paymentStatus = 'cancelled', bookingStatus = 'cancelled' WHERE bookingID = ?";
		$stmt = $conn->prepare($update_sql);

		if ($stmt) {
			$stmt->bind_param("i", $bookingID);

			if ($stmt->execute()) {
				echo "<div class='success'>";
				echo "<p>✓ Your booking has been successfully cancelled.</p>";
				echo "</div>";

				// Log the cancellation
				file_put_contents('cancellation_log.txt', date('Y-m-d H:i:s') . " - Booking $bookingID cancelled\n", FILE_APPEND);
			} else {
				echo "<p>⚠️ Could not update booking status in database.</p>";
			}
			$stmt->close();
		} else {
			echo "<p>⚠️ Database statement preparation failed.</p>";
		}
	} else {
		echo "<p>⚠️ No booking reference found. Your payment process was cancelled.</p>";
		echo "<p>If you have a booking reference, please contact support.</p>";
	}

	$conn->close();

} catch (Exception $e) {
	echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Display navigation links
echo "<hr>";
echo "<p>";
echo "<a href='../main/index.php'>← Return to Homepage</a> | ";
echo "<a href='../main/booking.php'>View Bookings</a> | ";
echo "<a href='../main/contact.php'>Contact Support</a>";
echo "</p>";

echo "</div>";

// Debug info (remove in production)
echo "<!-- Debug Info -->
<div style='margin-top: 20px; padding: 10px; background: #f0f0f0; font-size: 12px;'>
    <strong>Debug Information:</strong><br>
    Booking ID: " . ($bookingID > 0 ? $bookingID : 'Not found') . "<br>
    GET: " . print_r($_GET, true) . "<br>
    POST: " . print_r($_POST, true) . "<br>
    SESSION: " . (isset($_SESSION) ? print_r($_SESSION, true) : 'Not started') . "
</div>";

echo "</body></html>";
?>