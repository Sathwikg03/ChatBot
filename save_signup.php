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
    $name = $_POST['name'];
    $username = $_POST['username'];
    $phone_number = $_POST['phoneNumber'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (name, username, phone_number, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $phone_number, $email, $hashed_password);

    if ($stmt->execute()) {
        // Get the last inserted ID
        $user_id = $stmt->insert_id;

        // Start session and store user ID, username, and name
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['name'] = $name;

        // Redirect to chatbot.html
        header("Location: cb.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
    }

    // Close statements and connection
    $stmt->close();
    $conn->close();
}
?>
