<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $course_name     = trim($_POST["course_name"]);
    $tag_id          = trim($_POST["tag_id"]);
    $course_datetime = $_POST["course_datetime"];
    $description     = trim($_POST["description"]);

    // Collect checked weekdays into a comma-separated string e.g. "M,W,F"
    $allowed_days  = ["M", "TU", "W", "TR", "F"];
    $selected_days = array_filter(
        $_POST["course_days"] ?? [],
        fn($d) => in_array($d, $allowed_days)
    );
    $course_days = !empty($selected_days) ? implode(",", $selected_days) : null;

    if (empty($course_name) || empty($tag_id) || empty($course_datetime)) {
        $error = "Course name, NFC Tag ID, and date/time are required.";
    } else {
        $stmt = $pdo->prepare("SELECT Course_ID FROM Course WHERE Tag_ID = ?");
        $stmt->execute([$tag_id]);
        if ($stmt->fetch()) {
            $error = "This NFC tag is already assigned to a course.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO Course
                    (Course_Name, Course_DateTime, Course_Days, Tag_ID, Description, professor_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $course_name,
                $course_datetime,
                $course_days,
                $tag_id,
                $description ?: null,
                $_SESSION["user_id"],
            ]);
	    header("Location: course_confirm.php?course_id=" . $pdo->lastInsertId());
            exit;
        }
    }
}

// Rebuild checked days array for sticky form on error
$checked_days = $_POST["course_days"] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Setup Course</title>
    <style>
        .days-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0 6px;
            flex-wrap: wrap;
        }
        .day-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .day-btn input[type="checkbox"] {
            display: none;
        }
        .day-btn label {
            display: inline-block;
            width: 48px;
            height: 48px;
            line-height: 48px;
            text-align: center;
            border-radius: 50%;
            border: 2px solid #ccc;
            font-size: 0.85rem;
            font-weight: bold;
            cursor: pointer;
            color: #555;
            transition: all 0.15s ease;
            user-select: none;
        }
        .day-btn input[type="checkbox"]:checked + label {
            background: #4a69bd;
            border-color: #4a69bd;
            color: white;
        }
        .day-btn label:hover {
            border-color: #4a69bd;
            color: #4a69bd;
        }
        .field-label {
            font-size: 0.85rem;
            color: #555;
            display: block;
            margin: 10px 0 4px;
            text-align: left;
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
<div class="form-box">
    <h2>Create Course + Assign NFC Tag</h2>

    <?php if (isset($error)): ?>
        <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">

        <input type="text"
               name="course_name"
               placeholder="Course Name"
               required
               value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>">

        <label class="field-label">Class Date &amp; Time (first occurrence)</label>
        <input type="datetime-local"
               name="course_datetime"
               required
               value="<?php echo htmlspecialchars($_POST['course_datetime'] ?? ''); ?>">

        <label class="field-label">Recurring Days (optional)</label>
        <div class="days-group">
            <?php foreach (["M" => "M", "TU" => "Tu", "W" => "W", "TR" => "Th", "F" => "F"] as $val => $label): ?>
                <div class="day-btn">
                    <input type="checkbox"
                           name="course_days[]"
                           value="<?php echo $val; ?>"
                           id="day_<?php echo $val; ?>"
                           <?php echo in_array($val, $checked_days) ? "checked" : ""; ?>>
                    <label for="day_<?php echo $val; ?>"><?php echo $label; ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="text"
               name="tag_id"
               placeholder="NFC Tag ID"
               required
               value="<?php echo htmlspecialchars($_POST['tag_id'] ?? ''); ?>">
        <input type="text"
               name="description"
               placeholder="Description (optional)"
               value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">

        <button type="submit">Create Course</button>
    </form>

    <div style="margin-top: 16px;">
        <a href="index.php">← Back to Home</a>
    </div>
</div>
</body>
</html>
