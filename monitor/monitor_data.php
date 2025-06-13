<?php
require_once '../includes/db.php';

$servers = $pdo->query("SELECT * FROM servers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$result = [];

foreach ($servers as $srv) {
    $stmt = $pdo->prepare("SELECT * FROM server_stats WHERE server_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$srv['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $isOnline = false;
    if ($stats && isset($stats['created_at'])) {
        $isOnline = strtotime($stats['created_at']) >= (time() - 60);
    }

    $result[] = [
        'id' => $srv['id'],
        'name' => $srv['name'],
        'ip' => $srv['ip'],
        'status' => $isOnline ? 'online' : 'offline',
        'cpu' => [
            'history' => $stats ? [[ 't' => strtotime($stats['created_at']), 'v' => $stats['cpu_used'] ]] : []
        ],
        'memory' => [
            'used' => (int)($stats['mem_used'] ?? 0),
            'total' => (int)($stats['mem_total'] ?? 8192)
        ],
        'disks' => $stats ? json_decode($stats['disk'], true) ?? [] : [],
        'services' => $stats ? json_decode($stats['services'], true) ?? [] : []
    ];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
