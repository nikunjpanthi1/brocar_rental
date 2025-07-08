<?php
session_start();
require 'db.php';

$error = '';
$admin_username = "brother";
$admin_password = "brocar";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === "1";
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill all fields!";
    } elseif ($isAdmin) {
        if ($email === $admin_username && $password === $admin_password) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = 0;
            $_SESSION['name'] = $admin_username;
            $_SESSION['user_type'] = 'admin';
            $_SESSION['admin_logged_in'] = true;
            header("Location:admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid Admin credentials!";
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['user_type'] = 'user';
                header("Location:index.php");
                exit;
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "Email not found!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - BroCar Rental</title>
    <link rel="stylesheet" href="css/styles.css">

    <style>
        /* styles.css */

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
    display: flex;
    height: 100vh;
    justify-content: center;
    align-items: center;
    color: #333;
}

.login-container {
    background: white;
    padding: 30px 35px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
    text-align: center;
}

.login-container h2 {
    margin-bottom: 24px;
    color: #007bff;
    font-weight: 700;
    font-size: 28px;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    text-align: left;
    font-weight: 600;
    margin-bottom: 6px;
    margin-top: 12px;
    font-size: 14px;
    color: #555;
}

input[type="text"],
input[type="password"] {
    padding: 12px 15px;
    border-radius: 8px;
    border: 1.8px solid #ccc;
    font-size: 16px;
    transition: border-color 0.3s;
}

input[type="text"]:focus,
input[type="password"]:focus {
    border-color: #007bff;
    outline: none;
}

button[type="submit"] {
    margin-top: 20px;
    background-color: #007bff;
    border: none;
    color: white;
    padding: 14px 0;
    font-size: 18px;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button[type="submit"]:hover {
    background-color: #0056b3;
}

button.toggle-btn {
    margin-top: 12px;
    background: transparent;
    border: none;
    color: #007bff;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: color 0.3s;
}

button.toggle-btn:hover {
    color: #0056b3;
}

.error {
    background-color: #dc3545;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-weight: 600;
    font-size: 14px;
}

#admin-indicator {
    display: none;
    margin-bottom: 15px;
    font-weight: 700;
    color: #d6336c;
    font-size: 16px;
    user-select: none;
}

/* Responsive */
@media (max-width: 480px) {
    .login-container {
        padding: 25px 20px;
        width: 90%;
    }
}




     </style>

</head>
<body>
    <div class="login-container">
        <h2>Login to BroCar Rental</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <input type="hidden" name="is_admin" id="is_admin" value="0">
            <div id="admin-indicator">🔒 Admin Login Mode Enabled</div>
            <label id="emailLabel">Email</label>
            <input type="text" name="email" required placeholder="Enter your email or admin username">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
            <button type="submit">Login</button>
            <button type="button" class="toggle-btn" onclick="toggleAdmin()">Admin? Login here</button>
        </form>
        <p>Don’t have an account? <a href="signup.php">Register</a></p>
    </div>

    <script>
        let isAdminMode = false;
        function toggleAdmin() {
            isAdminMode = !isAdminMode;
            document.getElementById('is_admin').value = isAdminMode ? "1" : "0";
            document.getElementById('admin-indicator').style.display = isAdminMode ? "block" : "none";
            document.getElementById('emailLabel').innerText = isAdminMode ? "Admin Username" : "Email";
            document.querySelector(".toggle-btn").textContent = isAdminMode ? "User? Login here" : "Admin? Login here";
        }
    </script>
</body>
</html>