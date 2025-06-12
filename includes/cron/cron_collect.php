<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$servers = $pdo->query("SELECT * FROM servers ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

foreach ($servers as $srv) {
    $stats = collectServerStats($srv);
    $stmt = $pdo->prepare("
        INSERT INTO server_stats 
        (server_id, cpu_used, mem_used, mem_total, disk, services)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $srv['id'],
        $stats['cpu'].load ?? $stats['cpu'],    // поправь в функции если нужно
        $stats['memory'].used ?? $stats['memory'],
        $stats['memory'].total ?? 0,
        json_encode($stats['disks'], JSON_UNESCAPED_UNICODE),
        json_encode($stats['services'], JSON_UNESCAPED_UNICODE),
    ]);
}
