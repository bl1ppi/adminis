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
  <h2 class="setup-title">🔐 Генерация SSH-ключа мониторинга</h2>
  <pre class="setup-instruction">
Выполняется один раз на сервере Adminis (веб-интерфейса).

1. Создайте директорию для ключей мониторинга

  sudo mkdir -p /etc/monitoring
  sudo chmod 700 /etc/monitoring

2. Сгенерируйте пару ключей SSH

  sudo ssh-keygen -t rsa -b 4096 -f /etc/monitoring/monitor_id_rsa -N ""

3. Установите права доступа

  sudo chmod 600 /etc/monitoring/monitor_id_rsa
  sudo chmod 644 /etc/monitoring/monitor_id_rsa.pub

  sudo chown -R www-data:www-data /etc/monitoring 
  или если у вас httpd 
  sudo chown -R apache:apache /etc/monitoring

    🔑 Это создаст:

        приватный ключ: /etc/monitoring/monitor_id_rsa

        публичный ключ: /etc/monitoring/monitor_id_rsa.pub
  </pre>
  <h2 class="setup-title">📌 Инструкция по настройке пользователя и SSH</h2>
  <pre class="setup-instruction">
ssh root@REMOTE_IP

1. Создайте пользователя monitor с домашней директорией:

  adduser monitor
    
    🔒 Установите простой, но безопасный пароль, или сразу запретите вход по паролю, оставив только вход по ключу (см. шаг 2).

2. Настройте SSH-доступ по публичному ключу:

  mkdir -p /home/monitor/.ssh
  chmod 700 /home/monitor/.ssh

  # Вставьте содержимое публичного ключа мониторинга:
  echo "ВАШ_ПУБЛИК_КЛЮЧ" > /home/monitor/.ssh/authorized_keys

  chmod 600 /home/monitor/.ssh/authorized_keys
  chown -R monitor:monitor /home/monitor/.ssh

    📁 Теперь ключ будет храниться в /home/monitor/.ssh/authorized_keys, и пользователь сможет подключаться по SSH.

3. Разрешите запуск нужных команд без пароля через sudo:

Создайте отдельный файл:

  echo "monitor ALL=(ALL) NOPASSWD: /usr/bin/mpstat, /usr/bin/free, /bin/df, /usr/bin/systemctl show --property=SubState, /usr/bin/systemctl is-active *" > /etc/sudoers.d/monitor
  chmod 440 /etc/sudoers.d/monitor

    💡 Это даст доступ только к нужным командам для мониторинга CPU, памяти, дисков и служб.

✅ После этого:

Убедитесь, что с сервера мониторинга можно подключиться по SSH:

  ssh -i /etc/monitoring/monitor_id_rsa monitor@REMOTE_IP

Убедитесь, что команды вроде sudo systemctl is-active apache2 работают без запроса пароля.

🛠️ Инструкция по настройке cron на сервере мониторинга

sudo crontab -u www-data -e

Добавь в конец файла следующую строку, чтобы запускать сбор данных каждую минуту:

* * * * * php /var/www/html/adminis/modules/monitoring/collect_stats.php

  ✅ Убедись, что путь к collect_stats.php корректен — от корня файловой системы.
  ✅ Также проверь, что PHP доступен по команде php. Если используется php8.1, замени php на php8.1.

📁 Дополнительно: можно создать отдельного лог-файл /var/log/monitoring.log:

sudo touch /var/log/monitoring.log
sudo chown www-data:www-data /var/log/monitoring.log

Тогда команда в cron будет выглядеть так:

* * * * * php /var/www/html/adminis/modules/monitoring/collect_stats.php >> /var/log/monitoring.log 2>&1
  </pre>
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
</body>
</html>
