<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

$message = '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build query
$sql = "SELECT userID, userFullName, userEmail, userPhoneNumber, userType, 
               (SELECT COUNT(*) FROM bookings WHERE bookings.userID = users.userID) as booking_count
        FROM users WHERE 1=1";

if (!empty($search)) {
	$sql .= " AND (userFullName LIKE ? OR userEmail LIKE ?)";
}

if ($filter === 'admin') {
	$sql .= " AND userType = 'admin'";
} elseif ($filter === 'user') {
	$sql .= " AND userType = 'user'";
}

$sql .= " ORDER BY userID DESC";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
	$search_term = "%$search%";
	$stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$users = $stmt->get_result();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['update_role'])) {
		$userID = $_POST['user_id'];
		$newRole = $_POST['user_type'];

		$update_sql = "UPDATE users SET userType = ? WHERE userID = ?";
		$update_stmt = $conn->prepare($update_sql);
		$update_stmt->bind_param("si", $newRole, $userID);

		if ($update_stmt->execute()) {
			$message = "success:User role updated successfully!";
		} else {
			$message = "error:Error updating user role.";
		}
	}

	if (isset($_POST['delete_user'])) {
		$userID = $_POST['user_id'];

		// Start transaction
		$conn->begin_transaction();

		try {
			// Delete user's bookings
			$conn->query("DELETE FROM bookings WHERE userID = $userID");
			// Delete user's payments
			$conn->query("DELETE FROM payment WHERE userID = $userID");
			// Delete user
			$conn->query("DELETE FROM users WHERE userID = $userID");

			$conn->commit();
			$message = "success:User deleted successfully!";
		} catch (Exception $e) {
			$conn->rollback();
			$message = "error:Error deleting user: " . $e->getMessage();
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Users - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-container">
		<div class="admin-section">
			<h2>User Management</h2>
			<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
		</div>
	</div>

	<?php if (!empty($message)):
		list($type, $text) = explode(':', $message, 2); ?>
		<div class="message <?php echo $type; ?>"><?php echo $text; ?></div>
	<?php endif; ?>

	<!-- User Statistics -->
	<div class="stats-grid">
		<?php
		$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
		$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE userType = 'admin'")->fetch_assoc()['count'];
		$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
		?>
		<div class="stat-card">
			<h3>Total Users</h3>
			<p class="stat-number"><?php echo $total_users; ?></p>
		</div>
		<div class="stat-card">
			<h3>Admins</h3>
			<p class="stat-number"><?php echo $total_admins; ?></p>
		</div>
		<div class="stat-card">
			<h3>Total Bookings</h3>
			<p class="stat-number"><?php echo $total_bookings; ?></p>
		</div>
	</div>

	<!-- Search and Filter -->
	<div class="admin-section">
		<form method="GET" class="search-form">
			<input type="text" name="search" placeholder="Search users..."
				value="<?php echo htmlspecialchars($search); ?>">
			<select name="filter">
				<option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
				<option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
				<option value="user" <?php echo $filter === 'user' ? 'selected' : ''; ?>>Regular Users</option>
			</select>
			<button type="submit" class="btn btn-primary">Search</button>
			<a href="manageusers.php" class="btn btn-secondary">Clear</a>
		</form>
	</div>

	<!-- Users Table -->
	<div class="admin-section">
		<h2>Users List</h2>
		<div class="admin-table-container">
			<table class="admin-table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Full Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Role</th>
						<th>Bookings</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($users->num_rows > 0): ?>
						<?php while ($user = $users->fetch_assoc()): ?>
							<tr>
								<td><?php echo $user['userID']; ?></td>
								<td><?php echo htmlspecialchars($user['userFullName']); ?></td>
								<td><?php echo htmlspecialchars($user['userEmail']); ?></td>
								<td><?php echo htmlspecialchars($user['userPhoneNumber'] ?? 'N/A'); ?></td>
								<td>
									<form method="POST" class="inline-form">
										<input type="hidden" name="user_id" value="<?php echo $user['userID']; ?>">
										<select name="user_type" onchange="this.form.submit()">
											<option value="user" <?php echo $user['userType'] === 'user' ? 'selected' : ''; ?>>
												User</option>
											<option value="admin" <?php echo $user['userType'] === 'admin' ? 'selected' : ''; ?>>
												Admin</option>
										</select>
										<input type="hidden" name="update_role" value="1">
									</form>
								</td>
								<td><?php echo $user['booking_count']; ?></td>
								<td>
									<div class="action-buttons">
										<a href="viewuser.php?id=<?php echo $user['userID']; ?>"
											class="btn btn-small btn-secondary">View</a>
										<form method="POST"
											onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
											<input type="hidden" name="user_id" value="<?php echo $user['userID']; ?>">
											<button type="submit" name="delete_user"
												class="btn btn-small btn-danger">Delete</button>
										</form>
									</div>
								</td>
							</tr>
						<?php endwhile; ?>
					<?php else: ?>
						<tr>
							<td colspan="7" class="no-data">No users found.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</body>

</html>