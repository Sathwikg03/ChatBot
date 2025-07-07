<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not authenticated"]);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $image = $_FILES['image'];
    $imageName = basename($image['name']);
    $imagePath = 'uploads/' . $imageName;

    if (move_uploaded_file($image['tmp_name'], $imagePath)) {
        // Store the image path in the database (if needed)
        $query = $conn->prepare("INSERT INTO images (user_id, image_path) VALUES (?, ?)");
        $query->bind_param('is', $userId, $imagePath);
        $query->execute();

        echo json_encode(["success" => true, "imagePath" => $imagePath]);
    } else {
        echo json_encode(["error" => "Failed to upload image"]);
    }
} else {
    echo json_encode(["error" => "No image uploaded"]);
}
?>
