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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .layout-wrapper {
            display: flex;
        }
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        .content {
            flex-grow: 1;
            padding: 30px;
        }
        .p-center, .href-center {
            text-align: center;
            display: block;
        }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <div class="sidebar min-vh-100 bg-light p-3">
        <form method="get">
            <h5 class="mb-3">🔍 Фильтрация</h5>

            <div class="mb-3">
                <label class="form-label">👨‍🏫 Преподаватель</label>
                <select name="teacher_id" class="form-select">
                    <option value="">-- Все --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filters['teacher_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['full_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">💻 Ноутбук №</label>
                <input type="number" name="number" value="<?= htmlspecialchars($filters['number']) ?>" class="form-input w-100">
            </div>

            <div class="mb-3">
                <label class="form-label">📥 Статус</label>
                <select name="status" class="form-select">
                    <option value="">-- Все --</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="form-check">
                <label class="form-check-label"><input type="checkbox" name="show_permanent" class="form-check-input" value="1" <?= $filters['show_permanent'] ? 'checked' : '' ?>>
                    Долгосрочные
                </label>
            </div>

            <div class="form-check">
                <label class="form-check-label"><input type="checkbox" name="show_temporary" class="form-check-input" value="1" <?= $filters['show_temporary'] ? 'checked' : '' ?>>
                    Временные
                </label>
            </div><br>

            <button type="submit" class="btn btn-outline-primary w-100 mb-3">🔍 Применить </button>
            <a href="export_laptops.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success w-100 mb-3" target="_blank">⬇️ Экспорт в CSV </a>
        </form>
    </div>

    <div class="content">
        <h1 class="mb-2">Учёт ноутбуков</h1>

        <div class="mb-4">
            <a href="add_laptop.php" class="btn btn-outline-primary">➕ Выдать ноутбук</a>
            <a href="teachers.php" class="btn btn-outline-primary">👨‍🏫 Преподаватели</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th>ФИО преподавателя</th>
                        <th>Кабинет</th>
                        <th>№ ноутбука</th>
                        <th>Дата выдачи</th>
                        <th>Дата возврата</th>
                        <th>Статус</th>
                        <th>Долгосрочно</th>
                        <th style="width: 20%;">Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$laptops): ?>
                        <tr><td colspan="8" class="text-center text-muted">Нет записей</td></tr>
                    <?php else: ?>
                        <?php foreach ($laptops as $row): ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='edit_laptop.php?id=<?= $row['id'] ?>'">
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= $row['is_permanent'] ? '—' : htmlspecialchars($row['room_name'] ?? '—') ?></td>
                                <td>№<?= (int)$row['number'] ?></td>
                                <td><?= $row['is_permanent'] ? '—' : ($row['start_date'] ?? '—') ?></td>
                                <td><?= $row['is_permanent'] ? '—' : ($row['end_date'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td class="text-center"><?= $row['is_permanent'] ? '✅' : '—' ?></td>
                                <td><?= nl2br(htmlspecialchars($row['comment'])) ?></td>
                            </tr>

                            <?php if ($row['status'] === 'взят'): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="text-center">
                                            <a href="../mark_returned.php?id=<?= $row['id'] ?>"
                                            onclick="return confirm('Подтвердить возврат ноутбука?')"
                                            class="btn btn-outline-success w-100">
                                            ✅ Сдан
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
