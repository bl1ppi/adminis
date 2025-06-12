<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$rangeMin = isset($_GET['range']) ? (int)$_GET['range'] : 1440;
$since = date('Y-m-d H:i:s', time() - $rangeMin * 60);

$sql = "
  SELECT server_id, UNIX_TIMESTAMP(timestamp) AS t, cpu_used, mem_used
  FROM server_stats
  WHERE timestamp >= ?
  ORDER BY server_id, timestamp ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$since]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
