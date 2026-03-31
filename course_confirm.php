<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

// Expect course_id passed from setup_course.php after insert
if (!isset($_GET['course_id'])) {
    header("Location: professor_home.php");
    exit;
}

$course_id = (int) $_GET['course_id'];

$stmt = $pdo->prepare("SELECT * FROM Course WHERE Course_ID = ? AND professor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

// Build the scan URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$scan_url  = $protocol . '://' . $host . $base_path . '/scan.php?course_id=' . $course_id;
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Course Created</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .confirm-box {
            max-width: 480px;
            margin: 60px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            padding: 40px 36px 32px;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        .confirm-box .checkmark {
            font-size: 52px;
            margin-bottom: 8px;
        }

        .confirm-box h2 {
            color: #2ecc71;
            margin: 0 0 4px;
            font-size: 1.5rem;
        }

        .confirm-box .course-name {
            color: #555;
            font-size: 1rem;
            margin-bottom: 28px;
        }

        .section-label {
            font-size: 0.78rem;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
            text-align: left;
        }

        .url-row {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f4f6f9;
            border: 1px solid #dde1ea;
            border-radius: 7px;
            padding: 10px 12px;
            margin-bottom: 6px;
        }

        .url-row span {
            flex: 1;
            font-size: 0.82rem;
            color: #333;
            word-break: break-all;
            text-align: left;
        }

        .copy-btn {
            flex-shrink: 0;
            padding: 6px 14px;
            font-size: 0.82rem;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .copy-btn:hover { background: #2176ae; }
        .copy-btn.copied { background: #2ecc71; }

        .copy-hint {
            font-size: 0.75rem;
            color: #aaa;
            text-align: left;
            margin-bottom: 28px;
        }

        .qr-section {
            margin-bottom: 28px;
        }

        #qrcode {
            display: inline-block;
            padding: 12px;
            background: #fff;
            border: 1px solid #dde1ea;
            border-radius: 8px;
            margin-top: 8px;
        }

        .nfc-tip {
            background: #eaf4ff;
            border: 1px solid #b6d9f7;
            border-radius: 7px;
            padding: 12px 16px;
            font-size: 0.82rem;
            color: #2a6496;
            text-align: left;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .nfc-tip strong { display: block; margin-bottom: 4px; }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-primary {
            padding: 10px 22px;
            font-size: 0.95rem;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary:hover { background: #2176ae; }

        .btn-secondary {
            padding: 10px 22px;
            font-size: 0.95rem;
            background: #f4f6f9;
            color: #555;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-secondary:hover { background: #e8eaed; }
    </style>
</head>
<body>
<div class="confirm-box">

    <div class="checkmark">✅</div>
    <h2>Course Created!</h2>
    <p class="course-name"><?php echo htmlspecialchars($course['Course_Name']); ?></p>

    <!-- Scan URL -->
    <div class="section-label">Student Scan URL</div>
    <div class="url-row">
        <span id="scan-url"><?php echo htmlspecialchars($scan_url); ?></span>
        <button class="copy-btn" onclick="copyURL(this)">Copy</button>
    </div>
    <p class="copy-hint">Write this URL to your NFC tag, or share the QR code below.</p>

    <!-- QR Code -->
    <div class="qr-section">
        <div class="section-label">QR Code</div>
        <div id="qrcode"></div>
        <div style="margin-top:10px;">
            <a id="qr-download" href="#" download="qr_course_<?php echo $course_id; ?>.png"
               style="font-size:0.8rem; color:#3498db;">⬇ Download QR Code</a>
        </div>
    </div>

    <!-- NFC writing tip -->
    <div class="nfc-tip">
        <strong>📲 How to write this URL to an NFC tag</strong>
        Download <strong>NFC Tools</strong> (free, iOS &amp; Android) → tap <em>Write</em> → <em>Add a record</em> → <em>URL</em> → paste the URL above → hold your phone to the blank NFC tag.
    </div>

    <div class="actions">
        <a href="setup_course.php" class="btn-secondary">+ Another Course</a>
        <a href="professor_home.php" class="btn-primary">Go to Dashboard</a>
    </div>

</div>

<script>
    const scanUrl = <?php echo json_encode($scan_url); ?>;

    // Generate QR code
    const qr = new QRCode(document.getElementById("qrcode"), {
        text: scanUrl,
        width: 180,
        height: 180,
        colorDark: "#1a1a2e",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });

    // Enable download link after QR renders
    setTimeout(() => {
        const canvas = document.querySelector("#qrcode canvas");
        if (canvas) {
            document.getElementById("qr-download").href = canvas.toDataURL("image/png");
        }
    }, 300);

    // Copy URL to clipboard
    function copyURL(btn) {
        navigator.clipboard.writeText(scanUrl).then(() => {
            btn.textContent = "Copied!";
            btn.classList.add("copied");
            setTimeout(() => {
                btn.textContent = "Copy";
                btn.classList.remove("copied");
            }, 2000);
        });
    }
</script>
</body>
</html>
