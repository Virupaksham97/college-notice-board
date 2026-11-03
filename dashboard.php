
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "db.php";


$username = $_SESSION["name"] ?? "User";
$role = $_SESSION["role"] ?? "student";

$totalNotices = 0;
$totalCategories = 0;
$recentNotices = [];
$dbError = false;

try {
    // Total published notices that have not expired
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM notices
         WHERE status = 'published'
         AND (expiry_date IS NULL OR expiry_date >= CURDATE())"
    );
    $totalNotices = (int) $stmt->fetchColumn();

    // Number of categories used by published notices
    $stmt = $pdo->query(
        "SELECT COUNT(DISTINCT category)
         FROM notices
         WHERE status = 'published'
         AND (expiry_date IS NULL OR expiry_date >= CURDATE())"
    );
    $totalCategories = (int) $stmt->fetchColumn();

    // Latest five published notices
    $stmt = $pdo->query(
        "SELECT
            n.id,
            n.title,
            n.category,
            n.created_at,
            u.name AS author
         FROM notices n
         INNER JOIN users u ON n.admin_id = u.id
         WHERE n.status = 'published'
         AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())
         ORDER BY n.created_at DESC
         LIMIT 5"
    );

    $recentNotices = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard database error: " . $e->getMessage());
    $dbError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - College Notice Board</title>

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
            padding: 22px 30px;
            background: #163a63;
            color: white;
        }

        header h1 {
            margin: 0;
            font-size: 25px;
        }

        header p {
            margin-bottom: 0;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 15px 25px;
            background: white;
            box-shadow: 0 2px 5px #ddd;
        }

        nav a {
            padding: 9px 12px;
            color: #163a63;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
        }

        nav a:hover {
            background: #e7eef7;
        }

        .container {
            width: 92%;
            max-width: 1150px;
            margin: 30px auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            margin-bottom: 8px;
            color: #163a63;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #163a63;
        }

        .stat-card h3 {
            margin: 0 0 12px;
            color: #555;
            font-size: 16px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #163a63;
        }

        .section {
            margin-bottom: 25px;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .section h2 {
            margin-top: 0;
            color: #163a63;
        }

        .notice {
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .notice:last-child {
            border-bottom: none;
        }

        .notice h3 {
            margin: 0 0 8px;
            color: #163a63;
        }

        .notice p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            background: #e7eef7;
            color: #163a63;
            border-radius: 15px;
            font-size: 12px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .action-link {
            display: inline-block;
            padding: 12px 18px;
            background: #163a63;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .action-link:hover {
            background: #285c91;
        }

        .empty {
            color: #666;
            padding: 15px 0;
        }

        .error {
            color: #b00020;
        }

        @media (max-width: 600px) {
            header {
                padding: 20px;
            }

            .container {
                width: 94%;
                margin: 20px auto;
            }

            .section {
                padding: 18px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>🎓 College Notice Board</h1>
    <p>Dashboard | Manage college announcements in one place</p>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>

    <?php if ($role === "admin"): ?>
        <a href="post_notice.php">Post Notice</a>
    <?php endif; ?>

    <a href="view_notices.php">View Notices</a>
    <a href="logout.php">Logout</a>
</nav>

<main class="container">

    <div class="welcome">
        <h2>
            Welcome,
            <?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>!
        </h2>

        <p>
            Your role:
            <strong>
                <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, "UTF-8") ?>
            </strong>
        </p>
    </div>

    <?php if ($dbError): ?>
        <p class="error">
            Unable to load dashboard information.
            Please check your database and table structure.
        </p>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <h3>Total Notices</h3>
            <div class="number"><?= $totalNotices ?></div>
        </div>

        <div class="stat-card">
            <h3>Notice Categories</h3>
            <div class="number"><?= $totalCategories ?></div>
        </div>

        <div class="stat-card">
            <h3>Account Role</h3>
            <div class="number" style="font-size: 25px;">
                <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, "UTF-8") ?>
            </div>
        </div>
    </div>

    <section class="section">
        <h2>Quick Actions</h2>

        <div class="actions">
            <?php if ($role === "admin"): ?>
                <a class="action-link" href="post_notice.php">
                    + Post New Notice
                </a>
            <?php endif; ?>

            <a class="action-link" href="view_notices.php">
                View All Notices
            </a>
        </div>
    </section>

    <section class="section">
        <h2>Recent Notices</h2>

        <?php if (empty($recentNotices)): ?>
            <p class="empty">No notices have been published yet.</p>
        <?php else: ?>
            <?php foreach ($recentNotices as $notice): ?>
                <div class="notice">
                    <h3>
                        <?= htmlspecialchars(
                            $notice["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h3>

                    <span class="badge">
                        <?= htmlspecialchars(
                            $notice["category"] ?: "General",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>

                    <p>
                        Posted by:
                        <?= htmlspecialchars(
                            $notice["author"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <p>
                        Date:
                        <?= htmlspecialchars(
                            $notice["created_at"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p>
            <a href="view_notices.php">View all notices →</a>
        </p>
    </section>

</main>

</body>
</html>
