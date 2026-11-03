
<?php
session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// Only administrators can delete notices
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Only administrators can delete notices.");
}

require_once "db.php";

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

// Process deletion only through POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $token = $_POST["csrf_token"] ?? "";

    if (
        !$id || $id < 1 ||
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(400);
        exit("Invalid request. Please return to the notices page.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            exit("Notice not found or already deleted.");
        }

        header("Location: view_notices.php");
        exit;
    } catch (PDOException $e) {
        $error = "Unable to delete this notice. Please try again.";
    }
}

// Load notice for confirmation
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid notice ID.");
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, title FROM notices WHERE id = ?"
    );
    $stmt->execute([$id]);
    $notice = $stmt->fetch();

    if (!$notice) {
        http_response_code(404);
        exit("Notice not found.");
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit("Unable to load the notice. Please try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delete Notice - College Notice Board</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 50px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #163a63;
        }

        .warning {
            padding: 14px;
            background: #fff3cd;
            color: #664d03;
            border-radius: 6px;
        }

        .error {
            padding: 12px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 6px;
        }

        button {
            padding: 12px 18px;
            border: 0;
            border-radius: 6px;
            background: #b00020;
            color: white;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #850018;
        }

        a {
            display: inline-block;
            margin-left: 12px;
            color: #163a63;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Delete Notice?</h2>

    <p class="warning">
        This action permanently deletes the selected notice.
        Please confirm before continuing.
    </p>

    <p>
        Notice title:
        <strong>
            <?= htmlspecialchars(
                $notice["title"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </strong>
    </p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="delete_notice.php">
        <input type="hidden" name="id"
               value="<?= (int) $notice["id"] ?>">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars(
                   $_SESSION["csrf_token"],
                   ENT_QUOTES,
                   "UTF-8"
               ) ?>">

        <button type="submit">Confirm Delete</button>
        <a href="view_notices.php">Cancel</a>
    </form>
</div>

</body>
</html>
