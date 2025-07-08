<?php
require 'db.php';
header('Content-Type: application/json');
$id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
$stmt = $conn->prepare("SELECT * FROM cars WHERE car_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$imgStmt = $conn->prepare("SELECT image_path, label FROM car_images WHERE car_id=?");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$car['images'] = $images;
echo json_encode($car);
