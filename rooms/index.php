<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
require_once 'room_model.php';

$filters = [
    'room_id' => $_GET['room_id'] ?? null,
    'device_type' => $_GET['device_type'] ?? null,
    'status' => $_GET['status'] ?? null,
];

$rooms = getRooms($pdo, $filters);
$roomList = getRoomList($pdo);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Учет оборудования — Главная</title>
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
        <form method="GET">
            <h5 class="mb-3">🔍 Фильтрация</h5>

            <div class="mb-3">
                <label for="room_id" class="form-label">Кабинет:</label>
                <select id="room_id" name="room_id" class="form-select">
                    <option value="">Все</option>
                    <?php
                    $roomList = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
                    foreach ($roomList as $r) {
                        $sel = ($_GET['room_id'] ?? '') == $r['id'] ? 'selected' : '';
                        echo "<option value=\"{$r['id']}\" $sel>" . htmlspecialchars($r['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="device_type" class="form-label">Тип устройства:</label>
                <select id="device_type" name="device_type" class="form-select">
                    <option value="">Все</option>
                    <?php
                    $types = ['ПК', 'Сервер', 'Принтер', 'Маршрутизатор', 'Свитч', 'МФУ', 'Интерактивная доска', 'Прочее'];
                    foreach ($types as $type) {
                        $sel = ($_GET['device_type'] ?? '') == $type ? 'selected' : '';
                        echo "<option $sel>$type</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">📥 Статус:</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Все</option>
                    <?php
                    $statuses = ['В работе', 'На ремонте', 'Списан', 'На хранении', 'Числится за кабинетом'];
                    foreach ($statuses as $status) {
                        $sel = ($_GET['status'] ?? '') == $status ? 'selected' : '';
                        echo "<option $sel>$status</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-outline-primary w-100">🔍 Применить</button>
        </form>

        <form method="POST" action="export_rooms.php">
            <input type="hidden" name="room_id" value="<?= htmlspecialchars($_GET['room_id'] ?? '') ?>">
            <input type="hidden" name="device_type" value="<?= htmlspecialchars($_GET['device_type'] ?? '') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-success w-100">⬇️ Экспорт в CSV</button>
        </form>
    </div>

    <div class="content">
        <h1 class="mb-4">Учёт оборудования</h1>

        <?php if (count($rooms) === 0): ?>
            <p class="text-center">Кабинеты пока не добавлены.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Кабинет</th>
                            <th style="width: 50%;">Описание</th>
                            <th>Устройств</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr onclick="window.location.href='room.php?id=<?= $room['id'] ?>'" style="cursor: pointer;">
                                <td><?= htmlspecialchars($room['name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($room['description'])) ?></td>
                                <td class="text-center"><?= $room['device_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a class="btn btn-outline-success w-100" href="add_room.php">Добавить кабинет</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
