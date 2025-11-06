<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$messageID = $_POST['id'] ?? null;
	$status = $_POST['status'] ?? '';
	$action = $_POST['action'] ?? '';

	if ($messageID && in_array($status, ['read', 'replied'])) {
		// Update the message status
		$stmt = $conn->prepare("UPDATE contact SET status = ? WHERE messageID = ?");
		$stmt->bind_param("si", $status, $messageID);

		if ($stmt->execute()) {
			// If this is a reply action, you can also send an email here
			if ($action === 'reply' && $status === 'replied') {
				$userEmail = $_POST['email'] ?? '';
				$subject = $_POST['subject'] ?? '';
				$messageContent = $_POST['message'] ?? '';

				// Here you would implement actual email sending
				// For now, we'll just log it or you can implement your email function
				error_log("Reply sent to: $userEmail, Subject: $subject, Message: $messageContent");

				// Example of sending email (uncomment and configure if you have email setup)
				/*
				$to = $userEmail;
				$headers = "From: admin@gempaxexpo.com\r\n";
				$headers .= "Reply-To: admin@gempaxexpo.com\r\n";
				$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

				if (mail($to, $subject, $messageContent, $headers)) {
					error_log("Email sent successfully to: $userEmail");
				} else {
					error_log("Failed to send email to: $userEmail");
				}
				*/
			}

			echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
		} else {
			echo json_encode(['success' => false, 'message' => 'Error updating status']);
		}

		$stmt->close();
	} else {
		echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
	}
} else {
	echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>