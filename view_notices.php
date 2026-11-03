
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "db.php";

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

$categories = [
    "General",
    "Academic",
    "Examination",
    "Events",
    "Holiday",
    "Placement",
    "Sports"
];

if (!in_array($category, $categories, true)) {
    $category = "";
}

$isAdmin = (($_SESSION["role"] ?? "") === "admin");
$error = "";
$notices = [];

try {
    $sql = "SELECT
                n.id,
                n.title,
                n.description,
                n.category,
                n.created_at,
                u.name AS author
            FROM notices n
            INNER JOIN users u ON n.admin_id = u.id
            WHERE n.status = 'published'
              AND (
                  n.expiry_date IS NULL
                  OR n.expiry_date >= CURDATE()
              )";

    $params = [];

    if ($search !== "") {
        $sql .= " AND (
                    n.title LIKE :title
                    OR n.description LIKE :description
                  )";

        $params["title"] = "%" . $search . "%";
        $params["description"] = "%" . $search . "%";
    }

    if ($category !== "") {
        $sql .= " AND n.category = :category";
        $params["category"] = $category;
    }

    $sql .= " ORDER BY n.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notices = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("View notices error: " . $e->getMessage());
    $error = "Unable to load notices. Please check your database settings.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Notices - College Notice Board</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            color: #333;
        }

        header {
            padding: 20px;
            background: #163a63;
            color: white;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            padding: 15px 20px;
            background: white;
        }

        nav a {
            color: #163a63;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            color: #5b9bd5;
        }

        .container {
            width: 92%;
            max-width: 1000px;
            margin: 30px auto;
        }

        h2 {
            color: #163a63;
        }

        .filter-box {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            margin-bottom: 25px;
            background: white;
            border-radius: 10px;
        }

        .filter-box input,
        .filter-box select {
            flex: 1;
            min-width: 180px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .filter-box button,
        .reset-btn {
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            background: #163a63;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .notice-card {
            padding: 22px;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .notice-card h3 {
            margin-top: 0;
            color: #163a63;
            overflow-wrap: anywhere;
        }

        .category {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            background: #e7eef7;
            color: #163a63;
            font-size: 13px;
        }

        .content {
            line-height: 1.6;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .meta {
            color: #666;
            font-size: 13px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .edit-btn,
        .delete-btn {
            display: inline-block;
            padding: 9px 14px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .edit-btn {
            background: #1769aa;
        }

        .delete-btn {
            background: #b00020;
        }

        .empty {
            padding: 25px;
            background: white;
            border-radius: 10px;
        }

        .error {
            padding: 12px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<header>
    <h1>🎓 College Notice Board</h1>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>

    <?php if ($isAdmin): ?>
        <a href="post_notice.php">Post Notice</a>
    <?php endif; ?>

    <a href="view_notices.php">View Notices</a>
    <a href="logout.php">Logout</a>
</nav>

<main class="container">
    <h2>College Notices</h2>
    <p>Search and filter college announcements.</p>

    <form class="filter-box" method="GET" action="view_notices.php">
        <input
            type="search"
            name="search"
            placeholder="Search title or description..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
        >

        <select name="category">
            <option value="">All Categories</option>

            <?php foreach ($categories as $item): ?>
                <option
                    value="<?= htmlspecialchars($item, ENT_QUOTES, "UTF-8") ?>"
                    <?= $category === $item ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($item, ENT_QUOTES, "UTF-8") ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search</button>
        <a class="reset-btn" href="view_notices.php">Reset</a>
    </form>

    <?php if ($error !== ""): ?>

        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>

    <?php else: ?>

        <p>
            Notices found: <strong><?= count($notices) ?></strong>
        </p>

        <?php if (empty($notices)): ?>

            <div class="empty">
                No published notices found. Try another search or category.
            </div>

        <?php else: ?>

            <?php foreach ($notices as $notice): ?>
                <article class="notice-card">

                    <h3>
                        <?= htmlspecialchars($notice["title"], ENT_QUOTES, "UTF-8") ?>
                    </h3>

                    <span class="category">
                        <?= htmlspecialchars($notice["category"], ENT_QUOTES, "UTF-8") ?>
                    </span>

                    <p class="content"><?= htmlspecialchars(
                        $notice["description"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></p>

                    <p class="meta">
                        Posted by:
                        <?= htmlspecialchars($notice["author"], ENT_QUOTES, "UTF-8") ?>
                        |
                        Date:
                        <?= htmlspecialchars($notice["created_at"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <?php if ($isAdmin): ?>
                        <div class="actions">
                            <a
                                class="edit-btn"
                                href="edit_notice.php?id=<?= (int) $notice["id"] ?>"
                            >
                                Edit Notice
                            </a>

                            <a
                                class="delete-btn"
                                href="delete_notice.php?id=<?= (int) $notice["id"] ?>"
                            >
                                Delete Notice
                            </a>
                        </div>
                    <?php endif; ?>

                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>
