```php
<?php
session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// Admin-only access
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Only administrators can edit notices.");
}

require_once "db.php";

// Create CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$categories = [
    "General",
    "Academic",
    "Examination",
    "Events",
    "Holiday",
    "Placement",
    "Sports"
];

$error = "";

// Get notice ID
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid notice ID.");
}

try {
    // Fetch notice using the correct database column
    $stmt = $pdo->prepare(
        "SELECT id, title, category, description
         FROM notices
         WHERE id = ?"
    );

    $stmt->execute([$id]);
    $notice = $stmt->fetch();

    if (!$notice) {
        http_response_code(404);
        exit("Notice not found.");
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $token = $_POST["csrf_token"] ?? "";

        if (
            !is_string($token) ||
            !hash_equals($_SESSION["csrf_token"], $token)
        ) {
            $error = "Invalid request. Refresh the page and try again.";
        } else {
            $title = trim($_POST["title"] ?? "");
            $category = trim($_POST["category"] ?? "");
            $content = trim($_POST["content"] ?? "");

            if ($title === "" || $content === "") {
                $error = "Title and description are required.";
            } elseif (strlen($title) > 200) {
                $error = "Title cannot exceed 200 characters.";
            } elseif (!in_array($category, $categories, true)) {
                $error = "Please select a valid category.";
            } else {
                // Update the correct database column: description
                $stmt = $pdo->prepare(
                    "UPDATE notices
                     SET title = ?,
                         category = ?,
                         description = ?,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?"
                );

                $stmt->execute([
                    $title,
                    $category,
                    $content,
                    $id
                ]);

                header("Location: view_notices.php?updated=1");
                exit;
            }

            // Preserve values if validation fails
            $notice["title"] = $title;
            $notice["category"] = $category;
            $notice["description"] = $content;
        }
    }

} catch (PDOException $e) {
    error_log("Edit notice error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to update the notice. Please check the database.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Notice - College Notice Board</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: #f0f4f8;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #163a63;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        button {
            margin-top: 20px;
            padding: 12px 20px;
            background: #163a63;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #285c91;
        }

        .error {
            padding: 12px;
            color: #721c24;
            background: #f8d7da;
            border-radius: 6px;
        }

        a {
            color: #163a63;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Notice</h1>
    <p>Update your college announcement below.</p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST"
          action="edit_notice.php?id=<?= (int) $id ?>">

        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION["csrf_token"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >

        <label for="title">Notice Title</label>
        <input
            type="text"
            id="title"
            name="title"
            maxlength="200"
            required
            value="<?= htmlspecialchars(
                $notice["title"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >

        <label for="category">Category</label>
        <select id="category" name="category" required>
            <?php foreach ($categories as $option): ?>
                <option
                    value="<?= htmlspecialchars(
                        $option,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    <?= $notice["category"] === $option
                        ? "selected"
                        : "" ?>
                >
                    <?= htmlspecialchars(
                        $option,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="content">Description</label>
        <textarea
            id="content"
            name="content"
            required
        ><?= htmlspecialchars(
            $notice["description"],
            ENT_QUOTES,
            "UTF-8"
        ) ?></textarea>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="view_notices.php">Back to Notices</a></p>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>

</body>
</html>
