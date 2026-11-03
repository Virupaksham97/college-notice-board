<?php
session_start();
require_once "db.php";

$message = "";
$messageType = "error";
$name = "";
$email = "";
$selectedRole = "student";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $selectedRole = $_POST["role"] ?? "student";

    if (!in_array($selectedRole, ["admin", "student"], true)) {
        $selectedRole = "student";
    }

    if (
        $name === "" || $email === "" ||
        $password === "" || $confirmPassword === ""
    ) {
        $message = "Please fill in all fields.";
    } elseif (strlen($name) > 100) {
        $message = "Name must not exceed 100 characters.";
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        try {
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = :email"
            );
            $check->execute(["email" => $email]);

            if ($check->fetch()) {
                $message = "This email is already registered.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Public registration always creates a student.
                $insert = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role)
                     VALUES (:name, :email, :password, 'student')"
                );

                $insert->execute([
                    "name" => $name,
                    "email" => $email,
                    "password" => $hashedPassword
                ]);

                header("Location: index.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $message = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - College Notice Board</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    font-family: Arial, Helvetica, sans-serif;
    background:
        linear-gradient(rgba(15, 35, 60, 0.55), rgba(15, 35, 60, 0.55)),
        url("images/college-notice-board.jpg") center / cover no-repeat fixed;
    color: #263445;
}

        .register-box {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            text-align: center;
            color: #163a63;
            font-size: 26px;
        }

        .subtitle {
            margin-bottom: 25px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            color: #333;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2864a0;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #163a63;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #24578a;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .error {
            background: #ffe5e5;
            color: #a40000;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        a {
            color: #163a63;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="register-box">
        <h1>College Notice Board</h1>
        <p class="subtitle">Create your account</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                autocomplete="name"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                autocomplete="email"
                required
            >

            <label for="role">Register As</label>
            <select id="role" name="role" required>
                <option value="student"
                    <?= $selectedRole === "student" ? "selected" : "" ?>>
                    Student
                </option>
                <option value="admin"
                    <?= $selectedRole === "admin" ? "selected" : "" ?>>
                    Admin
                </option>
            </select>

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create Account</button>
        </form>

        <div class="footer">
            Already have an account?
            <a href="index.php">Login here</a>
        </div>
    </div>
</body>
</html>
