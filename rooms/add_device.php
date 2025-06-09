<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h1>Добавить устройство в <?= htmlspecialchars($room['name']) ?></h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Название устройства:<br>
            <input type="text" name="name" required>
        </label><br><br>

        <label>Тип устройства:<br>
            <select name="type" required>
                <option>ПК</option>
                <option>Сервер</option>
                <option>Принтер</option>
                <option>Маршрутизатор</option>
                <option>Свитч</option>
                <option>МФУ</option>
                <option>Интерактивная доска</option>
                <option>Прочее</option>
            </select>
        </label><br><br>

        <label>Иконка устройства:<br>
            <div id="icon-container">
                <p>Сначала выберите тип устройства</p>
            </div>
            <input type="hidden" name="icon" id="icon-input">
        </label><br><br>

        <label>IP-адрес:<br>
            <input type="text" name="ip">
        </label><br><br>

        <label>MAC-адрес:<br>
            <input type="text" name="mac">
        </label><br><br>

        <label>Инвентарный номер:<br>
            <input type="text" name="inventory_number">
        </label><br><br>

        <label>Статус:<br>
            <select name="status">
                <option selected>В работе</option>
                <option>На ремонте</option>
                <option>Списан</option>
                <option>На хранении</option>
                <option>Числится за кабинетом</option>
            </select>
        </label><br><br>

        <label>Комментарий:<br>
            <textarea name="comment" rows="4" cols="50"></textarea>
        </label><br><br>

        <label>Подключено к:<br>
            <select id="room-select" name="room_select">
                <option value="">-- Выберите кабинет --</option>
                <?php
                $rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
                foreach ($rooms as $r) {
                    echo "<option value=\"{$r['id']}\">{$r['name']}</option>";
                }
                ?>
            </select>
        </label><br><br>
        
        <label>Устройство в кабинете:<br>
            <select name="connected_to_device_id" id="device-select">
                <option value="">-- Сначала выберите кабинет --</option>
            </select>
        </label><br><br>
        
        <button type="submit">Добавить</button>
        <a href="room.php?id=<?= $room_id ?>">Отмена</a>
    </form>

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
                    document.querySelectorAll('.icon-option').forEach(i => i.style.border = '');
                    img.style.border = '2px solid green';
                });
            });

            // Если уже выбран — выделяем
            if (selected) {
                const selectedImg = document.querySelector(`.icon-option[data-filename="${selected}"]`);
                if (selectedImg) {
                    selectedImg.style.border = '2px solid green';
                }
            }
        });
}

// Загрузка иконок при изменении типа
document.querySelector('select[name="type"]').addEventListener('change', function () {
    const type = this.value;
    loadIcons(type);
});

// Загрузка иконок сразу при загрузке страницы (по умолчанию ПК)
document.addEventListener('DOMContentLoaded', () => {
    const defaultType = document.querySelector('select[name="type"]').value;
    loadIcons(defaultType);
});

// Загрузка списка устройств по кабинету
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
