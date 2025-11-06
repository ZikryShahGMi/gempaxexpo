<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

$messageID = $_GET['id'] ?? null;

if ($messageID) {
	$stmt = $conn->prepare("SELECT * FROM contact WHERE messageID = ?");
	$stmt->bind_param("i", $messageID);
	$stmt->execute();
	$message = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	// Mark as read when viewing
	if ($message && $message['status'] === 'new') {
		$updateStmt = $conn->prepare("UPDATE contact SET status = 'read' WHERE messageID = ?");
		$updateStmt->bind_param("i", $messageID);
		$updateStmt->execute();
		$updateStmt->close();
	}
}

if (!$message) {
	header('Location: reports.php');
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View Message - GEMPAX EXPO</title>
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>
	<?php include '../main/admin-header.php'; ?>

	<div class="admin-container">
		<div class="admin-section">
			<div class="header-actions">
				<a href="reports.php" class="btn btn-secondary">← Back to Reports</a>
			</div>

			<h2>Message Details</h2>

			<div class="message-details">
				<div class="detail-row">
					<strong>From:</strong> <?php echo htmlspecialchars($message['userName']); ?>
				</div>
				<div class="detail-row">
					<strong>Email:</strong> <?php echo htmlspecialchars($message['userEmail']); ?>
				</div>
				<div class="detail-row">
					<strong>Subject:</strong> <?php echo htmlspecialchars($message['messageSubject']); ?>
				</div>
				<div class="detail-row">
					<strong>Category:</strong>
					<span class="category-badge"><?php echo ucfirst($message['messageCategory']); ?></span>
				</div>
				<div class="detail-row">
					<strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($message['messageDate'])); ?>
				</div>
				<div class="detail-row">
					<strong>Status:</strong>
					<span class="status-badge status-<?php echo strtolower($message['status']); ?>">
						<?php echo ucfirst($message['status']); ?>
					</span>
				</div>
				<div class="detail-row message-content">
					<strong>Message:</strong>
					<div class="message-text">
						<?php echo nl2br(htmlspecialchars($message['messageContent'])); ?>
					</div>
				</div>
			</div>

			<div class="action-buttons" style="margin-top: 2rem;">
				<button class="btn btn-primary"
					onclick="replyToMessage(<?php echo $message['messageID']; ?>, '<?php echo $message['userEmail']; ?>')">
					Reply to Message
				</button>
				<?php if ($message['status'] === 'new'): ?>
					<button class="btn btn-success" onclick="markAsRead(<?php echo $message['messageID']; ?>)">
						Mark as Read
					</button>
				<?php endif; ?>
				<a href="reports.php" class="btn btn-secondary">Close</a>
			</div>
		</div>
	</div>

	<script>
		// Include the same functions from reports.php
		function markAsRead(messageID) {
			if (confirm('Mark this message as read?')) {
				fetch(`update_message_status.php?id=${messageID}&status=read`, {
					method: 'POST'
				})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							location.reload();
						} else {
							alert('Error updating status');
						}
					});
			}
		}

		function replyToMessage(messageID, userEmail) {
			const subject = prompt('Enter reply subject:', 'Re: <?php echo addslashes($message['messageSubject']); ?>');
			if (subject) {
				const message = prompt('Enter your reply message:');
				if (message) {
					// Implement actual email sending here
					alert('Reply would be sent to: ' + userEmail + '\n\nSubject: ' + subject + '\nMessage: ' + message);

					// Update status to replied
					fetch(`update_message_status.php?id=${messageID}&status=replied`, {
						method: 'POST'
					})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								location.reload();
							}
						});
				}
			}
		}
	</script>
</body>

</html>