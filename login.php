<?php
session_start(); // Start session to persist user login state

$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "ai";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT id, username, name, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows == 1) {
        // Bind result variables
        $stmt->bind_result($user_id, $db_username, $name, $hashed_password);
        $stmt->fetch();

        // Verify hashed password
        if (password_verify($password, $hashed_password)) {
            // Password correct, start session and store user ID, username, and name
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $db_username;
            $_SESSION['name'] = $name;

            // Redirect to chatbot.html
            header("Location: cb.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Username not found.'); window.history.back();</script>";
        exit();
    }

    // Close statements and connection
    $stmt->close();
    $conn->close();
}
?>
