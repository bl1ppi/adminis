<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $ip       = trim($_POST['ip']);
    $user     = trim($_POST['user']) ?: 'monitor';
    $services = trim($_POST['services']);

    if (!$name || !$ip) {
        $error = "Название и IP обязательны.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO servers (name, ip, user, services)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $ip, $user, $services]);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>➕ Добавить сервер</title>
<link rel="stylesheet" href="../includes/style.css">
</head>
<body>
  <h1>➕ Добавить сервер</h1>
  <?php if ($success): ?>
    <p style="color: green;">Сервер добавлен.</p>
    <p><a href="index.php">← Вернуться</a></p>
  <?php elseif ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Название:<br><input type="text" name="name" required></label><br><br>
    <label>IP-адрес:<br><input type="text" name="ip" required></label><br><br>
    <label>Пользователь (monitor):<br><input type="text" name="user" value="monitor" required></label><br><br>
    <label>Службы (через запятую):<br><input type="text" name="services"></label><br><br>
    <button type="submit">💾 Сохранить</button>
    <a href="index.php">Отмена</a>
  </form>

  <hr>
  <h2>📌 Инструкция по настройке пользователя и SSH</h2>
  <pre>
ssh root@REMOTE_IP

# 1. Создать пользователя
adduser --system --no-create-home --shell /usr/sbin/nologin monitor

# 2. Разрешить SSH
mkdir -p /home/monitor/.ssh
chmod 700 /home/monitor/.ssh

# 3. Добавить публичный ключ мониторинга:
# (вставьте сюда содержимое файла /etc/monitoring/monitor_id_rsa.pub)
echo "ВАШ_ПУБЛИК_КЛЮЧ" >> /home/monitor/.ssh/authorized_keys
chmod 600 /home/monitor/.ssh/authorized_keys
chown -R monitor:monitor /home/monitor/.ssh

# 4. Настроить sudo для статистики:
echo "monitor ALL=(ALL) NOPASSWD: /usr/bin/mpstat, /usr/bin/free, /bin/df, /usr/bin/systemctl" > /etc/sudoers.d/monitor
chmod 440 /etc/sudoers.d/monitor

# 5. Перезапустить SSH (если нужно):
systemctl restart ssh
  </pre>
</body>
</html>
