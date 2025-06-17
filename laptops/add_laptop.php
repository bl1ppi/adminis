<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

// Получаем список преподавателей и кабинетов
$teachers = $pdo->query("SELECT id, full_name FROM teachers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = (int)($_POST['number'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $room_id = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;
    $is_permanent = isset($_POST['is_permanent']) ? 1 : 0;
    $start_date = $is_permanent ? null : ($_POST['start_date'] ?? null);
    $end_date = $is_permanent ? null : ($_POST['end_date'] ?? null);
    $status = $_POST['status'] ?? 'взят';
    $comment = trim($_POST['comment'] ?? '');
	$today = date('Y-m-d');

    if ($number <= 0 || $teacher_id <= 0 || (!$is_permanent && empty($start_date))) {
        $error = "Пожалуйста, заполните все обязательные поля.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO laptops (number, teacher_id, room_id, start_date, end_date, status, comment, is_permanent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$number, $teacher_id, $room_id, $start_date, $end_date ?: null, $status, $comment, $is_permanent]);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выдача ноутбука преподавателю</title>
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

            if (isChecked) {
                dateStart.value = '';
                dateEnd.value = '';
            } else {
                const today = new Date().toISOString().split('T')[0];
                if (!dateStart.value) dateStart.value = today;
                if (!dateEnd.value) dateEnd.value = today;
            }

            labelStart.style.opacity = isChecked ? 0.4 : 1;
            labelEnd.style.opacity = isChecked ? 0.4 : 1;
            roomRow.style.opacity = isChecked ? 0.4 : 1;
        }

        document.addEventListener('DOMContentLoaded', toggleDateFields);
    </script>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4 text-center">Выдача ноутбука преподавателю</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Номер ноутбука</label>
            <input type="number" name="number" min="1" required class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">ФИО преподавателя</label>
            <select name="teacher_id" class="form-select" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_permanent" id="permanent" class="form-check-input" onchange="toggleDateFields()">
                <label class="form-check-label" for="permanent">Выдан в постоянное пользование</label>
            </div>
        </div>

        <div class="col-md-6" id="room_row">
            <label class="form-label">Кабинет</label>
            <select name="room_id" class="form-select">
                <option value="">-- Не указан --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3" id="label_date_start">
            <label class="form-label">Дата выдачи</label>
            <input type="date" name="start_date" id="date_start" value="<?= $today ?>" class="form-control">
        </div>

        <div class="col-md-3" id="label_date_end">
            <label class="form-label">Дата возврата</label>
            <input type="date" name="end_date" id="date_end" value="<?= $today ?>" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <option value="взят" selected>Взят</option>
                <option value="сдан">Сдан</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Комментарий</label>
            <textarea name="comment" rows="4" class="form-control"></textarea>
        </div>

        <div class="col-12 d-flex justify-content-center gap-4 mt-4">
            <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
            <a href="index.php" class="btn btn-outline-secondary">🚫 Отмена</a>
        </div>
    </form>
</div>
</body>
</html>
