<?php
// admincheck.php - ENHANCED VERSION
include 'db_connect.php';

function isAdmin()
{
	return isset($_SESSION['user_id']) &&
		(($_SESSION['userType'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'admin');
}

function requireAdmin()
{
	if (!isAdmin()) {
		$_SESSION['error'] = "Access denied. Admin privileges required.";
		header("Location: ../index.php");
		exit;
	}
}

// Check if user has permission for specific actions
function hasPermission($action)
{
	if (!isAdmin())
		return false;

	$allowedActions = ['manage_events', 'manage_users', 'manage_gallery', 'view_reports'];
	return in_array($action, $allowedActions);
}
?>