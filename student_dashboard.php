
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$sql = "SELECT id, title, description, category, created_at,
               attachment
        FROM notices
        WHERE status = 'published'
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
        ORDER BY created_at DESC";

$stmt = $pdo->query($sql);
$notices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Dashboard - College Notice Board</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    color: #333;
}

header {
    background: #163a63;
    color: white;
    padding: 22px 7%;
}

header h1 { margin: 0 0 8px; }

nav {
    background: white;
    padding: 16px 7%;
    box-shadow: 0 2px 5px #ddd;
}

nav a {
    margin-right: 22px;
    color: #163a63;
    text-decoration: none;
    font-weight: bold;
}

main {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}

.welcome, .notice {
    background: white;
    padding: 22px;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 10px #0000000d;
}

.welcome h2 { color: #163a63; }

.notice {
    border-left: 5px solid #18794e;
}

.category {
    display: inline-block;
    background: #e0f2e8;
    color: #18794e;
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 13px;
}

.date {
    color: #666;
    font-size: 13px;
}

.download {
    display: inline-block;
    margin-top: 10px;
    color: #163a63;
    font-weight: bold;
}

.empty {
    background: white;
    padding: 25px;
    border-radius: 10px;
}

@media (max-width: 600px) {
    nav a {
        display: inline-block;
        margin: 8px 12px 8px 0;
    }
}
</style>
</head>
<body>

<header>
    <h1>🎓 College Notice Board</h1>
    <p>Student Dashboard | Stay updated with college announcements</p>
</header>

<nav>
    <a href="student_dashboard.php">Dashboard</a>
    <a href="view_notices.php">View Notices</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <section class="welcome">
        <h2>
            Welcome,
            <?= htmlspecialchars($_SESSION["name"] ?? "Student", ENT_QUOTES, "UTF-8") ?>!
        </h2>
        <p>Your role: <strong>Student</strong></p>
        <p>Read the latest college announcements and important updates below.</p>
        <p>Total available notices: <strong><?= count($notices) ?></strong></p>
    </section>

    <h2>Latest Notices</h2>

    <?php if (count($notices) > 0): ?>
        <?php foreach ($notices as $notice): ?>
            <article class="notice">
                <h3>
                    <?= htmlspecialchars($notice["title"], ENT_QUOTES, "UTF-8") ?>
                </h3>

                <span class="category">
                    <?= htmlspecialchars($notice["category"], ENT_QUOTES, "UTF-8") ?>
                </span>

                <p>
                    <?= nl2br(htmlspecialchars($notice["description"], ENT_QUOTES, "UTF-8")) ?>
                </p>

                <p class="date">
                    Published:
                    <?= htmlspecialchars($notice["created_at"], ENT_QUOTES, "UTF-8") ?>
                </p>

                <?php if (!empty($notice["attachment"])): ?>
                    <a class="download"
                       href="uploads/<?= rawurlencode(basename($notice["attachment"])) ?>"
                       target="_blank" rel="noopener">
                        View / Download Attachment
                    </a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">
            No published notices are available right now.
        </div>
    <?php endif; ?>
</main>

</body>
</html>
