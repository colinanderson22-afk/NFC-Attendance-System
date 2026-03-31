session_start();
require "db.php";

$course_id = $_GET["id"];

// Ensure professor owns this course
$stmt = $pdo->prepare("
    SELECT * FROM Course
    WHERE Course_ID = ? AND professor_id = ?
");
$stmt->execute([$course_id, $_SESSION["user_id"]]);
$course = $stmt->fetch();

if (!$course) {
    die("Unauthorized.");
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM Enrollment WHERE Course_ID = ?
");
$stmt->execute([$course_id]);
$total_students = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM Attendance WHERE Course_ID = ?
");
$stmt->execute([$course_id]);
$total_attendance = $stmt->fetchColumn();

<h2><?php echo $course["Course_Name"]; ?> Stats</h2>
Total Students: <?php echo $total_students; ?><br>
Total Attendance Records: <?php echo $total_attendance; ?>
