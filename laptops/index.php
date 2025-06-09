<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

// Получение данных
$teachers = $pdo->query("SELECT id, full_name FROM teachers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$statuses = ['взят', 'сдан'];

$filters = [
    'teacher_id' => $_GET['teacher_id'] ?? '',
    'number' => $_GET['number'] ?? '',
    'status' => $_GET['status'] ?? 'взят',
    'show_permanent' => isset($_GET['show_permanent']),
    'show_temporary' => isset($_GET['show_temporary']) || !isset($_GET['show_permanent']),
];

$where = [];
$params = [];

if ($filters['teacher_id']) {
    $where[] = 'l.teacher_id = ?';
    $params[] = $filters['teacher_id'];
}

if ($filters['number']) {
    $where[] = 'l.number = ?';
    $params[] = $filters['number'];
}

if ($filters['status']) {
    $where[] = 'l.status = ?';
    $params[] = $filters['status'];
}

if ($filters['show_permanent'] && !$filters['show_temporary']) {
    $where[] = 'l.is_permanent = 1';
} elseif (!$filters['show_permanent'] && $filters['show_temporary']) {
    $where[] = 'l.is_permanent = 0';
} elseif (!$filters['show_permanent'] && !$filters['show_temporary']) {
    $where[] = '1 = 0';
}

$sql = "
    SELECT l.*, t.full_name, r.name AS room_name
    FROM laptops l
    JOIN teachers t ON l.teacher_id = t.id
    LEFT JOIN rooms r ON l.room_id = r.id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY l.start_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laptops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Учёт ноутбуков</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
	    .layout-wrapper {
	      display: flex !important;
	    }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <div class="sidebar">
        <form method="get">
            <h3>🔍 Фильтрация</h3>

            <label>👨‍🏫 Преподаватель:<br>
                <select name="teacher_id" style="width: 100%;">
                    <option value="">-- Все --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filters['teacher_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['full_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label><br><br>

            <label>💻 Ноутбук №:<br>
                <input type="number" name="number" value="<?= htmlspecialchars($filters['number']) ?>" style="width: 100%;">
            </label><br><br>

            <label>📥 Статус:<br>
                <select name="status" style="width: 100%;">
                    <option value="">-- Все --</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label><br><br>

            <label><input type="checkbox" name="show_permanent" value="1" <?= $filters['show_permanent'] ? 'checked' : '' ?>>
                Долгосрочные
            </label><br>

            <label><input type="checkbox" name="show_temporary" value="1" <?= $filters['show_temporary'] ? 'checked' : '' ?>>
                Временные
            </label><br><br>

            <button type="submit">Применить</button>
            <a href="export_laptops.php?<?= http_build_query($_GET) ?>" target="_blank">📤 Экспорт в CSV</a>
        </form>
    </div>

    <div class="main">
        <div class="top-actions">
            <a href="add_laptop.php">➕ Выдать ноутбук</a>
            <a href="teachers.php">👨‍🏫 Преподаватели</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ФИО преподавателя</th>
                    <th>Кабинет</th>
                    <th>№ ноутбука</th>
                    <th>Дата выдачи</th>
                    <th>Дата возврата</th>
                    <th>Статус</th>
                    <th>Долгосрочно</th>
                    <th>Комментарий</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$laptops): ?>
                    <tr><td colspan="10" style="text-align: center; color: gray;">Нет записей</td></tr>
                <?php else: ?>
                    <?php foreach ($laptops as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= $row['is_permanent'] ? '—' : htmlspecialchars($row['room_name'] ?? '—') ?></td>
                            <td>№<?= (int)$row['number'] ?></td>
                            <td><?= $row['is_permanent'] ? '—' : ($row['start_date'] ?? '—') ?></td>
                            <td><?= $row['is_permanent'] ? '—' : ($row['end_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= $row['is_permanent'] ? '✅' : '—' ?></td>
                            <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                            <td class="actions">
                                <a href="edit_laptop.php?id=<?= $row['id'] ?>">✏Редактировать</a>
                            </td>
                            </tr>
                            
                            <?php if ($row['status'] === 'взят'): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 10px;">
                                    <a href="../mark_returned.php?id=<?= $row['id'] ?>" 
                                       onclick="return confirm('Подтвердить возврат ноутбука?')" 
                                       style="display: inline-block; padding: 6px 12px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">
                                       ✅ Сдан
                                    </a>
                                </td>
                            </tr>
                            <?php endif ?>
                            </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
