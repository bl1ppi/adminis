<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Фильтры из $_GET
$filters = [
    'teacher_id' => $_GET['teacher_id'] ?? '',
    'number' => $_GET['number'] ?? '',
    'status' => $_GET['status'] ?? '',
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
    $where[] = '1 = 0'; // ничего
}

// Запрос
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

// Заголовки CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="laptops_export.csv"');

// BOM для Excel
echo "\xEF\xBB\xBF";

// Открытие потока вывода
$output = fopen('php://output', 'w');

// Заголовки
fputcsv($output, [
    'ID', 'Преподаватель', 'Кабинет', 'Ноутбук №', 'Дата выдачи',
    'Дата возврата', 'Статус', 'Долгосрочно', 'Комментарий'
]);

foreach ($laptops as $row) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['room_name'],
        '№' . $row['number'],
        $row['start_date'],
        $row['end_date'] ?: '',
        $row['status'],
        $row['is_permanent'] ? 'Да' : 'Нет',
        $row['comment']
    ]);
}

fclose($output);
exit;
