<?php
function mapTypeToFolder(string $type): string {
    return [
        'ПК' => 'pc',
        'Сервер' => 'server',
        'Принтер' => 'printer',
        'Маршрутизатор' => 'router',
        'Свитч' => 'switch',
        'МФУ' => 'mfu',
        'Интерактивная доска' => 'board',
        'Прочее' => 'other',
    ][$type] ?? 'other';
}

function getMapData(PDO $pdo): array {
    $devices = $pdo->query("SELECT d.*, r.name AS room_name, r.id AS room_id FROM devices d JOIN rooms r ON d.room_id = r.id ORDER BY r.id")->fetchAll(PDO::FETCH_ASSOC);
    $links = $pdo->query("SELECT * FROM switch_links")->fetchAll(PDO::FETCH_ASSOC);

    $nodes = [];
    $edges = [];
    $roomGroups = [];

    foreach ($devices as $device) {
        $roomGroups[$device['room_id']]['devices'][] = $device;
        $roomGroups[$device['room_id']]['room_name'] = $device['room_name'];
    }

    foreach ($roomGroups as $roomId => $group) {
        $groupId = "room_$roomId";
        $nodes[] = [
            'key' => $groupId,
            'isGroup' => true,
            'text' => $group['room_name']
        ];
        foreach ($group['devices'] as $device) {
            $label = $device['name'];
            if (!empty($device['ip'])) {
                $label .= "\nIP: " . $device['ip'];
            }
            $nodes[] = [
                'key' => (int)$device['id'],
                'text' => $label,
                'group' => $groupId,
                'icon' => '../assets/icons/' . mapTypeToFolder($device['type']) . '/' . $device['icon']
            ];
        }
    }

    foreach ($links as $link) {
        $edges[] = [
            'from' => (int)$link['connected_to_device_id'],
            'to' => (int)$link['device_id']
        ];
    }

    return ['nodes' => $nodes, 'edges' => $edges];
}
