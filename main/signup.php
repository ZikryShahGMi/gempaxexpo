<?php
include 'db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userFullName = $_POST['userFullName'];
    $userEmail = $_POST['userEmail'];
    $userPassword = $_POST['userPassword'];
    $userConfirmedPassword = $_POST['userConfirmedPassword'];

    if ($userPassword !== $userConfirmedPassword) {
        $message = "<div class='form-message error'>Passwords do not match.</div>";
    } else {
        $hashed = password_hash($userPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (userFullName, userEmail, userPassword) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $userFullName, $userEmail, $hashed);

        if ($stmt->execute()) {
            $newUserID = $conn->insert_id;

            $message = "<div class='form-message success'>Account created successfully!. Redirecting...</div>";
            echo "<script>setTimeout(() => { window.location='signin.php'; }, 2500);</script>";
        } else {
            $message = "<div class='form-message error'>Email already exists or database error.</div>";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GEMPAX EXPO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styling/styles.css">
</head>

<body>
    <header class="site-header">
        <div class="container header">
            <a class="logo" href="index.php">GEMPAX EXPO</a>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="form-container">
            <h2>Sign Up</h2>
            <?= $message ?>
            <form method="post" action="">
                <div class="form-group">
                    <input type="text" name="userFullName" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="userEmail" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="userPassword" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="userConfirmedPassword" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            </form>
            <p>Already have an account? <a href="signin.php">Sign In</a></p>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p>© GEMPAX EXPO — 2025</p>
        </div>
    </footer>
</body>

</html>