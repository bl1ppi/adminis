<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM laptops WHERE id = ?");
$stmt->execute([$id]);
$laptop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laptop) {
    die("Запись не найдена");
}

$teachers = $pdo->query("SELECT id, full_name FROM teachers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM laptops WHERE id = ?")->execute([$id]);
        header("Location: index.php");
        exit;
    }

    $number = (int)($_POST['number'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $room_id = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;
    $is_permanent = isset($_POST['is_permanent']) ? 1 : 0;
    $start_date = $is_permanent ? null : ($_POST['start_date'] ?? null);
    $end_date = $is_permanent ? null : ($_POST['end_date'] ?? null);
    $status = $_POST['status'] ?? 'взят';
    $comment = trim($_POST['comment'] ?? '');

    if ($number <= 0 || $teacher_id <= 0 || (!$is_permanent && empty($start_date))) {
        $error = "Пожалуйста, заполните все обязательные поля.";
    } else {
        $stmt = $pdo->prepare("UPDATE laptops SET number=?, teacher_id=?, room_id=?, start_date=?, end_date=?, status=?, comment=?, is_permanent=? WHERE id=?");
        $stmt->execute([$number, $teacher_id, $room_id, $start_date, $end_date ?: null, $status, $comment, $is_permanent, $id]);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование ноутбука</title>
    <link rel="stylesheet" href="../includes/style.css">
    <script>
        function toggleDateFields() {
            const checkbox = document.getElementById('permanent');
            const dateStart = document.getElementById('date_start');
            const dateEnd = document.getElementById('date_end');
            const labelStart = document.getElementById('label_date_start');
            const labelEnd = document.getElementById('label_date_end');
            const roomRow = document.getElementById('room_row');

            const isChecked = checkbox.checked;

            dateStart.disabled = isChecked;
            dateEnd.disabled = isChecked;

            labelStart.style.opacity = isChecked ? 0.4 : 1;
            labelEnd.style.opacity = isChecked ? 0.4 : 1;
            roomRow.style.opacity = isChecked ? 0.4 : 1;
        }

        document.addEventListener('DOMContentLoaded', toggleDateFields);
    </script>
</head>
<body>
    <h1>Редактирование записи о ноутбуке</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Номер ноутбука:<br>
            <input type="number" name="number" min="1" value="<?= htmlspecialchars($laptop['number']) ?>" required>
        </label><br><br>

        <label>ФИО преподавателя:<br>
            <select name="teacher_id" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $laptop['teacher_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label>
            <input type="checkbox" name="is_permanent" id="permanent" onchange="toggleDateFields()" <?= $laptop['is_permanent'] ? 'checked' : '' ?>>
            Выдан в постоянное пользование
        </label><br><br>

        <label id="room_row">Кабинет:<br>
            <select name="room_id">
                <option value="">-- Не указан --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $laptop['room_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label id="label_date_start">Дата выдачи:<br>
            <input type="date" name="start_date" id="date_start" value="<?= htmlspecialchars($laptop['start_date'] ?? $today) ?>">
        </label><br><br>

        <label id="label_date_end">Дата возврата:<br>
            <input type="date" name="end_date" id="date_end" value="<?= htmlspecialchars($laptop['end_date'] ?? $today) ?>">
        </label><br><br>

        <label>Статус:<br>
            <select name="status">
                <option value="взят" <?= $laptop['status'] === 'взят' ? 'selected' : '' ?>>Взят</option>
                <option value="сдан" <?= $laptop['status'] === 'сдан' ? 'selected' : '' ?>>Сдан</option>
            </select>
        </label><br><br>

        <label>Комментарий:<br>
            <textarea name="comment" rows="4" cols="50"><?= htmlspecialchars($laptop['comment']) ?></textarea>
        </label><br><br>

        <button type="submit">💾 Сохранить</button>
        <a href="index.php">Отмена</a>
    </form>

    <form method="post" onsubmit="return confirm('Вы действительно хотите удалить эту запись?');" style="margin-top: 20px;">
        <input type="hidden" name="delete" value="1">
        <button type="submit" style="color: red;">🗑️ Удалить запись</button>
    </form>

    <script>toggleDateFields();</script>
</body>
</html>
