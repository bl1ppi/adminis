<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID устройства.");
}

$device_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT d.*, r.name AS room_name, r.id AS room_id
                       FROM devices d
                       JOIN rooms r ON d.room_id = r.id
                       WHERE d.id = ?");
$stmt->execute([$device_id]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    die("Устройство не найдено.");
}

// Получаем текущее подключение
$link = $pdo->prepare("SELECT s.connected_to_device_id, r.id AS connected_room_id
                       FROM switch_links s
                       JOIN devices d2 ON s.connected_to_device_id = d2.id
                       JOIN rooms r ON d2.room_id = r.id
                       WHERE s.device_id = ?");
$link->execute([$device_id]);
$link_data = $link->fetch(PDO::FETCH_ASSOC);
$connected_id = $link_data['connected_to_device_id'] ?? null;
$connected_room_id = $link_data['connected_room_id'] ?? null;

// Обновление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $ip = trim($_POST['ip'] ?? '');
    $mac = trim($_POST['mac'] ?? '');
    $inventory = trim($_POST['inventory_number'] ?? '');
    $status = $_POST['status'] ?? 'В работе';
    $comment = trim($_POST['comment'] ?? '');
    $icon = $_POST['icon'] ?? '';
    $new_connected_id = ($_POST['connected_to_device_id'] ?? '') !== '' ? (int) $_POST['connected_to_device_id'] : null;

    if ($name === '') {
        $error = "Название обязательно.";
    } else {
        $stmt = $pdo->prepare("UPDATE devices SET name=?, type=?, ip=?, mac=?, inventory_number=?, status=?, comment=?, icon=? WHERE id=?");
        $stmt->execute([$name, $type, $ip, $mac, $inventory, $status, $comment, $icon, $device_id]);

        $pdo->prepare("DELETE FROM switch_links WHERE device_id = ?")->execute([$device_id]);

        if ($new_connected_id !== null) {
            $pdo->prepare("INSERT INTO switch_links (device_id, connected_to_device_id) VALUES (?, ?)")
                ->execute([$device_id, $new_connected_id]);
        }

        header("Location: room.php?id=" . $device['room_id']);
        exit;
    }
}

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $pdo->prepare("DELETE FROM devices WHERE id = ?")->execute([$device_id]);
    header("Location: room.php?id=" . $device['room_id']);
    exit;
}

// Дублирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duplicate'])) {
    $stmt = $pdo->prepare("INSERT INTO devices 
        (room_id, name, type, ip, mac, inventory_number, status, comment, icon)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $device['room_id'],
        $device['name'] . ' (копия)',
        $device['type'],
        $device['ip'],
        $device['mac'],
        $device['inventory_number'],
        $device['status'],
        $device['comment'],
        $device['icon']
    ]);

    $new_device_id = $pdo->lastInsertId();

    if ($connected_id) {
        $pdo->prepare("INSERT INTO switch_links (device_id, connected_to_device_id) VALUES (?, ?)")
            ->execute([$new_device_id, $connected_id]);
    }

    header("Location: edit_device.php?id=$new_device_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование устройства</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container py-4">
    <h1 class="mb-4 text-center">Редактирование <?= htmlspecialchars($device['name']) ?>, кабинет <?= htmlspecialchars($device['room_name']) ?></h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Название устройства</label>
            <input type="text" name="name" value="<?= htmlspecialchars($device['name']) ?>" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Тип устройства</label>
            <select name="type" id="type-select" class="form-control" required>
                <?php
                $types = ['ПК', 'Сервер', 'Принтер', 'Маршрутизатор', 'Свитч', 'МФУ', 'Интерактивная доска', 'Прочее'];
                foreach ($types as $type) {
                    $selected = ($type === $device['type']) ? 'selected' : '';
                    echo "<option $selected>$type</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Иконка устройства</label>
            <div id="icon-container" class="border rounded p-2 bg-light">
                <p class="text-muted m-0">Сначала выберите тип устройства</p>
            </div>
            <input type="hidden" name="icon" id="icon-input" value="<?= htmlspecialchars($device['icon']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">IP-адрес</label>
            <input type="text" name="ip" class="form-control" value="<?= htmlspecialchars($device['ip']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">MAC-адрес</label>
            <input type="text" name="mac" class="form-control" value="<?= htmlspecialchars($device['mac']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Инвентарный номер</label>
            <input type="text" name="inventory_number" class="form-control" value="<?= htmlspecialchars($device['inventory_number']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <?php
                $statuses = ['В работе', 'На ремонте', 'Списан', 'На хранении', 'Числится за кабинетом'];
                foreach ($statuses as $status) {
                    $selected = ($status === $device['status']) ? 'selected' : '';
                    echo "<option $selected>$status</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Комментарий</label>
            <textarea name="comment" rows="4" class="form-control"><?= htmlspecialchars($device['comment']) ?></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Подключено к (кабинет)</label>
            <select id="room-select" name="room_select" class="form-select">
                <option value="">-- Выберите кабинет --</option>
                <?php
                $rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
                foreach ($rooms as $r) {
                    $sel = ($r['id'] == $connected_room_id) ? 'selected' : '';
                    echo "<option value=\"{$r['id']}\" $sel>{$r['name']}</option>";
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
            <button type="submit" name="update" class="btn btn-outline-success">💾 Сохранить</button>
            <button type="submit" name="delete" class="btn btn-outline-danger" onclick="return confirm('Удалить это устройство?');">🗑️ Удалить</button>
            <button type="submit" name="duplicate" class="btn btn-outline-secondary">📋 Дублировать</button>
            <a href="room.php?id=<?= $device['room_id'] ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
        </div>
    </form>
</div>

<script>
function loadIcons(type, selected = '') {
    fetch('../load_icons.php?type=' + encodeURIComponent(type))
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('icon-container');
            container.innerHTML = html;
            document.querySelectorAll('.icon-option').forEach(img => {
                img.addEventListener('click', () => {
                    document.getElementById('icon-input').value = img.dataset.filename;
                    document.querySelectorAll('.icon-option').forEach(i => i.style.border = '');
                    img.style.border = '2px solid green';
                });

                if (img.dataset.filename === selected) {
                    img.style.border = '2px solid green';
                }
            });
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('type-select').value;
    loadIcons(type, "<?= $device['icon'] ?>");

    const roomId = "<?= $connected_room_id ?>";
    const selectedDevice = "<?= $connected_id ?>";
    if (roomId !== "") {
        document.getElementById('room-select').value = roomId;
        fetch('../load_devices_by_room.php?room_id=' + roomId)
            .then(res => res.text())
            .then(html => {
                const deviceSelect = document.getElementById('device-select');
                deviceSelect.innerHTML = html;
                deviceSelect.value = selectedDevice;
            });
    }
});

document.getElementById('type-select').addEventListener('change', function () {
    loadIcons(this.value);
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
