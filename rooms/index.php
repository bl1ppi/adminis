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
    <link rel="stylesheet" href="../includes/style.css">
    <style>
	    .layout-wrapper {
	      display: flex !important;
	    }
    </style>
</head>
<body>
    <h1>Учёт оборудования</h1>
    <p>
        <a href="add_room.php">➕ Добавить кабинет</a>
    </p>

    <div class="layout-wrapper">
        <div class="sidebar">
            <form method="GET">
                <h3>🔍 Фильтрация</h3>
                <label>Кабинет:</label>
                <select name="room_id">
                    <option value="">Все</option>
                    <?php
                    $roomList = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
                    foreach ($roomList as $r) {
                        $sel = ($_GET['room_id'] ?? '') == $r['id'] ? 'selected' : '';
                        echo "<option value=\"{$r['id']}\" $sel>" . htmlspecialchars($r['name']) . "</option>";
                    }
                    ?>
                </select>

                <label>Тип устройства:</label>
                <select name="device_type">
                    <option value="">Все</option>
                    <?php
                    $types = ['ПК', 'Сервер', 'Принтер', 'Маршрутизатор', 'Свитч', 'МФУ', 'Интерактивная доска', 'Прочее'];
                    foreach ($types as $type) {
                        $sel = ($_GET['device_type'] ?? '') == $type ? 'selected' : '';
                        echo "<option $sel>$type</option>";
                    }
                    ?>
                </select>

                <label>📥 Статус:</label>
                <select name="status">
                    <option value="">Все</option>
                    <?php
                    $statuses = ['В работе', 'На ремонте', 'Списан', 'На хранении', 'Числится за кабинетом'];
                    foreach ($statuses as $status) {
                        $sel = ($_GET['status'] ?? '') == $status ? 'selected' : '';
                        echo "<option $sel>$status</option>";
                    }
                    ?>
                </select>

                <button type="submit">🔍 Применить</button>
            </form>

            <form method="POST" action="export_rooms.php">
                <input type="hidden" name="room_id" value="<?= htmlspecialchars($_GET['room_id'] ?? '') ?>">
                <input type="hidden" name="device_type" value="<?= htmlspecialchars($_GET['device_type'] ?? '') ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
                <button type="submit">⬇️ Экспорт в CSV</button>
            </form>
        </div>

        <div class="content">
            <?php if (count($rooms) === 0): ?>
                <p>Кабинеты пока не добавлены.</p>
            <?php else: ?>
                <table border="1" cellpadding="5">
                    <thead>
                        <tr>
                            <th>Кабинет</th>
                            <th>Описание</th>
                            <th>Устройств</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?= htmlspecialchars($room['name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($room['description'])) ?></td>
                                <td style="text-align: center;"><?= $room['device_count'] ?></td>
                                <td>
                                    <a href="room.php?id=<?= $room['id'] ?>">🔍 Просмотр</a>
                                    <a href="edit_room.php?id=<?= $room['id'] ?>">✏️ Редактировать</a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div> 

</body>
</html>
