
<?php
session_start();
require_once "db.php";

// Accept login requests only through POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Get login details.
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    header("Location: index.php?error=empty");
    exit;
}

try {
    // Find the registered user.
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password, role
         FROM users
         WHERE email = :email
         LIMIT 1"
    );

    $stmt->execute(["email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password.
    if ($user && password_verify($password, $user["password"])) {

        // Create a secure session.
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];
        



        // Redirect according to the account role.
        $role = strtolower(trim($user["role"] ?? ""));
         
        if ($user["role"] === "admin") {
    header("Location: dashboard.php");
} elseif ($user["role"] === "faculty") {
    header("Location: faculty_dashboard.php");
} else {
    header("Location: student_dashboard.php");
}
exit();

        // Reject accounts with an unexpected role.
        session_unset();
        session_destroy();

        header("Location: index.php?error=invalid");
        exit;
    }

    // Email or password is incorrect.
    header("Location: index.php?error=invalid");
    exit;

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());

    http_response_code(500);
    exit("Unable to process login. Please check the server error log.");
}
?>
