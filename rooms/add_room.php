<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
require_once 'room_model.php';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = "Название кабинета обязательно.";
    } else {
        createRoom($pdo, $name, $description);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить кабинет</title>
 	<link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h1>Добавление кабинета</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Название кабинета:<br>
            <input type="text" name="name" required>
        </label><br><br>

        <label>Описание (необязательно):<br>
            <textarea name="description" rows="4" cols="50"></textarea>
        </label><br><br>

        <button type="submit">Сохранить</button>
        <a href="index.php">Отмена</a>
    </form>
</body>
</html>
