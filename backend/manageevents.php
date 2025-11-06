<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

$message = '';
$venues = $conn->query("SELECT venueID, venueName FROM Venue");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$concertName = trim($_POST['concert_name']);
	$concertDate = $_POST['concert_date'];
	$concertTime = $_POST['concert_time'];
	$ticketPrice = $_POST['ticket_price'];
	$venueID = $_POST['venue_id'];
	$description = trim($_POST['description'] ?? '');
	$image_url = trim($_POST['image_url'] ?? '');
	$total_tickets = $_POST['total_tickets'] ?? 0;
	$duration_minutes = $_POST['duration_minutes'] ?? 180;
	$age_restriction = $_POST['age_restriction'] ?? 'All ages';
	$status = $_POST['status'] ?? 'upcoming';
	$event_type = $_POST['event_type'] ?? 'concert';

	// Validation
	if (empty($concertName) || empty($concertDate) || empty($venueID)) {
		$message = "error:Please fill in all required fields.";
	} else {
		// Handle image upload
		$uploaded_image_url = $image_url; // Default to URL if no file uploaded

		if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === 0) {
			$upload_dir = '../visuals/events/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0755, true);
			}

			$filename = uniqid() . '_' . basename($_FILES['event_image']['name']);
			$target_file = $upload_dir . $filename;

			// Check image type
			$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
			$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

			// Check if file is actually an image
			$check = getimagesize($_FILES['event_image']['tmp_name']);
			if ($check === false) {
				$message = "error:File is not a valid image.";
			} elseif (!in_array($imageFileType, $allowed_types)) {
				$message = "error:Only JPG, JPEG, PNG, GIF & WebP files are allowed.";
			} elseif ($_FILES['event_image']['size'] > 50000000) { // 50MB limit
				$message = "error:File is too large. Maximum size is 5MB.";
			} else {
				if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
					$uploaded_image_url = '../visuals/events/' . $filename; // ← ADD ../ at the beginning
				} else {
					$message = "error:Error uploading file.";
				}
			}
		}

		// Only proceed with database insertion if no upload errors
		if (empty($message) || strpos($message, 'error:') === false) {
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
				$uploaded_image_url, // Use uploaded image URL or existing URL
				$total_tickets,
				$duration_minutes,
				$age_restriction,
				$status,
				$event_type
			);

			if ($stmt->execute()) {
				$concertID = $conn->insert_id;

				// Add default ticket types
				$ticketTypes = [
					['General Admission', $ticketPrice, 'Standard entry to the event', $total_tickets],
					['VIP Package', $ticketPrice * 2, 'Priority entry and exclusive merchandise', floor($total_tickets * 0.3)],
					['VVIP Experience', $ticketPrice * 3, 'All VIP benefits plus special experiences', floor($total_tickets * 0.1)]
				];

				foreach ($ticketTypes as $ticket) {
					$ticket_sql = "INSERT INTO tickettypes (concertID, ticketName, ticketPrice, ticketDescription, availableQuantity) 
                                  VALUES (?, ?, ?, ?, ?)";
					$ticket_stmt = $conn->prepare($ticket_sql);
					$ticket_stmt->bind_param("isdss", $concertID, $ticket[0], $ticket[1], $ticket[2], $ticket[3]);
					$ticket_stmt->execute();
				}

				$message = "success:Event added successfully with default ticket types!";
			} else {
				$message = "error:Error adding event: " . $conn->error;
			}
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add Event - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-section">
		<h2>Event Management</h2>
		<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
	</div>

	<?php if (!empty($message)):
		list($type, $text) = explode(':', $message, 2); ?>
		<div class="message <?php echo $type; ?>"><?php echo $text; ?></div>
	<?php endif; ?>

	<form method="POST" class="admin-form" enctype="multipart/form-data">
		<div class="form-grid">
			<div class="form-group">
				<label for="concert_name">Event Name *</label>
				<input type="text" id="concert_name" name="concert_name" required>
			</div>

			<div class="form-group">
				<label for="venue_id">Venue *</label>
				<select id="venue_id" name="venue_id" required>
					<option value="">Select Venue</option>
					<?php while ($venue = $venues->fetch_assoc()): ?>
						<option value="<?php echo $venue['venueID']; ?>">
							<?php echo htmlspecialchars($venue['venueName']); ?>
						</option>
					<?php endwhile; ?>
				</select>
			</div>

			<div class="form-group">
				<label for="concert_date">Event Date *</label>
				<input type="date" id="concert_date" name="concert_date" required>
			</div>

			<div class="form-group">
				<label for="concert_time">Event Time</label>
				<input type="time" id="concert_time" name="concert_time">
			</div>

			<div class="form-group">
				<label for="ticket_price">Base Ticket Price (RM) *</label>
				<input type="number" id="ticket_price" name="ticket_price" step="0.01" min="0" required>
			</div>

			<div class="form-group">
				<label for="total_tickets">Total Tickets</label>
				<input type="number" id="total_tickets" name="total_tickets" min="0" value="1000">
			</div>

			<div class="form-group">
				<label for="duration_minutes">Duration (minutes)</label>
				<input type="number" id="duration_minutes" name="duration_minutes" min="0" value="180">
			</div>

			<div class="form-group">
				<label for="age_restriction">Age Restriction</label>
				<select id="age_restriction" name="age_restriction">
					<option value="All ages">All ages</option>
					<option value="18+">18+</option>
					<option value="21+">21+</option>
				</select>
			</div>

			<div class="form-group">
				<label for="status">Status</label>
				<select id="status" name="status">
					<option value="upcoming">Upcoming</option>
					<option value="ongoing">Ongoing</option>
					<option value="completed">Completed</option>
					<option value="cancelled">Cancelled</option>
				</select>
			</div>

			<div class="form-group">
				<label for="event_type">Event Type</label>
				<select id="event_type" name="event_type">
					<option value="concert">Concert</option>
					<option value="festival">Festival</option>
					<option value="show">Show</option>
				</select>
			</div>

			<div class="form-group full-width">
				<label for="description">Description</label>
				<textarea id="description" name="description" rows="4"></textarea>
			</div>

			<!-- Image Upload Section -->
			<div class="form-group full-width">
				<label for="event_image">Upload Event Image</label>
				<input type="file" id="event_image" name="event_image" accept="image/*">
				<small class="form-help">Supported formats: JPG, JPEG, PNG, GIF, WebP. Max size: 5MB</small>
			</div>

			<div class="form-group full-width">
				<label for="image_url">Or Enter Image URL</label>
				<input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
				<small class="form-help">If you upload an image, it will override the URL</small>
			</div>
		</div>

		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Add Event</button>
			<button type="reset" class="btn btn-secondary">Reset Form</button>
		</div>
	</form>


	<!-- Existing Events List -->
	<div class="admin-section">
		<h2>Existing Events</h2>
	</div>

	<?php
	// Fetch all events
	$events_sql = "SELECT c.*, v.venueName, v.venueCity, v.venueCountry 
                   FROM concert c 
                   LEFT JOIN venue v ON c.venueID = v.venueID 
                   ORDER BY c.concertDate DESC";
	$events_result = $conn->query($events_sql);

	if ($events_result && $events_result->num_rows > 0): ?>
		<div class="events-table">
			<table class="admin-table">
				<thead>
					<tr>
						<th>Event Name</th>
						<th>Date</th>
						<th>Venue</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($event = $events_result->fetch_assoc()): ?>
						<tr>
							<td><?php echo htmlspecialchars($event['concertName']); ?></td>
							<td><?php echo date('M j, Y', strtotime($event['concertDate'])); ?></td>
							<td><?php echo htmlspecialchars($event['venueName'] . ', ' . $event['venueCity']); ?></td>
							<td>
								<span class="status-badge <?php echo $event['status']; ?>">
									<?php echo ucfirst($event['status']); ?>
								</span>
							</td>
							<td class="actions">
								<a href="editevent.php?id=<?php echo $event['concertID']; ?>"
									class="btn btn-small btn-primary">Edit</a>
								<a href="deleteevent.php?id=<?php echo $event['concertID']; ?>" class="btn btn-small btn-danger"
									onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?>
		<div class="message info">No events found. Create your first event above.</div>
	<?php endif; ?>


	<script>
		document.getElementById('concert_date').min = new Date().toISOString().split('T')[0];

		// Show preview of selected image
		document.getElementById('event_image').addEventListener('change', function (e) {
			const file = e.target.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function (e) {
					// You can add image preview functionality here if needed
					console.log('Image selected:', file.name);
				}
				reader.readAsDataURL(file);
			}
		});
	</script>
</body>

</html>