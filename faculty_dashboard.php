
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "faculty") {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$stmt = $pdo->query(
    "SELECT id, title, description, category, created_at
     FROM notices
     WHERE status = 'published'
     ORDER BY created_at DESC"
);

$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - College Notice Board</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            color: #263445;
        }
        header {
            background: #163a63;
            color: white;
            padding: 22px;
        }
        header h1 { margin: 0 0 8px; }
        nav {
            background: white;
            padding: 15px 22px;
            box-shadow: 0 2px 5px #0001;
        }
        nav a {
            margin-right: 20px;
            color: #163a63;
            text-decoration: none;
            font-weight: bold;
        }
        main {
            max-width: 1000px;
            margin: 25px auto;
            padding: 0 18px;
        }
        .card {
            background: white;
            padding: 22px;
            margin-bottom: 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000d;
        }
        .notice {
            border-left: 4px solid #2563eb;
            padding: 12px 16px;
            margin-top: 15px;
            background: #f8fafc;
        }
        .category { color: #2563eb; font-size: 14px; }
    </style>
</head>
<body>
<header>
    <h1>Faculty Dashboard</h1>
    <p>College Notice Board System</p>
</header>

<nav>
    <a href="faculty_dashboard.php">Dashboard</a>
    <a href="view_notices.php">View Notices</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <section class="card">
        <h2>Welcome, <?= htmlspecialchars($_SESSION["name"] ?? "Faculty") ?>!</h2>
        <p>You are logged in as Faculty.</p>
    </section>

    <section class="card">
        <h2>Published College Notices</h2>

        <?php if (empty($notices)): ?>
            <p>No published notices are available.</p>
        <?php else: ?>
            <?php foreach ($notices as $notice): ?>
                <article class="notice">
                    <h3><?= htmlspecialchars($notice["title"]) ?></h3>
                    <p class="category">
                        <?= htmlspecialchars($notice["category"]) ?>
                    </p>
                    <p><?= nl2br(htmlspecialchars($notice["description"])) ?></p>
                    <small>
                        Posted:
                        <?= htmlspecialchars($notice["created_at"] ?? "") ?>
                    </small>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
