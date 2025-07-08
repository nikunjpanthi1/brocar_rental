<?php
session_start();
require 'db.php';          // Your DB connection
require 'config.php';      // SMTP config

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Helper to send OTP mail
function sendOTPEmail($email, $name, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USER, 'BroCar Rental');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for BroCar Signup';
        $mail->Body = "Hello $name,<br><br>Your OTP is: <b>$otp</b><br>This OTP will expire in 10 minutes.<br><br>Regards,<br>BroCar Rental Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

// On fresh page load (GET without ?step=otp), clear session to reset state
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!isset($_GET['step']) || $_GET['step'] !== 'otp')) {
    session_unset();
    session_destroy();
    session_start();
}

// Variables for display
$errors = [];
$messages = [];

// POST: Signup form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $user_type = 'user'; // default or add selection if you want

    // Validate
    if (!$name || !$email || !$password || !$phone) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();
    }

    if (!$errors) {
        // Save form data in session for OTP verification step
        $_SESSION['otp_name'] = $name;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_password'] = password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['otp_phone'] = $phone;
        $_SESSION['otp_user_type'] = $user_type;

        // Generate OTP
        $otp = random_int(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600; // 10 min expiry
        $_SESSION['otp_last_sent'] = time();

        $result = sendOTPEmail($email, $name, $otp);
        if ($result === true) {
            // Redirect to OTP step page to avoid resubmitting form on reload
            header("Location: signup.php?step=otp");
            exit;
        } else {
            $errors[] = "Failed to send OTP: $result";
        }
    }
}

// POST: Verify OTP submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $enteredOTP = trim($_POST['otp'] ?? '');

    if (!isset($_SESSION['otp'], $_SESSION['otp_expiry'])) {
        $errors[] = "OTP expired or not generated. Please signup again.";
    } elseif ($enteredOTP === (string)$_SESSION['otp'] && time() <= $_SESSION['otp_expiry']) {
        // Insert user into DB
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, user_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
            "sssss",
            $_SESSION['otp_name'],
            $_SESSION['otp_email'],
            $_SESSION['otp_password'],
            $_SESSION['otp_phone'],
            $_SESSION['otp_user_type']
        );
        if ($stmt->execute()) {
            $messages[] = "Signup successful! You can now <a href='login.php'>login</a>.";
            session_unset();
            session_destroy();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid or expired OTP.";
    }
}

// POST: Resend OTP clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    if (!isset($_SESSION['otp_email'], $_SESSION['otp_name'], $_SESSION['otp_last_sent'])) {
        $errors[] = "Session expired. Please signup again.";
    } else {
        $now = time();
        if ($now - $_SESSION['otp_last_sent'] < 60) {
            $errors[] = "Please wait before resending OTP.";
        } else {
            $otp = random_int(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = $now + 600;
            $_SESSION['otp_last_sent'] = $now;

            $result = sendOTPEmail($_SESSION['otp_email'], $_SESSION['otp_name'], $otp);
            if ($result === true) {
                $messages[] = "OTP resent successfully.";
            } else {
                $errors[] = "Failed to resend OTP: $result";
            }
        }
    }
    // Redirect to prevent form resubmission
    header("Location: signup.php?step=otp");
    exit;
}

// Decide which form to show:
$showSignupForm = !isset($_GET['step']) || $_GET['step'] !== 'otp' || !isset($_SESSION['otp']);
$showOTPForm = isset($_GET['step']) && $_GET['step'] === 'otp' && isset($_SESSION['otp']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>BroCar Rental - Signup</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f9f9f9;
        color: #333;
        padding: 20px;
    }
    .container {
        max-width: 400px;
        margin: 40px auto;
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }
    h2 {
        margin-bottom: 20px;
        text-align: center;
        color: #007bff;
    }
    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }
    input[type=text],
    input[type=email],
    input[type=password],
    input[type=tel] {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 18px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 15px;
    }
    button {
        width: 100%;
        background-color: #007bff;
        color: white;
        border: none;
        padding: 12px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
    }
    button:disabled {
        background-color: #999;
        cursor: not-allowed;
    }
    button:hover:not(:disabled) {
        background-color: #0056b3;
    }
    .msg, .error {
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-weight: 600;
        text-align: center;
    }
    .msg {
        background-color: #28a745;
        color: white;
    }
    .error {
        background-color: #dc3545;
        color: white;
    }
    .resend-section {
        margin-top: 10px;
        text-align: center;
    }
</style>
</head>
<body>

<div class="container">

<?php foreach ($messages as $msg): ?>
    <div class="msg"><?= $msg ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $err): ?>
    <div class="error"><?= $err ?></div>
<?php endforeach; ?>

<?php if ($showSignupForm): ?>
    <h2>Signup</h2>
    <form method="POST" novalidate>
        <label for="name">Name</label>
        <input id="name" name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required />

        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />

        <button name="signup" type="submit">Sign Up</button>
    </form>
<?php endif; ?>

<?php if ($showOTPForm): ?>
    <h2>Verify OTP</h2>
    <form method="POST" novalidate>
        <label for="otp">Enter OTP</label>
        <input id="otp" name="otp" type="text" maxlength="6" pattern="\d{6}" required autofocus />
        <button name="verify_otp" type="submit">Verify</button>
    </form>

    <div class="resend-section">
        <form method="POST" style="display:inline;">
            <button id="resendBtn" name="resend_otp" type="submit" disabled>
                Resend OTP 🔒
            </button>
        </form>
        <p id="timerText">You can resend OTP in 60 seconds.</p>
    </div>
<?php endif; ?>

</div>

<script>
<?php if ($showOTPForm): ?>
let resendBtn = document.getElementById('resendBtn');
let timerText = document.getElementById('timerText');

let lastSent = <?= $_SESSION['otp_last_sent'] ?? 0 ?>;
let now = Math.floor(Date.now() / 1000);
let diff = now - lastSent;
let timer = diff >= 60 ? 0 : 60 - diff;

function startTimer() {
    if (timer <= 0) {
        resendBtn.disabled = false;
        resendBtn.textContent = "Resend OTP";
        timerText.textContent = "You can resend OTP now.";
        return;
    }

    resendBtn.disabled = true;
    timerText.textContent = `You can resend OTP in ${timer} seconds.`;

    let countdown = setInterval(() => {
        timer--;
        if (timer <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false;
            resendBtn.textContent = "Resend OTP";
            timerText.textContent = "You can resend OTP now.";
        } else {
            timerText.textContent = `You can resend OTP in ${timer} seconds.`;
        }
    }, 1000);
}

startTimer();
<?php endif; ?>
</script>

</body>
</html>
