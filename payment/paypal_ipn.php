<?php
// PayPal IPN (Instant Payment Notification) Handler
include '../main/db_connect.php';

// Read the POST data from PayPal
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();

foreach ($raw_post_array as $keyval) {
	$keyval = explode('=', $keyval);
	if (count($keyval) == 2) {
		$myPost[$keyval[0]] = urldecode($keyval[1]);
	}
}

// Read the IPN message from PayPal and add 'cmd'
$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
	$value = urlencode($value);
	$req .= "&$key=$value";
}

// Post IPN data back to PayPal to validate
$sandbox = true; // Set to false for production
$paypal_url = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

$res = curl_exec($ch);
curl_close($ch);

// Process IPN data if verified
if (strcmp($res, "VERIFIED") == 0) {
	$bookingID = $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$txn_id = $_POST['txn_id'];

	if ($payment_status == 'Completed') {
		// Update booking as paid
		$update_sql = "UPDATE bookings SET paymentStatus = 'paid', bookingStatus = 'confirmed' WHERE bookingID = ?";
		$stmt = $conn->prepare($update_sql);
		$stmt->bind_param("i", $bookingID);
		$stmt->execute();

		// Record payment
		$payment_sql = "INSERT INTO payment (bookingID, paymentStatus, paymentDate, totalAmount) 
                        VALUES (?, 'completed', NOW(), ?)";
		$payment_stmt = $conn->prepare($payment_sql);
		$payment_stmt->bind_param("id", $bookingID, $_POST['mc_gross']);
		$payment_stmt->execute();
	}

	if ($payment_status == 'Canceled_Reversal' || $payment_status == 'Denied' || $payment_status == 'Expired' || $payment_status == 'Failed' || $payment_status == 'Voided') {
		// Update booking as cancelled
		$update_sql = "UPDATE bookings SET paymentStatus = 'cancelled', bookingStatus = 'cancelled' WHERE bookingID = ?";
		$stmt = $conn->prepare($update_sql);
		$stmt->bind_param("i", $bookingID);
		$stmt->execute();
		$stmt->close();

		// Record cancelled payment
		$payment_sql = "INSERT INTO payment (bookingID, paymentStatus, paymentDate, totalAmount) 
                    VALUES (?, 'cancelled', NOW(), ?)";
		$payment_stmt = $conn->prepare($payment_sql);
		$payment_stmt->bind_param("id", $bookingID, $_POST['mc_gross']);
		$payment_stmt->execute();
		$payment_stmt->close();
	}

	// Log the IPN
	file_put_contents('ipn_log.txt', date('Y-m-d H:i:s') . " - IPN: $bookingID - $payment_status\n", FILE_APPEND);
}
?>