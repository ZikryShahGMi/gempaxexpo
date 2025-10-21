<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
session_start();
$message = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = $_POST['userEmail'];
    $userPassword = $_POST['userPassword'];

    $stmt = $conn->prepare("SELECT userID, userFullName, userPassword, userType FROM users WHERE userEmail = ?");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($userPassword, $row['userPassword'])) {
            $_SESSION['user_id'] = $row['userID'];
            $_SESSION['fullname'] = $row['userFullName'];
            $_SESSION['userType'] = $row['userType'];
            $_SESSION['role'] = $row['userType'];

            if ($row['userType'] === 'admin') {
                $redirectPage = "adminpage.php";
            } else {
                $redirectPage = "index.php";
            }

            $message = "<div class='form-message success'>Login successful! Redirecting...</div>";
            echo "<script>setTimeout(() => { window.location='{$redirectPage}'; }, 1500);</script>";
        } else {
            $message = "<div class='form-message error'>Incorrect password.</div>";
        }
    } else {
        $message = "<div class='form-message error'>No account found with that email.</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - GEMPAX EXPO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styling/sign-styles.css">
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
            <h2>Sign In</h2>
            <?= $message ?>
            <form method="post" action="">
                <div class="form-group">
                    <input type="email" name="userEmail" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="userPassword" placeholder="Password" required>
                </div>
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            <p class="redirect-text">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p>© GEMPAX EXPO — 2025</p>
        </div>
    </footer>
</body>

</html>