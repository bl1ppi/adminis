<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
require_once 'room_model.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID кабинета.");
}

$room_id = (int) $_GET['id'];

// Получаем данные кабинета
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Кабинет не найден.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = "Название кабинета не может быть пустым.";
        } else {
            updateRoom($pdo, $room_id, $name, $description);
            header("Location: index.php");
            exit;
        }
    }

    if (isset($_POST['duplicate'])) {
        $newId = duplicateRoom($pdo, $room_id);
        header("Location: edit_room.php?id=$newId");
        exit;
    }

    if (isset($_POST['delete'])) {
        deleteRoom($pdo, $room_id);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать кабинет</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h1>Редактировать кабинет</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Название кабинета:<br>
            <input type="text" name="name" value="<?= htmlspecialchars($room['name']) ?>" required>
        </label><br><br>

        <label>Описание:<br>
            <textarea name="description" rows="4" cols="50"><?= htmlspecialchars($room['description']) ?></textarea>
        </label><br><br>

        <button type="submit" name="update">💾 Сохранить</button>
        <form method="post" style="margin-top:20px;">
            <button type="submit" name="duplicate">📄 Дублировать кабинет</button>
            <a href="index.php">Отмена</a>
        </form>
    </form>

    <form method="post" onsubmit="return confirm('Вы действительно хотите удалить кабинет и все его устройства?');" style="margin-top:20px;">
        <button type="submit" name="delete">🗑️ Удалить кабинет</button>
    </form>
</body>
</html>
