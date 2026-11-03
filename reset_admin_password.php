
<?php
require_once "db.php";

$email = "madhugowri047@gmail.com";
$newPassword = "12345678";

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "UPDATE users
     SET password = ?, role = 'admin'
     WHERE email = ?"
);

$stmt->execute([$hashedPassword, $email]);

if ($stmt->rowCount() > 0) {
    echo "Admin password has been updated successfully.";
} else {
    echo "No row changed. Check whether the email exists and the password is already set.";
}
?>
