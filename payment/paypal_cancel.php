<?php
session_start();
include '../main/db_connect.php';

$bookingID = isset($_GET['item_number']) ? $_GET['item_number'] : (isset($_SESSION['pending_booking']) ? $_SESSION['pending_booking'] : null);

if ($bookingID) {
    // Update booking status to cancelled
    $update_sql = "UPDATE bookings SET paymentStatus = 'cancelled', bookingStatus = 'cancelled' WHERE bookingID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    
    // Restore ticket quantities
    $restore_sql = "UPDATE tickettypes t 
                    JOIN bookings b ON t.ticketTypeID = b.ticketTypeID 
                    SET t.availableQuantity = t.availableQuantity + b.quantity 
                    WHERE b.bookingID = ?";
    $restore_stmt = $conn->prepare($restore_sql);
    $restore_stmt->bind_param("i", $bookingID);
    $restore_stmt->execute();
    
    // Clear session
    unset($_SESSION['pending_booking']);
    unset($_SESSION['booking_amount']);
    unset($_SESSION['booking_details']);
}

header('Location: ../main/booking.php?error=payment_cancelled');
exit;
?>