<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
require_once 'room_model.php';
require_once '../includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID кабинета.");
}

$room_id = (int) $_GET['id'];
$room = getRoomById($pdo, $room_id);

if (!$room) {
    die("Кабинет не найден.");
}

$devices = getDevicesByRoom($pdo, $room_id);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($room['name']) ?> — Список устройств</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        td.icon-cell img {
            width: 24px;
            height: 24px;
            vertical-align: middle;
            margin-right: 6px;
        }
        tr.clickable-row {
            cursor: pointer;
        }
        .table-container {
            max-width: 80%;
        }
        td.comment-cell {
            max-width: 300px;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
<div class="container-fluid py-4">
    <div class="container table-container">
        <div class="mb-4">    
            <h1 class="mb-3"><?= htmlspecialchars($room['name']) ?></h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">← Назад к кабинетам</a>
            <a href="edit_room.php?id=<?= $room_id ?>" class="btn btn-outline-success btn-sm">Редактировать комнату</a>
        </div>

        <?php if ($room['description']): ?>
            <p class="mb-4"><strong>Описание:</strong> <?= nl2br(htmlspecialchars($room['description'])) ?></p>
        <?php endif; ?>

        <?php if (count($devices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Устройство</th>
                            <th>Тип</th>
                            <th>IP</th>
                            <th>MAC</th>
                            <th>Инв. №</th>
                            <th>Статус</th>
                            <th>Подключено к</th>
                            <th>Комментарий</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                            <tr class="clickable-row" onclick="window.location.href='edit_device.php?id=<?= $device['id'] ?>'">
                                <td class="icon-cell">
                                    <?php
                                        $folder = mapTypeToFolder($device['type']);
                                        $icon = htmlspecialchars($device['icon']);
                                        $path = "../assets/icons/{$folder}/{$icon}";
                                        if ($icon && file_exists($path)) {
                                            echo "<img src=\"$path\" alt=\"\"> ";
                                        }
                                        echo htmlspecialchars($device['name']);
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($device['type']) ?></td>
                                <td><?= htmlspecialchars($device['ip']) ?></td>
                                <td><?= htmlspecialchars($device['mac']) ?></td>
                                <td><?= htmlspecialchars($device['inventory_number']) ?></td>
                                <td><?= htmlspecialchars($device['status']) ?></td>
                                <td>
                                    <?php
                                        $connected = getDeviceConnectionName($pdo, $device['id']);
                                        echo $connected ? "→ " . htmlspecialchars($connected) : "—";
                                    ?>
                                </td>
                                <td class="comment-cell"><?= nl2br(htmlspecialchars($device['comment'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">В этом кабинете пока нет устройств.</div>
        <?php endif; ?>
        <a href="add_device.php?room_id=<?= $room_id ?>" class="btn btn-outline-success w-100">➕ Добавить устройство</a>
    </div>
</div>

</body>
</html>
