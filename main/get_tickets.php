<?php
include('db_connect.php');

header('Content-Type: application/json');

if (isset($_GET['concert_id'])) {
	$concertID = intval($_GET['concert_id']);

	$sql = "SELECT * FROM tickettypes WHERE concertID = ? AND availableQuantity > 0";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $concertID);
	$stmt->execute();
	$result = $stmt->get_result();

	$tickets = [];
	while ($row = $result->fetch_assoc()) {
		$tickets[] = $row;
	}

	echo json_encode($tickets);
} else {
	echo json_encode([]);
}
?>