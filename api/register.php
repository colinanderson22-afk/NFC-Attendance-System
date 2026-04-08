<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "db.php";

// If a logged-in student is enrolling in another course, prefill their info
$prefill_email      = '';
$prefill_first_name = '';
$prefill_last_name  = '';
$prefill_role       = 'student';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT Name, Email, role FROM Users WHERE User_ID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $me = $stmt->fetch();
    if ($me) {
        $prefill_email = $me['Email'];
        $prefill_role  = $me['role'];
        $parts = explode(" ", $me['Name'], 2);
        $prefill_first_name = $parts[0];
        $prefill_last_name  = $parts[1] ?? '';
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email      = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name  = trim($_POST["last_name"]);
    $course_tag = trim($_POST["tag_id"]);
    $role_pick  = $_POST["role"] ?? "student";
    $name       = $first_name . " " . $last_name;

    // --- Path 1: Professor registering without a tag ---
    if ($role_pick === "professor" && empty($course_tag)) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing["role"] === "professor") {
                $_SESSION["user_id"] = $existing["User_ID"];
                $_SESSION["role"]    = "professor";
                header("Location: professor_home.php");
                exit;
            } else {
                $error = "This email is already registered as a student.";
            }
        } else {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, role, token) VALUES (?, ?, 'professor', ?)");
            $stmt->execute([$name, $email, $token]);
            $_SESSION["user_id"] = $pdo->lastInsertId();
            $_SESSION["role"]    = "professor";
            header("Location: setup_course.php");
            exit;
        }

    // --- Path 2: Original tag-based flow (students + returning professors) ---
    } elseif (!empty($course_tag)) {
        $stmt = $pdo->prepare("SELECT * FROM Course WHERE Tag_ID = ?");
        $stmt->execute([$course_tag]);
        $course = $stmt->fetch();

        if (!$course) {
            $error = "Invalid course tag.";
        } else {
            $intended_role = ($course["professor_id"] == NULL) ? "professor" : "student";

            $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
            $stmt->execute([$email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                if ($existing_user["role"] === "professor" && $intended_role === "professor") {
                    $user_id = $existing_user["User_ID"];
                    $stmt = $pdo->prepare("UPDATE Course SET professor_id = ? WHERE Course_ID = ?");
                    $stmt->execute([$user_id, $course["Course_ID"]]);
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["role"]    = "professor";
                    header("Location: professor_home.php");
                    exit;
                } else {
                    $error = "This email is already registered. Please log in instead.";
                }
            } else {
                $token = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, role, token) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $intended_role, $token]);
                $user_id = $pdo->lastInsertId();

                if ($intended_role === "professor") {
                    $pdo->prepare("UPDATE Course SET professor_id = ? WHERE Course_ID = ?")->execute([$user_id, $course["Course_ID"]]);
                } else {
                    $pdo->prepare("INSERT IGNORE INTO Enrollment (User_ID, Course_ID) VALUES (?, ?)")->execute([$user_id, $course["Course_ID"]]);
                }

                $_SESSION["user_id"] = $user_id;
                $_SESSION["role"]    = $intended_role;
                header("Location: " . ($intended_role === "professor" ? "professor_home.php" : "student_home.php"));
                exit;
            }
        }
    } else {
        $error = "Please enter an NFC Tag ID or register as a professor.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Register</title>
</head>
<body>
<div class="form-box">
    <h2>Register</h2>
    <?php if (isset($error)): ?>
        <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST">
        <?php
            $f_email = htmlspecialchars($_POST['email']      ?? $prefill_email);
            $f_first = htmlspecialchars($_POST['first_name'] ?? $prefill_first_name);
            $f_last  = htmlspecialchars($_POST['last_name']  ?? $prefill_last_name);
            $f_role  = $_POST['role'] ?? $prefill_role;
        ?>
        <input type="email" name="email"      placeholder="Email"      required value="<?= $f_email ?>">
        <input type="text"  name="first_name" placeholder="First Name" required value="<?= $f_first ?>">
        <input type="text"  name="last_name"  placeholder="Last Name"  required value="<?= $f_last ?>">

        <label style="margin: 10px auto 4px; width: 90%; text-align: left;">I am a:</label>
        <select name="role">
            <option value="student"   <?= $f_role === 'student'   ? 'selected' : '' ?>>Student</option>
            <option value="professor" <?= $f_role === 'professor' ? 'selected' : '' ?>>Professor</option>
        </select>

        <input type="text" name="tag_id" placeholder="NFC Tag ID (students / returning professors)">
        <small style="color:#888; display:block; width:90%; margin: 0 auto 12px; text-align:left;">
            Professors creating a new account can leave this blank.
        </small>

        <button type="submit">Register</button>
    </form>

    <div style="margin-top: 16px;">
        <a href="index.php"><button class="btn-secondary">Cancel</button></a>
    </div>
</div>
</body>
</html>
