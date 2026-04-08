<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email      = trim($_POST["email"]);
    $first_name = trim($_POST["first_name"]);
    $last_name  = trim($_POST["last_name"]);
    $course_tag = trim($_POST["tag_id"]);
    $role_pick  = $_POST["role"] ?? "student"; // new: let them pick
    $name       = $first_name . " " . $last_name;

    // --- Path 1: Professor registering without a tag ---
    if ($role_pick === "professor" && empty($course_tag)) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing["role"] === "professor") {
                // Already a professor, just log them in
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
        <input type="email" name="email"      placeholder="Email"               required>
        <input type="text"  name="first_name" placeholder="First Name"          required>
        <input type="text"  name="last_name"  placeholder="Last Name"           required>

        <label style="display:block; margin: 10px 0 4px; text-align:left;">I am a:</label>
        <select name="role">
            <option value="student">Student</option>
            <option value="professor">Professor</option>
        </select>

        <input type="text"  name="tag_id"     placeholder="NFC Tag ID (For Students)">
        <small style="color:#888;">Professors creating a new account can leave this blank.</small>

        <button type="submit">Register</button>
    </form>
</div>
<a href="index.php">
    <button class="card">Cancel</button>
</div>
</body>
</html>
