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
    <title>Редактирование записи о ноутбуке</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
<div class="container py-4">
    <h1 class="mb-4 text-center">Редактирование записи о ноутбуке</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Номер ноутбука</label>
            <input type="number" name="number" min="1" value="<?= htmlspecialchars($laptop['number']) ?>" required class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">ФИО преподавателя</label>
            <select name="teacher_id" class="form-select" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $laptop['teacher_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_permanent" id="permanent" class="form-check-input" onchange="toggleDateFields()" <?= $laptop['is_permanent'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="permanent">Выдан в постоянное пользование</label>
            </div>
        </div>

        <div class="col-md-6" id="room_row">
            <label class="form-label">Кабинет</label>
            <select name="room_id" class="form-select">
                <option value="">-- Не указан --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $laptop['room_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3" id="label_date_start">
            <label class="form-label">Дата выдачи</label>
            <input type="date" name="start_date" id="date_start" value="<?= htmlspecialchars($laptop['start_date'] ?? $today) ?>" class="form-control">
        </div>

        <div class="col-md-3" id="label_date_end">
            <label class="form-label">Дата возврата</label>
            <input type="date" name="end_date" id="date_end" value="<?= htmlspecialchars($laptop['end_date'] ?? $today) ?>" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <option value="взят" <?= $laptop['status'] === 'взят' ? 'selected' : '' ?>>Взят</option>
                <option value="сдан" <?= $laptop['status'] === 'сдан' ? 'selected' : '' ?>>Сдан</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Комментарий</label>
            <textarea name="comment" rows="4" class="form-control"><?= htmlspecialchars($laptop['comment']) ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-center gap-4 mt-4">
            <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
            <button type="submit" name="delete" class="btn btn-outline-danger" onclick="return confirm('Удалить это устройство?');">🗑️ Удалить</button>
            <a href="index.php" class="btn btn-outline-secondary">🚫 Отмена</a>
        </div>
    </form>
</div>
</body>
</html>

