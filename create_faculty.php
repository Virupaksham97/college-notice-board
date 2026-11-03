
<?php
require_once "db.php";

$name = "Demo Faculty";
$email = "faculty@college.com";
$password = "Faculty@123";

$check = $pdo->prepare(
    "SELECT id FROM users WHERE email = ?"
);
$check->execute([$email]);

if ($check->fetch()) {
    exit("This faculty account already exists. No changes were made.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password, role)
     VALUES (?, ?, ?, 'faculty')"
);

$stmt->execute([$name, $email, $hashedPassword]);

echo "Faculty account created successfully!";
echo "<br>Email: " . htmlspecialchars($email);
echo "<br>Password: " . htmlspecialchars($password);
echo "<br><strong>Delete create_faculty.php after running it.</strong>";
?>
