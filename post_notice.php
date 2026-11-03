
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: student_dashboard.php");
    exit;
}

$error = "";
$success = "";

$title = "";
$description = "";
$category = "General";
$status = "published";

$categories = [
    "General",
    "Academic",
    "Examination",
    "Events",
    "Holiday"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = $_POST["category"] ?? "General";
    $status = $_POST["status"] ?? "published";

    if ($title === "" || $description === "") {
        $error = "Please enter both the notice title and description.";
    } elseif (strlen($title) > 200) {
        $error = "The notice title must not exceed 200 characters.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please select a valid category.";
    } elseif (!in_array($status, ["draft", "published"], true)) {
        $error = "Please select a valid status.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO notices
                 (title, description, category, status, admin_id)
                 VALUES
                 (:title, :description, :category, :status, :admin_id)"
            );

            $stmt->execute([
                "title" => $title,
                "description" => $description,
                "category" => $category,
                "status" => $status,
                "admin_id" => $_SESSION["user_id"]
            ]);

            header("Location: view_notices.php?success=posted");
            exit;

        } catch (PDOException $e) {
            error_log("Post notice error: " . $e->getMessage());
            $error = "Unable to save the notice. Please check your database settings.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post Notice | College Notice Board</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f7fc;
    color: #18243a;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    flex-shrink: 0;
    background: #102747;
    color: white;
    padding: 28px 18px;
}

.brand {
    font-size: 21px;
    font-weight: 750;
    line-height: 1.4;
    margin: 0 10px 8px;
}

.brand small {
    display: block;
    font-size: 12px;
    color: #a9c4e8;
    font-weight: 400;
    margin-top: 5px;
}

.menu-title {
    margin: 35px 10px 12px;
    color: #91a9c9;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.4px;
}

.nav-link {
    display: block;
    padding: 13px 14px;
    margin: 7px 0;
    color: #dce7f6;
    text-decoration: none;
    border-radius: 9px;
    font-size: 14px;
}

.nav-link:hover,
.nav-link.active {
    background: #244a79;
    color: white;
}

.main {
    flex: 1;
    min-width: 0;
    padding: 30px;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    color: #142b4c;
    font-size: 28px;
}

.page-header p {
    color: #78869a;
    font-size: 14px;
    margin-top: 8px;
}

.form-card {
    max-width: 850px;
    background: white;
    border: 1px solid #e5ebf4;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(26, 52, 86, 0.05);
}

.form-card h2 {
    font-size: 20px;
    color: #183456;
    margin-bottom: 8px;
}

.form-description {
    color: #78869a;
    font-size: 13px;
    margin-bottom: 26px;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 22px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: 650;
    color: #263c59;
    margin-bottom: 9px;
}

input,
select,
textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #d8e1ed;
    border-radius: 9px;
    background: #fff;
    font-family: inherit;
    font-size: 14px;
    color: #253750;
    outline: none;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #3477b9;
    box-shadow: 0 0 0 3px rgba(52, 119, 185, 0.12);
}

textarea {
    min-height: 180px;
    resize: vertical;
    line-height: 1.7;
}

.hint {
    margin-top: 7px;
    color: #8995a7;
    font-size: 12px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.alert {
    padding: 13px 15px;
    margin-bottom: 20px;
    border-radius: 9px;
    font-size: 14px;
    line-height: 1.5;
}

.alert-error {
    background: #fff0f0;
    color: #a52828;
    border: 1px solid #ffd5d5;
}

.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.button {
    display: inline-block;
    border: none;
    padding: 12px 19px;
    border-radius: 9px;
    text-decoration: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 650;
    cursor: pointer;
}

.button-primary {
    background: #1e5b99;
    color: white;
}

.button-primary:hover {
    background: #123d6b;
}

.button-secondary {
    background: #edf2f8;
    color: #304661;
}

.button-secondary:hover {
    background: #dfe8f3;
}

.footer {
    color: #8995a7;
    font-size: 12px;
    margin-top: 28px;
}

@media (max-width: 700px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        padding: 18px;
    }

    .menu-title {
        display: none;
    }

    .sidebar nav {
        display: flex;
        gap: 7px;
        overflow-x: auto;
        margin-top: 15px;
    }

    .nav-link {
        white-space: nowrap;
        margin: 0;
        padding: 10px;
    }

    .main {
        padding: 18px;
    }

    .form-card {
        padding: 20px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .page-header h1 {
        font-size: 24px;
    }
}
</style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            🎓 Campus Notice Board
            <small>College Management Portal</small>
        </div>

        <div class="menu-title">Main Menu</div>

        <nav>
            <a class="nav-link" href="dashboard.php">▦ &nbsp; Dashboard</a>
            <a class="nav-link active" href="post_notice.php">＋ &nbsp; Post Notice</a>
            <a class="nav-link" href="view_notices.php">▤ &nbsp; Manage Notices</a>
            <a class="nav-link" href="student_dashboard.php">♙ &nbsp; Student View</a>
            <a class="nav-link" href="logout.php">↪ &nbsp; Logout</a>
        </nav>
    </aside>

    <main class="main">

        <header class="page-header">
            <h1>Post a New Notice</h1>
            <p>Create and publish important college announcements.</p>
        </header>

        <section class="form-card">
            <h2>Notice Information</h2>
            <p class="form-description">
                Enter the details below. Published notices will be available
                to students on their dashboard.
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="post_notice.php">

                <div class="form-group">
                    <label for="title">Notice Title *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="200"
                        placeholder="Enter notice title"
                        value="<?= htmlspecialchars($title) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Notice Description *</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Write the complete notice details here..."
                        required
                    ><?= htmlspecialchars($description) ?></textarea>
                    <p class="hint">Include important dates, instructions and details.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Notice Category</label>
                        <select id="category" name="category">
                            <?php foreach ($categories as $item): ?>
                                <option
                                    value="<?= htmlspecialchars($item) ?>"
                                    <?= $category === $item ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($item) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Publication Status</label>
                        <select id="status" name="status">
                            <option value="published"
                                <?= $status === "published" ? "selected" : "" ?>>
                                Published
                            </option>
                            <option value="draft"
                                <?= $status === "draft" ? "selected" : "" ?>>
                                Save as Draft
                            </option>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="button button-primary">
                        Publish Notice
                    </button>

                    <a href="view_notices.php"
                       class="button button-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </section>

        <footer class="footer">
            College Notice Board System · Admin Portal
        </footer>

    </main>
</div>
</body>
</html>
