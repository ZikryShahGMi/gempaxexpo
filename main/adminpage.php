<?php
session_start();
include 'db_connect.php';
include 'admincheck.php';

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
    <!-- Header Section -->
    <header class="site-header">
        <div class="container nav-container">
            <h1 class="logo"><a href="index.php">GEMPAX EXPO</a></h1>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="adminpage.php" class="active">Admin Dashboard</a></li>
                    <li><a href="adminmanagementpage.php">Management Page</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Show Dashboard only if logged in -->
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- If user is logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">
                            <?= htmlspecialchars($_SESSION['fullname']) ?> ▾
                        </button>
                        <div class="dropdown-content">
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- If user is NOT logged in -->
                    <div class="dropdown">
                        <button class="dropbtn">Account ▾</button>
                        <div class="dropdown-content">
                            <a href="signin.php">Sign In</a>
                            <a href="signup.php">Sign Up</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button class="lang-btn">MS</button>
        </div>
    </header>

    <div class="container-section">
        <h2>Admin Dashboard Overview</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Quick Stats Section -->
        <div class="admin-section">
            <h2>Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Users</h4>
                    <p class="stat-number"><?php echo $users->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Concerts</h4>
                    <p class="stat-number"><?php echo $concerts->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Bookings</h4>
                    <p class="stat-number"><?php echo $bookings->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Payments</h4>
                    <p class="stat-number"><?php echo $payments->num_rows; ?></p>
                </div>
            </div>
        </div>

        <!-- Concert Management Section -->
        <div class="admin-section">
            <h2>Current Concerts</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Concert Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Ticket Price</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Reset concerts pointer
                    $concerts->data_seek(0);
                    while ($concert = $concerts->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $concert['concertID']; ?></td>
                            <td>
                                <strong><?php echo $concert['concertName']; ?></strong>
                                <?php if (!empty($concert['event_type'])): ?>
                                    <br><small>Type: <?php echo ucfirst($concert['event_type']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $concert['concertDate']; ?></td>
                            <td><?php echo $concert['concertTime']; ?></td>
                            <td>$<?php echo $concert['ticketPrice']; ?></td>
                            <td><?php echo $concert['venueName']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $concert['status'] ?? 'upcoming'; ?>">
                                    <?php echo ucfirst($concert['status'] ?? 'upcoming'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="concert_id" value="<?php echo $concert['concertID']; ?>">
                                        <button type="submit" name="delete_concert" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this concert?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Bookings Management Section -->
        <div class="admin-section">
            <h2>Bookings Management</h2>
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
                                        <option value="paid" <?php echo $booking['paymentStatus'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
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

        <!-- Users Management Section -->
        <div class="admin-section">
            <h2>Users Management</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>User Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['userID']; ?></td>
                            <td><?php echo $user['userFullName']; ?></td>
                            <td><?php echo $user['userEmail']; ?></td>
                            <td><?php echo $user['userPhoneNumber']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($user['userType']); ?>">
                                    <?php echo ucfirst($user['userType']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Payments Management Section -->
        <div class="admin-section">
            <h2>Payments Management</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>User</th>
                        <th>Booking ID</th>
                        <th>Payment Date</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $payment['paymentID']; ?></td>
                            <td><?php echo $payment['userFullName']; ?></td>
                            <td><?php echo $payment['bookingID']; ?></td>
                            <td><?php echo $payment['paymentDate']; ?></td>
                            <td>$<?php echo $payment['totalAmount']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($payment['paymentStatus']); ?>">
                                    <?php echo ucfirst($payment['paymentStatus']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['paymentID']; ?>">
                                    <select name="payment_status">
                                        <option value="pending" <?php echo $payment['paymentStatus'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo $payment['paymentStatus'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="failed" <?php echo $payment['paymentStatus'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $payment['paymentStatus'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                    <button type="submit" name="update_payment_status" class="btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p>© GEMPAX EXPO — 2025 | Admin Dashboard</p>
        </div>
    </footer>
</body>

</html>
<?php $conn->close(); ?>