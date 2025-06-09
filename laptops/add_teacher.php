<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    if ($full_name === '') {
        $error = "Введите ФИО.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO teachers (full_name) VALUES (?)");
        $stmt->execute([$full_name]);
        header("Location: teachers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить преподавателя</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h1>Добавить преподавателя</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>ФИО преподавателя:<br>
            <input type="text" name="full_name" required style="width: 300px;">
        </label><br><br>
        <button type="submit">Сохранить</button>
        <a href="teachers.php">Отмена</a>
    </form>
</body>
</html>
