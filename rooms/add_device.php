<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

// Получаем ID кабинета
if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    die("Некорректный ID кабинета.");
}

$room_id = (int) $_GET['room_id'];

// Проверка существования кабинета
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

// Загрузка всех кабинетов для выпадающего списка
$stmt = $pdo->query("SELECT id, name FROM rooms ORDER BY name");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$room) {
    die("Кабинет не найден.");
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	var_dump($_POST['status']); // или где ты получаешь статус
	
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $icon = $_POST['icon'] ?? '';
    $ip = trim($_POST['ip'] ?? '');
    $mac = trim($_POST['mac'] ?? '');
    $inventory = trim($_POST['inventory_number'] ?? '');
    $status = $_POST['status'] ?? 'В работе';
    $comment = trim($_POST['comment'] ?? '');
    $connected_id = ($_POST['connected_to_device_id'] ?? '') !== '' ? (int) $_POST['connected_to_device_id'] : null;

    if ($name === '') {
        $error = "Название устройства обязательно.";
    } else {
        // Сохраняем устройство
        $stmt = $pdo->prepare("INSERT INTO devices 
            (room_id, name, type, ip, mac, inventory_number, status, comment, icon)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $room_id, $name, $type, $ip, $mac, $inventory, $status, $comment, $icon
        ]);

        // Получаем ID только что добавленного устройства
        $new_device_id = $pdo->lastInsertId();

        // Если задано подключение — сохраняем
        if ($connected_id !== null) {
            $link = $pdo->prepare("INSERT INTO switch_links (device_id, connected_to_device_id)
                                   VALUES (?, ?)");
            $link->execute([$new_device_id, $connected_id]);
        }

        header("Location: room.php?id=$room_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить устройство в <?= htmlspecialchars($room['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container py-4">
    <h1 class="mb-4 text-center">Добавить устройство в <?= htmlspecialchars($room['name']) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Название устройства</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Тип устройства</label>
            <select name="type" class="form-select" required>
                <option>ПК</option>
                <option>Сервер</option>
                <option>Принтер</option>
                <option>Маршрутизатор</option>
                <option>Свитч</option>
                <option>МФУ</option>
                <option>Интерактивная доска</option>
                <option>Прочее</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Иконка устройства</label>
            <div id="icon-container" class="border rounded p-2 bg-light">
                <p class="text-muted m-0">Сначала выберите тип устройства</p>
            </div>
            <input type="hidden" name="icon" id="icon-input">
        </div>

        <div class="col-md-6">
            <label class="form-label">IP-адрес</label>
            <input type="text" name="ip" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">MAC-адрес</label>
            <input type="text" name="mac" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Инвентарный номер</label>
            <input type="text" name="inventory_number" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <option selected>В работе</option>
                <option>На ремонте</option>
                <option>Списан</option>
                <option>На хранении</option>
                <option>Числится за кабинетом</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Комментарий</label>
            <textarea name="comment" rows="4" class="form-control"></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Подключено к (кабинет)</label>
            <select id="room-select" name="room_select" class="form-select">
                <option value="">-- Выберите кабинет --</option>
                <?php
                foreach ($rooms as $r) {
                    echo "<option value=\"{$r['id']}\">{$r['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Устройство в кабинете</label>
            <select name="connected_to_device_id" id="device-select" class="form-select">
                <option value="">-- Сначала выберите кабинет --</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-center gap-4 mt-4">
            <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
            <a href="room.php?id=<?= $room_id ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
        </div>
    </form>
 </div>
    <script>
    function loadIcons(type, selected = '') {
        fetch('../load_icons.php?type=' + encodeURIComponent(type))
            .then(response => response.text())
            .then(html => {
                const container = document.getElementById('icon-container');
                container.innerHTML = html;
                document.querySelectorAll('.icon-option').forEach(img => {
                    img.addEventListener('click', () => {
                        document.getElementById('icon-input').value = img.dataset.filename;
                        document.querySelectorAll('.icon-option').forEach(i => i.classList.remove('border-success'));
                        img.classList.add('border', 'border-success');
                    });
                });

                if (selected) {
                    const selectedImg = document.querySelector(`.icon-option[data-filename="${selected}"]`);
                    if (selectedImg) selectedImg.classList.add('border', 'border-success');
                }
            });
    }

    document.querySelector('select[name="type"]').addEventListener('change', function () {
        loadIcons(this.value);
    });

    document.addEventListener('DOMContentLoaded', () => {
        const defaultType = document.querySelector('select[name="type"]').value;
        loadIcons(defaultType);
    });

    document.getElementById('room-select').addEventListener('change', function () {
        const roomId = this.value;
        const deviceSelect = document.getElementById('device-select');
        deviceSelect.innerHTML = '<option>Загрузка...</option>';

        fetch('../load_devices_by_room.php?room_id=' + roomId)
            .then(res => res.text())
            .then(html => {
                deviceSelect.innerHTML = html || '<option>Нет устройств в кабинете</option>';
            });
    });
    </script>
</body>
</html>
