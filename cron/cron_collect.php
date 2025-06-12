<?php
require_once '../includes/db.php';
require_once '../includes/helpers.php';

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
        $stats['cpu']['used'] ?? 0,
        $stats['memory']['used'] ?? 0,
        $stats['memory']['total'] ?? 0,
        json_encode($stats['disks'], JSON_UNESCAPED_UNICODE),
        json_encode($stats['services'], JSON_UNESCAPED_UNICODE),
    ]);
}
