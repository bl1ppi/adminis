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
    <title>Выдача ноутбука</title>
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
    <h1>Выдача ноутбука преподавателю</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Номер ноутбука:<br>
            <input type="number" name="number" min="1" required>
        </label><br><br>

        <label>ФИО преподавателя:<br>
            <select name="teacher_id" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

		<label>
			<input type="checkbox" name="is_permanent" id="permanent" onchange="toggleDateFields()">
			Выдан в постоянное пользование
		</label><br><br>

        <label id="room_row">Кабинет:<br>
            <select name="room_id">
                <option value="">-- Не указан --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

		<label id="label_date_start">Дата выдачи:<br>
		    <input type="date" name="start_date" id="date_start" value="<?= $today ?>">
		</label><br><br>

		<label id="label_date_end">Дата возврата:<br>
		    <input type="date" name="end_date" id="date_end" value="<?= $today ?>">
		</label><br><br>

        <label>Статус:<br>
            <select name="status">
                <option value="взят" selected>Взят</option>
                <option value="сдан">Сдан</option>
            </select>
        </label><br><br>

        <label>Комментарий:<br>
            <textarea name="comment" rows="4" cols="50"></textarea>
        </label><br><br>

        <button type="submit">Сохранить</button>
        <a href="index.php">Отмена</a>
    </form>
</body>
</html>
