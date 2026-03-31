<?php
session_start();
require 'db.php'; // your PDO connection

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'];
$first = $data['first_name'];
$last = $data['last_name'];
$class_tag = $data['class_tag']; // comes from NFC scan

// Find class
$stmt = $pdo->prepare("SELECT * FROM classes WHERE nfc_tag = ?");
$stmt->execute([$class_tag]);
$class = $stmt->fetch();

if (!$class) {
    echo json_encode(["success" => false, "message" => "Invalid class tag"]);
    exit;
}

// Check if professor exists
$isProfessor = false;

if ($class['professor_id'] == NULL) {
    $role = 'professor';
    $isProfessor = true;
} else {
    $role = 'student';
}

// Generate secure token
$token = bin2hex(random_bytes(16));

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (email, first_name, last_name, token, role, class_id)
VALUES (?, ?, ?, ?, ?, ?)");

$stmt->execute([$email, $first, $last, $token, $role, $class['id']]);

$user_id = $pdo->lastInsertId();

// If professor, update class
if ($isProfessor) {
    $stmt = $pdo->prepare("UPDATE classes SET professor_id = ? WHERE id = ?");
    $stmt->execute([$user_id, $class['id']]);
}

// Store session
$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $role;

echo json_encode([
    "success" => true,
    "role" => $role,
    "redirect" => $role === 'professor' ? "professor_home.php" : "student_home.php"
]);

