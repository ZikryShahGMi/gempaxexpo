<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

$message = '';
$concertID = $_GET['id'] ?? null;

if (!$concertID) {
	header('Location: manageevents.php');
	exit;
}

// Fetch event data
$event_sql = "SELECT * FROM concert WHERE concertID = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $concertID);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();

if (!$event) {
	$message = "error:Event not found.";
}

// Fetch venues for dropdown
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
			} elseif ($_FILES['event_image']['size'] > 5000000) { // 5MB limit
				$message = "error:File is too large. Maximum size is 5MB.";
			} else {
				if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
					$uploaded_image_url = '../visuals/events/' . $filename;

					// Delete old uploaded image if it exists and is not the default
					if (
						!empty($event['image_url']) &&
						strpos($event['image_url'], '../visuals/events/') !== false &&
						file_exists($event['image_url'])
					) {
						unlink($event['image_url']);
					}
				} else {
					$message = "error:Error uploading file.";
				}
			}
		}

		// Only proceed with database update if no upload errors
		if (empty($message) || strpos($message, 'error:') === false) {
			$sql = "UPDATE Concert SET 
                    concertName = ?, 
                    concertDate = ?, 
                    concertTime = ?, 
                    ticketPrice = ?, 
                    venueID = ?, 
                    description = ?, 
                    image_url = ?, 
                    total_tickets = ?, 
                    duration_minutes = ?, 
                    age_restriction = ?, 
                    status = ?, 
                    event_type = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE concertID = ?";

			$stmt = $conn->prepare($sql);
			$stmt->bind_param(
				"sssdisssisssi",
				$concertName,
				$concertDate,
				$concertTime,
				$ticketPrice,
				$venueID,
				$description,
				$uploaded_image_url,
				$total_tickets,
				$duration_minutes,
				$age_restriction,
				$status,
				$event_type,
				$concertID
			);

			if ($stmt->execute()) {
				$message = "success:Event updated successfully!";
				// Refresh event data
				$event_sql = "SELECT * FROM concert WHERE concertID = ?";
				$event_stmt = $conn->prepare($event_sql);
				$event_stmt->bind_param("i", $concertID);
				$event_stmt->execute();
				$event_result = $event_stmt->get_result();
				$event = $event_result->fetch_assoc();
			} else {
				$message = "error:Error updating event: " . $conn->error;
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
	<title>Edit Event - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-section">
		<h2>Edit Event</h2>
		<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
		<a href="manageevents.php" class="btn btn-secondary">← Back to Events</a>
	</div>

	<?php if (!empty($message)):
		list($type, $text) = explode(':', $message, 2); ?>
		<div class="message <?php echo $type; ?>"><?php echo $text; ?></div>
	<?php endif; ?>

	<?php if ($event): ?>
		<form method="POST" class="admin-form" enctype="multipart/form-data">
			<div class="form-grid">
				<div class="form-group">
					<label for="concert_name">Event Name *</label>
					<input type="text" id="concert_name" name="concert_name"
						value="<?php echo htmlspecialchars($event['concertName']); ?>" required>
				</div>

				<div class="form-group">
					<label for="venue_id">Venue *</label>
					<select id="venue_id" name="venue_id" required>
						<option value="">Select Venue</option>
						<?php while ($venue = $venues->fetch_assoc()): ?>
							<option value="<?php echo $venue['venueID']; ?>" <?php echo $venue['venueID'] == $event['venueID'] ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($venue['venueName']); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="concert_date">Event Date *</label>
					<input type="date" id="concert_date" name="concert_date" value="<?php echo $event['concertDate']; ?>"
						required>
				</div>

				<div class="form-group">
					<label for="concert_time">Event Time</label>
					<input type="time" id="concert_time" name="concert_time" value="<?php echo $event['concertTime']; ?>">
				</div>

				<div class="form-group">
					<label for="ticket_price">Base Ticket Price (RM) *</label>
					<input type="number" id="ticket_price" name="ticket_price" step="0.01" min="0"
						value="<?php echo $event['ticketPrice']; ?>" required>
				</div>

				<div class="form-group">
					<label for="total_tickets">Total Tickets</label>
					<input type="number" id="total_tickets" name="total_tickets" min="0"
						value="<?php echo $event['total_tickets']; ?>">
				</div>

				<div class="form-group">
					<label for="duration_minutes">Duration (minutes)</label>
					<input type="number" id="duration_minutes" name="duration_minutes" min="0"
						value="<?php echo $event['duration_minutes'] ?? 180; ?>">
				</div>

				<div class="form-group">
					<label for="age_restriction">Age Restriction</label>
					<select id="age_restriction" name="age_restriction">
						<option value="All ages" <?php echo ($event['age_restriction'] ?? 'All ages') == 'All ages' ? 'selected' : ''; ?>>All ages</option>
						<option value="18+" <?php echo ($event['age_restriction'] ?? '') == '18+' ? 'selected' : ''; ?>>18+
						</option>
						<option value="21+" <?php echo ($event['age_restriction'] ?? '') == '21+' ? 'selected' : ''; ?>>21+
						</option>
					</select>
				</div>

				<div class="form-group">
					<label for="status">Status</label>
					<select id="status" name="status">
						<option value="upcoming" <?php echo ($event['status'] ?? 'upcoming') == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
						<option value="ongoing" <?php echo ($event['status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing
						</option>
						<option value="completed" <?php echo ($event['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>
							Completed</option>
						<option value="cancelled" <?php echo ($event['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>
							Cancelled</option>
					</select>
				</div>

				<div class="form-group">
					<label for="event_type">Event Type</label>
					<select id="event_type" name="event_type">
						<option value="concert" <?php echo ($event['event_type'] ?? 'concert') == 'concert' ? 'selected' : ''; ?>>Concert</option>
						<option value="festival" <?php echo ($event['event_type'] ?? '') == 'festival' ? 'selected' : ''; ?>>
							Festival</option>
						<option value="show" <?php echo ($event['event_type'] ?? '') == 'show' ? 'selected' : ''; ?>>Show
						</option>
					</select>
				</div>

				<div class="form-group full-width">
					<label for="description">Description</label>
					<textarea id="description" name="description"
						rows="4"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
				</div>

				<!-- Current Image Preview -->
				<div class="form-group full-width">
					<label>Current Image</label>
					<?php if (!empty($event['image_url'])): ?>
						<div class="current-image-preview">
							<img src="<?php echo $event['image_url']; ?>" alt="Current event image"
								style="max-width: 300px; max-height: 200px; border-radius: 8px;">
							<small class="form-help">Current image: <?php echo basename($event['image_url']); ?></small>
						</div>
					<?php else: ?>
						<p>No image currently set</p>
					<?php endif; ?>
				</div>

				<!-- Image Upload Section -->
				<div class="form-group full-width">
					<label for="event_image">Upload New Event Image</label>
					<input type="file" id="event_image" name="event_image" accept="image/*">
					<small class="form-help">Supported formats: JPG, JPEG, PNG, GIF, WebP. Max size: 5MB</small>
				</div>

				<div class="form-group full-width">
					<label for="image_url">Or Enter New Image URL</label>
					<input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg"
						value="<?php echo htmlspecialchars($event['image_url'] ?? ''); ?>">
					<small class="form-help">If you upload an image, it will override the URL</small>
				</div>
			</div>

			<div class="form-actions">
				<button type="submit" class="btn btn-primary">Update Event</button>
				<button type="reset" class="btn btn-secondary">Reset Changes</button>
				<a href="manageevents.php" class="btn btn-secondary">Cancel</a>
			</div>
		</form>
	<?php else: ?>
		<div class="message error">Event not found.</div>
	<?php endif; ?>

	<script>
		// Set minimum date to today
		document.getElementById('concert_date').min = new Date().toISOString().split('T')[0];

		// Show preview of selected image
		document.getElementById('event_image').addEventListener('change', function (e) {
			const file = e.target.files[0];
			if (file) {
				const reader = new FileReader();
				reader.onload = function (e) {
					// You can add image preview functionality here if needed
					console.log('New image selected:', file.name);
				}
				reader.readAsDataURL(file);
			}
		});
	</script>
</body>

</html>