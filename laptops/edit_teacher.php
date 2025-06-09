<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID преподавателя.");
}

$id = (int) $_GET['id'];

// Получаем текущие данные преподавателя
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die("Преподаватель не найден.");
}

// Обработка формы
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Удаление
        $pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
        header("Location: teachers.php");
        exit;
    }

    $full_name = trim($_POST['full_name'] ?? '');

    if ($full_name === '') {
        $error = "ФИО не может быть пустым.";
    } else {
        $stmt = $pdo->prepare("UPDATE teachers SET full_name = ? WHERE id = ?");
        $stmt->execute([$full_name, $id]);
        header("Location: teachers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать преподавателя</title>
    <link rel="stylesheet" href="../includes/style.css">
    <script>
        function confirmDelete() {
            return confirm("Вы действительно хотите удалить этого преподавателя?");
        }
    </script>
</head>
<body>
    <h1>Редактировать преподавателя</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>ФИО:<br>
            <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
        </label><br><br>

        <button type="submit">💾 Сохранить</button>
        <button type="submit" name="delete" onclick="return confirmDelete()" style="margin-left: 10px; color: red;">🗑️ Удалить</button>
        <a href="teachers.php">Назад</a>
    </form>
</body>
</html>
