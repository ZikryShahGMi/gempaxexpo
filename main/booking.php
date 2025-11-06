<?php
session_start();
include('db_connect.php');

// Fetch events from database
$events_sql = "SELECT concertID, concertName, concertDate, ticketPrice FROM concert WHERE status = 'upcoming' ORDER BY concertDate";
$events_result = $conn->query($events_sql);

// Fetch ticket types from tickettypes table
$ticket_types_sql = "SELECT ticketTypeID, ticketName, ticketPrice, availableQuantity FROM tickettypes WHERE availableQuantity > 0";
$ticket_types_result = $conn->query($ticket_types_sql);

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $concertID = $_POST['concert_id'];
    $ticketTypeID = $_POST['ticket_type'];
    $quantity = $_POST['quantity'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $customerName = $firstName . ' ' . $lastName;

    // Calculate total amount
    $ticket_price_sql = "SELECT ticketPrice, availableQuantity FROM tickettypes WHERE ticketTypeID = ?";
    $ticket_stmt = $conn->prepare($ticket_price_sql);

    if ($ticket_stmt) {
        $ticket_stmt->bind_param("i", $ticketTypeID);
        $ticket_stmt->execute();
        $ticket_result = $ticket_stmt->get_result();
        $ticket_data = $ticket_result->fetch_assoc();

        // Check if enough tickets are available
        if ($quantity > $ticket_data['availableQuantity']) {
            $message = "error:Sorry, only " . $ticket_data['availableQuantity'] . " tickets available for this type.";
        } else {
            $totalAmount = $ticket_data['ticketPrice'] * $quantity;

            // Get user ID if logged in
            $userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

            // Insert booking with all required fields
            $insert_sql = "INSERT INTO bookings (userID, concertID, ticketTypeID, quantity, totalAmount, customerName, customerEmail, customerPhone, paymentStatus, bookingStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt) {
                $insert_stmt->bind_param("iiiidsss", $userID, $concertID, $ticketTypeID, $quantity, $totalAmount, $customerName, $email, $phone);

                if ($insert_stmt->execute()) {
                    $bookingID = $conn->insert_id;

                    // Store booking in session for payment processing
                    $_SESSION['pending_booking'] = $bookingID;
                    $_SESSION['booking_amount'] = $totalAmount;

                    // Get event details for the payment page
                    $event_sql = "SELECT concertName FROM concert WHERE concertID = ?";
                    $event_stmt = $conn->prepare($event_sql);
                    if ($event_stmt) {
                        $event_stmt->bind_param("i", $concertID);
                        $event_stmt->execute();
                        $event_result = $event_stmt->get_result();
                        $event_data = $event_result->fetch_assoc();

                        $_SESSION['booking_details'] = [
                            'concert_name' => $event_data['concertName'],
                            'customer_name' => $customerName,
                            'quantity' => $quantity,
                            'total_amount' => $totalAmount
                        ];
                        $event_stmt->close();
                    }

                    // Update available quantity
                    $update_quantity_sql = "UPDATE tickettypes SET availableQuantity = availableQuantity - ? WHERE ticketTypeID = ?";
                    $update_stmt = $conn->prepare($update_quantity_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("ii", $quantity, $ticketTypeID);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }

                    // Redirect to payment page
                    header('Location: ../payment/paypal_payment.php?booking_id=' . $bookingID);
                    exit;

                } else {
                    $message = "error:Error creating booking. Please try again. Error: " . $conn->error;
                }
                $insert_stmt->close();
            } else {
                $message = "error:Database error: " . $conn->error;
            }
        }
        $ticket_stmt->close();
    } else {
        $message = "error:Database preparation error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEMPAX EXPO — Booking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styling/styles.css">
    <style>
        .message {
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="site-header">
        <div class="container header">
            <a class="logo" href="index.php">GEMPAX EXPO</a>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="booking.php" class="active">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Show Dashboard for all logged-in users -->
                        <li><a href="dashboard.php">Dashboard</a></li>

                        <!-- Show Admin links only for admin users -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['userType'] === 'admin'): ?>
                            <li><a href="adminmanagementpage.php">Admin Management Page</a></li>
                        <?php endif; ?>
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

            <div class="language-switcher">
                <button class="lang-btn">EN</button>
            </div>
        </div>
    </header>

    <!-- Booking Section -->
    <section class="booking-section">
        <div class="container">
            <div class="booking-header" data-reveal>
                <h2>Book Your Tickets</h2>
                <p>Secure your spot at the most anticipated music expo of the year</p>
            </div>

            <?php if (!empty($message)):
                list($type, $text) = explode(':', $message, 2);
                ?>
                <div class="message <?php echo $type; ?>" data-reveal>
                    <?php echo htmlspecialchars($text); ?>
                </div>
            <?php endif; ?>

            <?php if ($events_result && $events_result->num_rows > 0): ?>
                <form method="POST" action="">
                    <div class="booking-step" data-reveal>
                        <h3>1. Select Event</h3>
                        <div class="event-selection">
                            <?php while ($event = $events_result->fetch_assoc()): ?>
                                <div class="event-option">
                                    <input type="radio" id="event-<?php echo $event['concertID']; ?>" name="concert_id"
                                        value="<?php echo $event['concertID']; ?>" required>
                                    <label for="event-<?php echo $event['concertID']; ?>">
                                        <h4><?php echo htmlspecialchars($event['concertName']); ?></h4>
                                        <p><i class="fas fa-calendar"></i>
                                            <?php echo date('M j, Y', strtotime($event['concertDate'])); ?></p>
                                        <p class="price">From RM <?php echo number_format($event['ticketPrice'], 2); ?></p>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="booking-step" data-reveal>
                        <h3>2. Select Ticket Type & Quantity</h3>
                        <?php if ($ticket_types_result && $ticket_types_result->num_rows > 0): ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ticket_type">Ticket Type</label>
                                    <select id="ticket_type" name="ticket_type" required>
                                        <option value="">Select Ticket Type</option>
                                        <?php
                                        // Reset pointer and loop again
                                        $ticket_types_result->data_seek(0);
                                        while ($ticket_type = $ticket_types_result->fetch_assoc()): ?>
                                            <option value="<?php echo $ticket_type['ticketTypeID']; ?>"
                                                data-price="<?php echo $ticket_type['ticketPrice']; ?>"
                                                data-available="<?php echo $ticket_type['availableQuantity']; ?>">
                                                <?php echo htmlspecialchars($ticket_type['ticketName']); ?> - RM
                                                <?php echo number_format($ticket_type['ticketPrice'], 2); ?>
                                                (<?php echo $ticket_type['availableQuantity']; ?> available)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quantity">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" min="1" max="10" value="1" required>
                                    <small id="available-text" style="color: var(--text-muted); font-size: 0.8rem;"></small>
                                </div>
                            </div>
                            <div class="total-price"
                                style="margin-top: 1rem; padding: 1rem; background: var(--card-bg); border-radius: 8px; text-align: center;">
                                <h4>Total Amount: RM <span id="totalAmount">0.00</span></h4>
                            </div>
                        <?php else: ?>
                            <p>No ticket types available.</p>
                        <?php endif; ?>
                    </div>

                    <div class="booking-step" data-reveal>
                        <h3>3. Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>

                    <div class="booking-step" data-reveal>
                        <h3>4. Confirm Booking</h3>
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the Terms & Conditions and Privacy Policy</label>
                        </div>

                        <div class="booking-actions" data-reveal>
                            <button type="submit" name="confirm_booking" class="btn" id="confirm-booking">Confirm
                                Booking</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="booking-step" data-reveal>
                    <h3>No Events Available</h3>
                    <p>There are currently no upcoming events available for booking. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p>© GEMPAX EXPO — 2025</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Reveal animation
            const revealElements = document.querySelectorAll('[data-reveal]');

            const revealOnScroll = () => {
                revealElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;

                    if (elementTop < windowHeight - 100) {
                        element.classList.add('revealed');
                    }
                });
            };

            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll();

            // Update available quantity text and max quantity
            function updateAvailableQuantity() {
                const ticketType = document.getElementById('ticket_type');
                const quantityInput = document.getElementById('quantity');
                const availableText = document.getElementById('available-text');

                if (ticketType.value) {
                    const selectedOption = ticketType.options[ticketType.selectedIndex];
                    const available = parseInt(selectedOption.getAttribute('data-available'));

                    availableText.textContent = `${available} tickets available`;
                    quantityInput.max = available;

                    // If current quantity exceeds available, reset it
                    if (parseInt(quantityInput.value) > available) {
                        quantityInput.value = available;
                    }
                } else {
                    availableText.textContent = '';
                }
                calculateTotal();
            }

            // Calculate total amount
            function calculateTotal() {
                const ticketType = document.getElementById('ticket_type');
                const quantity = document.getElementById('quantity');
                const totalAmount = document.getElementById('totalAmount');

                if (ticketType.value && quantity.value) {
                    const selectedOption = ticketType.options[ticketType.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price'));
                    const qty = parseInt(quantity.value);
                    const total = price * qty;

                    totalAmount.textContent = total.toFixed(2);
                } else {
                    totalAmount.textContent = '0.00';
                }
            }

            // Event listeners
            document.getElementById('ticket_type').addEventListener('change', updateAvailableQuantity);
            document.getElementById('quantity').addEventListener('input', calculateTotal);

            // Initial calculations
            updateAvailableQuantity();
            calculateTotal();

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const concertSelected = document.querySelector('input[name="concert_id"]:checked');
                    if (!concertSelected) {
                        e.preventDefault();
                        alert('Please select an event.');
                        return;
                    }

                    const ticketTypeSelected = document.getElementById('ticket_type').value;
                    if (!ticketTypeSelected) {
                        e.preventDefault();
                        alert('Please select a ticket type.');
                        return;
                    }

                    const termsChecked = document.getElementById('terms').checked;
                    if (!termsChecked) {
                        e.preventDefault();
                        alert('Please agree to the Terms & Conditions.');
                        return;
                    }
                });
            }
        });
    </script>
</body>

</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>