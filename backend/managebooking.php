<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';

requireAdmin();

// Handle form submissions
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

	if (isset($_POST['update_concert'])) {
		$concertID = $_POST['concert_id'];
		$concertName = $_POST['concert_name'];
		$concertDate = $_POST['concert_date'];
		$concertTime = $_POST['concert_time'];
		$ticketPrice = $_POST['ticket_price'];
		$venueID = $_POST['venue_id'];

		$sql = "UPDATE Concert SET concertName=?, concertDate=?, concertTime=?, ticketPrice=?, venueID=? 
                WHERE concertID=?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("sssdii", $concertName, $concertDate, $concertTime, $ticketPrice, $venueID, $concertID);

		if ($stmt->execute()) {
			$success_message = "Concert updated successfully!";
		} else {
			$error_message = "Error updating concert: " . $conn->error;
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

	if (isset($_POST['update_booking_status'])) {
		$bookingID = $_POST['booking_id'];
		$paymentStatus = $_POST['payment_status'];

		$sql = "UPDATE Bookings SET paymentStatus=? WHERE bookingID=?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si", $paymentStatus, $bookingID);

		if ($stmt->execute()) {
			$success_message = "Booking status updated successfully!";
		} else {
			$error_message = "Error updating booking status: " . $conn->error;
		}
	}

	if (isset($_POST['update_payment_status'])) {
		$paymentID = $_POST['payment_id'];
		$paymentStatus = $_POST['payment_status'];

		$sql = "UPDATE Payment SET paymentStatus=? WHERE paymentID=?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("si", $paymentStatus, $paymentID);

		if ($stmt->execute()) {
			$success_message = "Payment status updated successfully!";
		} else {
			$error_message = "Error updating payment status: " . $conn->error;
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
	<title>Admin Dashboard - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-section">
		<h2>Booking Management</h2>
		<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
		<table class="admin-table">
			<thead>
				<tr>
					<th>Booking ID</th>
					<th>User</th>
					<th>Concert</th>
					<th>Booking Date</th>
					<th>Total Amount</th>
					<th>Payment Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$bookings->data_seek(0);
				while ($booking = $bookings->fetch_assoc()): ?>
					<tr>
						<td><?php echo $booking['bookingID']; ?></td>
						<td><?php echo $booking['userFullName']; ?></td>
						<td><?php echo $booking['concertName']; ?></td>
						<td><?php echo $booking['bookingDate']; ?></td>
						<td>$<?php echo $booking['totalAmount']; ?></td>
						<td>
							<span class="status-badge status-<?php echo strtolower($booking['paymentStatus']); ?>">
								<?php echo ucfirst($booking['paymentStatus']); ?>
							</span>
						</td>
						<td>
							<form method="POST" class="inline-form">
								<input type="hidden" name="booking_id" value="<?php echo $booking['bookingID']; ?>">
								<select name="payment_status">
									<option value="pending" <?php echo $booking['paymentStatus'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
									<option value="paid" <?php echo $booking['paymentStatus'] == 'paid' ? 'selected' : ''; ?>>
										Paid
									</option>
									<option value="cancelled" <?php echo $booking['paymentStatus'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
									<option value="refunded" <?php echo $booking['paymentStatus'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
								</select>
								<button type="submit" name="update_booking_status" class="btn">Update</button>
							</form>
						</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
	</div>

</body>

</html>
<?php $conn->close(); ?>