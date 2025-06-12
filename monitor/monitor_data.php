<?php
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$servers = $pdo->query("SELECT * FROM servers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$result = [];

foreach ($servers as $srv) {
    $data = collectServerStats($srv);
    $result[] = array_merge(
        [
            'id' => $srv['id'],
            'name' => $srv['name'],
            'ip' => $srv['ip']
        ],
        $data
    );
}

header('Content-Type: application/json');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
