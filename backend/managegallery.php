<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

$message = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_image'])) {
	$upload_dir = '../visuals/gallery/';

	// Create directory if it doesn't exist
	if (!is_dir($upload_dir)) {
		mkdir($upload_dir, 0755, true);
	}

	$file_tmp = $_FILES['gallery_image']['tmp_name'];
	$file_size = $_FILES['gallery_image']['size'];
	$file_error = $_FILES['gallery_image']['error'];

	// Check for upload errors
	if ($file_error !== UPLOAD_ERR_OK) {
		$message = "error:File upload error: " . $file_error;
	}
	// Check if file is actually an image
	elseif (!getimagesize($file_tmp)) {
		$message = "error:File is not a valid image.";
	}
	// Check file size (50MB limit)
	elseif ($file_size > 50000000) {
		$message = "error:File is too large. Maximum size is 50MB.";
	} else {
		// Generate unique filename
		$file_extension = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
		$filename = uniqid() . '.' . $file_extension;
		$target_file = $upload_dir . $filename;

		// Move uploaded file
		if (move_uploaded_file($file_tmp, $target_file)) {
			// Get form data
			$caption = $_POST['caption'] ?? '';
			$concertID = !empty($_POST['concert_id']) ? $_POST['concert_id'] : NULL;

			// Insert into database
			$insert_sql = "INSERT INTO gallery (image_url, caption, concertID) VALUES (?, ?, ?)";
			$stmt = $conn->prepare($insert_sql);

			// The path stored in database should be relative to the root
			$db_image_path = 'visuals/gallery/' . $filename;

			$stmt->bind_param("ssi", $db_image_path, $caption, $concertID);

			if ($stmt->execute()) {
				$message = "success:Image uploaded successfully!";
			} else {
				$message = "error:Error saving image to database: " . $conn->error;
				// Remove the uploaded file if database insert failed
				unlink($target_file);
			}
			$stmt->close();
		} else {
			$message = "error:Error uploading file.";
		}
	}
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
	$galleryID = intval($_POST['gallery_id']);

	// Get image URL first
	$get_sql = "SELECT image_url FROM gallery WHERE galleryID = ?";
	$get_stmt = $conn->prepare($get_sql);
	$get_stmt->bind_param("i", $galleryID);
	$get_stmt->execute();
	$result = $get_stmt->get_result();

	if ($image = $result->fetch_assoc()) {
		// Delete from database
		$delete_sql = "DELETE FROM gallery WHERE galleryID = ?";
		$delete_stmt = $conn->prepare($delete_sql);
		$delete_stmt->bind_param("i", $galleryID);

		if ($delete_stmt->execute()) {
			// Delete physical file
			if ($image['image_url'] && file_exists('../' . $image['image_url'])) {
				unlink('../' . $image['image_url']);
			}
			$message = "success:Image deleted successfully!";
		} else {
			$message = "error:Error deleting image from database.";
		}
		$delete_stmt->close();
	} else {
		$message = "error:Image not found in database.";
	}
	$get_stmt->close();
}

// Fetch concerts for dropdown
$concerts = $conn->query("SELECT concertID, concertName FROM concert ORDER BY concertDate DESC");

// Fetch gallery images
$gallery_sql = "SELECT g.*, c.concertName 
                FROM gallery g 
                LEFT JOIN concert c ON g.concertID = c.concertID 
                ORDER BY g.uploaded_at DESC";
$gallery = $conn->query($gallery_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Gallery - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-section">
		<h2>Gallery Management</h2>
		<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
	</div>

	<?php if (!empty($message)):
		list($type, $text) = explode(':', $message, 2); ?>
		<div class="message <?php echo $type; ?>"><?php echo $text; ?></div>
	<?php endif; ?>

	<!-- Upload Form -->
	<div class="admin-section">
		<h2>Upload New Image</h2>
		<form method="POST" enctype="multipart/form-data" class="admin-form">
			<div class="form-grid">
				<div class="form-group">
					<label for="concert_id">Associated Event (Optional)</label>
					<select id="concert_id" name="concert_id">
						<option value="">-- No Event --</option>
						<?php while ($concert = $concerts->fetch_assoc()): ?>
							<option value="<?php echo $concert['concertID']; ?>">
								<?php echo htmlspecialchars($concert['concertName']); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>

				<div class="form-group full-width">
					<label for="gallery_image">Select Image</label>
					<input type="file" id="gallery_image" name="gallery_image" accept="image/*" required>
					<small class="form-help">Max file size: 50MB. Supported formats: JPG, PNG, GIF, WebP</small>
				</div>

				<div class="form-group full-width">
					<label for="caption">Caption</label>
					<input type="text" id="caption" name="caption" placeholder="Image caption...">
				</div>
			</div>

			<div class="form-actions">
				<button type="submit" name="upload_image" class="btn btn-primary">Upload Image</button>
			</div>
		</form>
	</div>

	<!-- Gallery Images -->
	<div class="admin-section">
		<h2>Gallery Images (<?php echo $gallery->num_rows; ?>)</h2>
		<div class="gallery-grid">
			<?php if ($gallery->num_rows > 0): ?>
				<?php while ($image = $gallery->fetch_assoc()): ?>
					<div class="gallery-item">
						<img src="../<?php echo htmlspecialchars($image['image_url']); ?>"
							alt="<?php echo htmlspecialchars($image['caption']); ?>"
							onerror="this.src='../visuals/default.jpg'">
						<div class="gallery-info">
							<p><strong>Event:</strong> <?php echo htmlspecialchars($image['concertName'] ?? 'N/A'); ?></p>
							<p><strong>Caption:</strong> <?php echo htmlspecialchars($image['caption'] ?? 'No caption'); ?>
							</p>
							<p><strong>Uploaded:</strong>
								<?php echo date('M j, Y g:i A', strtotime($image['uploaded_at'])); ?></p>
						</div>
						<div class="gallery-actions">
							<form method="POST" onsubmit="return confirm('Are you sure you want to delete this image?');">
								<input type="hidden" name="gallery_id" value="<?php echo $image['galleryID']; ?>">
								<button type="submit" name="delete_image" class="btn btn-danger btn-small">Delete</button>
							</form>
						</div>
					</div>
				<?php endwhile; ?>
			<?php else: ?>
				<div class="no-data">No gallery images found. Upload your first image above.</div>
			<?php endif; ?>
		</div>
	</div>
	</div>
</body>

</html>