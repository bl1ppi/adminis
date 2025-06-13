<?php
require_once '../includes/db.php';

$rangeMin = isset($_GET['range']) ? (int)$_GET['range'] : 1440;
$serverId = isset($_GET['server_id']) ? (int)$_GET['server_id'] : null;

// Удаление старых данных
$cutoff = date('Y-m-d H:i:s', time() - 24 * 60 * 60);
$pdo->prepare("DELETE FROM server_stats WHERE created_at < ?")->execute([$cutoff]);

// Получение данных
$since = date('Y-m-d H:i:s', time() - $rangeMin * 60);

$sql = "
  SELECT server_id, UNIX_TIMESTAMP(created_at) AS t, cpu_used, mem_used
  FROM server_stats
  WHERE created_at >= ?
";
$params = [$since];

if ($serverId) {
    $sql .= " AND server_id = ?";
    $params[] = $serverId;
}

$sql .= " ORDER BY server_id, created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Сборка результата
$result = [];
foreach ($rows as $row) {
    $sid = (int)$row['server_id'];
    if (!isset($result[$sid])) {
        $result[$sid] = ['cpu'=>[], 'memory'=>[]];
    }
    $result[$sid]['cpu'][] = ['t' => (int)$row['t'], 'v' => (float)$row['cpu_used']];
    $result[$sid]['memory'][] = ['t' => (int)$row['t'], 'v' => (float)$row['mem_used']];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
