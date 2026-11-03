<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$errorMessage = "";

if (isset($_GET["error"])) {
    if ($_GET["error"] === "empty") {
        $errorMessage = "Please enter your email and password.";
    } elseif ($_GET["error"] === "invalid") {
        $errorMessage = "Invalid email address or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>College Notice Board - Login</title>

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
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #163a63, #5b9bd5);
        }

        .login-box {
            width: 90%;
            max-width: 400px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        h1 {
            color: #163a63;
            text-align: center;
            font-size: 25px;
        }

        p {
            color: #666;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #5b9bd5;
            border-color: #163a63;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 24px;
            background: #163a63;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #285c91;
        }

        .register-link {
            margin-top: 20px;
            font-size: 14px;
        }

        .register-link a {
            color: #163a63;
            font-weight: bold;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .success-message {
            padding: 10px;
            background: #e6f7e9;
            color: #17652b;
            border-radius: 6px;
            font-size: 14px;
        }

        .error-message {
            padding: 10px;
            background: #f8d7da;
            color: #842029;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h1>🎓 College Notice Board</h1>
        <p>Login to continue</p>

        <?php if (isset($_GET["registered"]) && $_GET["registered"] === "1"): ?>
            <p class="success-message">
                Registration successful! Please log in.
            </p>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <p class="error-message">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Login</button>
        </form>

        <p class="register-link">
            Don't have an account?
            <a href="register.php">Register as a Student</a>
        </p>
    </div>
</body>
</html>