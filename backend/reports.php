<?php
session_start();
include '../main/db_connect.php';
include '../main/admincheck.php';
requireAdmin();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Overall Statistics
$total_revenue = $conn->query("SELECT SUM(totalAmount) as revenue FROM bookings WHERE paymentStatus = 'paid'")->fetch_assoc()['revenue'];
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_events = $conn->query("SELECT COUNT(*) as count FROM concert")->fetch_assoc()['count'];

// Revenue by event
$revenue_by_event = $conn->query("
    SELECT c.concertName, SUM(b.totalAmount) as revenue, COUNT(b.bookingID) as bookings
    FROM bookings b
    JOIN concert c ON b.concertID = c.concertID
    WHERE b.paymentStatus = 'paid'
    GROUP BY c.concertID
    ORDER BY revenue DESC
");

// Recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, c.concertName, u.userFullName
    FROM bookings b
    JOIN concert c ON b.concertID = c.concertID
    LEFT JOIN users u ON b.userID = u.userID
    ORDER BY b.bookingDate DESC
    LIMIT 10
");

// Booking status distribution
$status_distribution = $conn->query("
    SELECT paymentStatus, COUNT(*) as count
    FROM bookings
    GROUP BY paymentStatus
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reports & Analytics - GEMPAX EXPO</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../styling/styles.css">
	<link rel="stylesheet" href="../styling/admin-styles.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		.charts-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 2rem;
			margin-bottom: 2rem;
		}

		.chart-container {
			background: var(--card-bg);
			padding: 1.5rem;
			border-radius: 12px;
			border: 1px solid var(--border-color);
			height: 300px;
			/* Fixed height for consistency */
			display: flex;
			flex-direction: column;
		}

		.chart-container h3 {
			margin: 0 0 1rem 0;
			color: var(--text-color);
			font-size: 1.1rem;
		}

		.chart-wrapper {
			flex: 1;
			position: relative;
			min-height: 0;
			/* Important for flexbox sizing */
		}

		.chart-wrapper canvas {
			max-width: 100% !important;
			max-height: 100% !important;
			width: auto !important;
			height: auto !important;
		}

		/* Make the date filter more compact */
		.date-filter {
			display: flex;
			gap: 0.5rem;
			align-items: center;
			flex-wrap: wrap;
		}

		.date-filter input {
			padding: 0.5rem;
			border: 1px solid var(--border-color);
			border-radius: 6px;
			background: var(--bg-color);
			color: var(--text-color);
			font-size: 0.9rem;
		}

		.date-filter span {
			color: var(--text-muted);
			font-size: 0.9rem;
		}

		.category-badge {
			background: var(--accent-color);
			color: white;
			padding: 0.25rem 0.5rem;
			border-radius: 4px;
			font-size: 0.8rem;
			font-weight: 500;
		}

		.action-buttons {
			display: flex;
			gap: 0.5rem;
		}

		.btn-sm {
			padding: 0.25rem 0.75rem;
			font-size: 0.8rem;
		}

		.status-new {
			background-color: #007bff;
			color: white;
		}

		.status-read {
			background-color: #28a745;
			color: white;
		}

		.status-replied {
			background-color: #6c757d;
			color: white;
		}

		/* Responsive adjustments */
		@media (max-width: 768px) {
			.charts-grid {
				grid-template-columns: 1fr;
			}

			.chart-container {
				height: 250px;
			}

			.date-filter {
				flex-direction: column;
				align-items: stretch;
			}
		}
	</style>
</head>

<body>

	<?php include '../main/admin-header.php'; ?>

	<div class="admin-container">
		<div class="admin-section">
			<h2>Reports & Analytics</h2>
			<div class="header-actions">
				<form method="GET" class="date-filter">
					<input type="date" name="start_date" value="<?php echo $start_date; ?>">
					<span>to</span>
					<input type="date" name="end_date" value="<?php echo $end_date; ?>">
					<button type="submit" class="btn btn-primary">Apply</button>
				</form>
				<a href="../main/adminmanagementpage.php" class="btn btn-secondary">← Dashboard</a>
			</div>
		</div>

		<!-- Key Metrics -->
		<div class="stats-grid">
			<div class="stat-card">
				<h3>Total Revenue</h3>
				<p class="stat-number">RM <?php echo number_format($total_revenue ?? 0, 2); ?></p>
			</div>
			<div class="stat-card">
				<h3>Total Bookings</h3>
				<p class="stat-number"><?php echo $total_bookings; ?></p>
			</div>
			<div class="stat-card">
				<h3>Registered Users</h3>
				<p class="stat-number"><?php echo $total_users; ?></p>
			</div>
			<div class="stat-card">
				<h3>Events</h3>
				<p class="stat-number"><?php echo $total_events; ?></p>
			</div>
		</div>

		<div class="charts-grid">
			<!-- Revenue by Event -->
			<div class="chart-container">
				<h3>Revenue by Event</h3>
				<div class="chart-wrapper">
					<canvas id="revenueChart"></canvas>
				</div>
			</div>

			<!-- Booking Status Distribution -->
			<div class="chart-container">
				<h3>Booking Status</h3>
				<div class="chart-wrapper">
					<canvas id="statusChart"></canvas>
				</div>
			</div>
		</div>

		<!-- Revenue by Event Table -->
		<div class="admin-section">
			<h2>Event Performance</h2>
			<div class="admin-table-container">
				<table class="admin-table">
					<thead>
						<tr>
							<th>Event</th>
							<th>Revenue (RM)</th>
							<th>Bookings</th>
							<th>Average Revenue</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($revenue_by_event->num_rows > 0): ?>
							<?php while ($event = $revenue_by_event->fetch_assoc()): ?>
								<tr>
									<td><?php echo htmlspecialchars($event['concertName']); ?></td>
									<td>RM <?php echo number_format($event['revenue'] ?? 0, 2); ?></td>
									<td><?php echo $event['bookings']; ?></td>
									<td>RM
										<?php echo number_format(($event['revenue'] ?? 0) / max($event['bookings'], 1), 2); ?>
									</td>
								</tr>
							<?php endwhile; ?>
						<?php else: ?>
							<tr>
								<td colspan="4" class="no-data">No revenue data available.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Recent Bookings -->
		<div class="admin-section">
			<h2>Recent Bookings</h2>
			<div class="admin-table-container">
				<table class="admin-table">
					<thead>
						<tr>
							<th>Booking ID</th>
							<th>Event</th>
							<th>Customer</th>
							<th>Amount (RM)</th>
							<th>Status</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($recent_bookings->num_rows > 0): ?>
							<?php while ($booking = $recent_bookings->fetch_assoc()): ?>
								<tr>
									<td><?php echo $booking['bookingID']; ?></td>
									<td><?php echo htmlspecialchars($booking['concertName']); ?></td>
									<td><?php echo htmlspecialchars($booking['customerName'] ?? $booking['userFullName'] ?? 'Guest'); ?>
									</td>
									<td>RM <?php echo number_format($booking['totalAmount'] ?? 0, 2); ?></td>
									<td>
										<span class="status-badge status-<?php echo strtolower($booking['paymentStatus']); ?>">
											<?php echo ucfirst($booking['paymentStatus']); ?>
										</span>
									</td>
									<td><?php echo date('M j, Y', strtotime($booking['bookingDate'])); ?></td>
								</tr>
							<?php endwhile; ?>
						<?php else: ?>
							<tr>
								<td colspan="6" class="no-data">No recent bookings.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Contact Messages -->
			<div class="admin-section">
				<h2>User Reports</h2>
				<div class="admin-table-container">
					<table class="admin-table">
						<thead>
							<tr>
								<th>ID</th>
								<th>Name</th>
								<th>Email</th>
								<th>Subject</th>
								<th>Category</th>
								<th>Date</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php
							// Fetch contact messages
							$contact_messages = $conn->query("
                    SELECT messageID, userName, userEmail, messageSubject, messageCategory, messageDate, status
                    FROM contact 
                    ORDER BY messageDate DESC
                ");

							if ($contact_messages->num_rows > 0): ?>
								<?php while ($message = $contact_messages->fetch_assoc()): ?>
									<tr>
										<td><?php echo $message['messageID']; ?></td>
										<td><?php echo htmlspecialchars($message['userName']); ?></td>
										<td><?php echo htmlspecialchars($message['userEmail']); ?></td>
										<td><?php echo htmlspecialchars($message['messageSubject']); ?></td>
										<td>
											<span class="category-badge">
												<?php echo ucfirst($message['messageCategory']); ?>
											</span>
										</td>
										<td><?php echo date('M j, Y g:i A', strtotime($message['messageDate'])); ?></td>
										<td>
											<span class="status-badge status-<?php echo strtolower($message['status']); ?>">
												<?php echo ucfirst($message['status']); ?>
											</span>
										</td>
										<td>
											<div class="action-buttons">
												<button class="btn btn-sm btn-primary"
													onclick="viewMessage(<?php echo $message['messageID']; ?>)"
													title="View Message">
													View
												</button>
												<?php if ($message['status'] === 'new'): ?>
													<button class="btn btn-sm btn-success"
														onclick="markAsRead(<?php echo $message['messageID']; ?>)"
														title="Mark as Read">
														Mark Read
													</button>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endwhile; ?>
							<?php else: ?>
								<tr>
									<td colspan="8" class="no-data">No contact messages found.</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	</div>

	<script>
		// Revenue Chart
		const revenueCtx = document.getElementById('revenueChart').getContext('2d');
		const revenueChart = new Chart(revenueCtx, {
			type: 'bar',
			data: {
				labels: [<?php
				$revenue_by_event->data_seek(0);
				$labels = [];
				while ($event = $revenue_by_event->fetch_assoc()) {
					$labels[] = "'" . addslashes($event['concertName']) . "'";
				}
				echo implode(', ', $labels);
				?>],
				datasets: [{
					label: 'Revenue (RM)',
					data: [<?php
					$revenue_by_event->data_seek(0);
					$data = [];
					while ($event = $revenue_by_event->fetch_assoc()) {
						$data[] = $event['revenue'] ?? 0;
					}
					echo implode(', ', $data);
					?>],
					backgroundColor: 'rgba(74, 144, 226, 0.5)',
					borderColor: 'rgba(74, 144, 226, 1)',
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							callback: function (value) {
								return 'RM ' + value.toLocaleString();
							}
						}
					},
					x: {
						ticks: {
							maxRotation: 45,
							minRotation: 45
						}
					}
				}
			}
		});

		// Message Management Functions
		function viewMessage(messageID) {
			// You can implement a modal or redirect to view the full message
			window.location.href = `view_message.php?id=${messageID}`;
		}

		function markAsRead(messageID) {
			if (confirm('Mark this message as read?')) {
				fetch(`update_message_status.php?id=${messageID}&status=read`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					}
				})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							location.reload(); // Reload to show updated status
						} else {
							alert('Error updating status: ' + data.message);
						}
					})
					.catch(error => {
						console.error('Error:', error);
						alert('Error updating status');
					});
			}
		}

		// Function to reply to message (you can add this to your action buttons)
		function replyToMessage(messageID, userEmail, currentSubject = '') {
			const subject = prompt('Enter reply subject:', 'Re: ' + currentSubject);
			if (subject === null) return; // User cancelled

			const message = prompt('Enter your reply message:');
			if (message === null) return; // User cancelled

			if (subject && message) {
				// Show loading state
				const replyBtn = event.target;
				const originalText = replyBtn.textContent;
				replyBtn.textContent = 'Sending...';
				replyBtn.disabled = true;

				fetch(`handle_reply.php`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: `messageID=${messageID}&userEmail=${encodeURIComponent(userEmail)}&subject=${encodeURIComponent(subject)}&message=${encodeURIComponent(message)}`
				})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							alert('✓ Reply sent successfully!');
							location.reload(); // Reload to show updated status
						} else {
							alert('✗ Error: ' + data.message);
							// Reset button
							replyBtn.textContent = originalText;
							replyBtn.disabled = false;
						}
					})
					.catch(error => {
						console.error('Error:', error);
						alert('✗ Network error sending reply');
						// Reset button
						replyBtn.textContent = originalText;
						replyBtn.disabled = false;
					});
			}
		}

		// Status Chart
		const statusCtx = document.getElementById('statusChart').getContext('2d');
		const statusChart = new Chart(statusCtx, {
			type: 'pie',
			data: {
				labels: [<?php
				$status_distribution->data_seek(0);
				$labels = [];
				while ($status = $status_distribution->fetch_assoc()) {
					$labels[] = "'" . ucfirst($status['paymentStatus']) . "'";
				}
				echo implode(', ', $labels);
				?>],
				datasets: [{
					data: [<?php
					$status_distribution->data_seek(0);
					$data = [];
					while ($status = $status_distribution->fetch_assoc()) {
						$data[] = $status['count'];
					}
					echo implode(', ', $data);
					?>],
					backgroundColor: [
						'rgba(255, 159, 64, 0.7)',
						'rgba(54, 162, 235, 0.7)',
						'rgba(255, 99, 132, 0.7)',
						'rgba(75, 192, 192, 0.7)',
						'rgba(153, 102, 255, 0.7)'
					],
					borderColor: [
						'rgba(255, 159, 64, 1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 99, 132, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)'
					],
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							boxWidth: 12,
							padding: 15
						}
					}
				}
			}
		});

		// Make charts responsive to window resize
		window.addEventListener('resize', function () {
			revenueChart.resize();
			statusChart.resize();
		});
	</script>
</body>

</html>