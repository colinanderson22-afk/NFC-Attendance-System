<?php
session_start();
require "db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "professor") {
    die("Unauthorized.");
}
if (!isset($_POST["course_id"])) {
    die("Invalid request.");
}

$course_id    = $_POST["course_id"];
$professor_id = $_SESSION["user_id"];

// Only end sessions belonging to this professor's course
$stmt = $pdo->prepare("
    UPDATE Class_Session cs
    JOIN Course c ON cs.Course_ID = c.Course_ID
    SET cs.Active = 0
    WHERE cs.Course_ID = ? AND c.professor_id = ? AND cs.Active = 1
");
$stmt->execute([$course_id, $professor_id]);

header("Location: professor_home.php");
exit;
