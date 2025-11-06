<?php
session_start();
include '../main/db_connect.php';

// Get parameters from PayPal return
$bookingID = isset($_GET['item_number']) ? $_GET['item_number'] : (isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null);

if (!$bookingID) {
	header('Location: ../main/booking.php?error=invalid_booking');
	exit;
}

// Update booking status to paid
$update_sql = "UPDATE bookings SET paymentStatus = 'paid', bookingStatus = 'confirmed' WHERE bookingID = ?";
$stmt = $conn->prepare($update_sql);
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

	// Clear session
	unset($_SESSION['pending_booking']);
	unset($_SESSION['booking_amount']);
	unset($_SESSION['booking_details']);

	// Redirect to confirmation
	header('Location: ../main/booking-confirmation.php?booking_id=' . $bookingID . '&payment=success');
	exit;
} else {
	header('Location: ../main/booking.php?error=payment_update_failed');
	exit;
}
?>