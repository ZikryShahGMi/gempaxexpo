<?php
include 'db_connect.php';

function isAdmin()
{
	return isset($_SESSION['user_id']) &&
		(($_SESSION['userType'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'admin');
}

function requireAdmin()
{
	if (!isAdmin()) {
		header("Location: index.php");
		exit;
	}
}
?>