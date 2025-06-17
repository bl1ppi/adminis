<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$configFile = __DIR__ . '/../includes/config.php';
$installedFlag = __DIR__ . '/.installed';

if (file_exists($configFile)) {
    header('Location: ../index.php');
    exit;
}

// Проверка базовых требований
$requirements = [
    'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'Расширение PDO' => extension_loaded('pdo'),
    'Расширение mbstring' => extension_loaded('mbstring'),
    'Расширение openssl' => extension_loaded('openssl'),
    'Расширение json' => extension_loaded('json'),
    'Права на запись в config.php' => is_writable(__DIR__ . '/../includes/') || !file_exists(__DIR__ . '/../includes/config.php'),
];

// Проверка доступных PDO-драйверов
$availableDrivers = PDO::getAvailableDrivers();
$supportedDrivers = ['mysql', 'pgsql', 'sqlite'];
$driverCheck = array_intersect($supportedDrivers, $availableDrivers);
$requirements['Поддерживаемый драйвер PDO (MySQL, PostgreSQL, SQLite)'] = !empty($driverCheck);

$all_ok = !in_array(false, $requirements, true);

$error = '';
$success = false;

$availableDrivers = PDO::getAvailableDrivers();
$supported = [
    'mysql' => in_array('mysql', $availableDrivers),
    'pgsql' => in_array('pgsql', $availableDrivers),
    'sqlite' => in_array('sqlite', $availableDrivers),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbType = $_POST['db_type'];

    if (empty($supported[$dbType])) {
        $error = "PDO-драйвер для '$dbType' не установлен на сервере.";
    } else {
        $host = $_POST['host'] ?? '';
        $dbname = $_POST['dbname'] ?? '';
        $user = $_POST['user'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $adminUser = $_POST['admin_user'] ?? '';
        $adminPass = $_POST['admin_pass'] ?? '';
        $siteTitle = $_POST['site_title'] ?? '📡 Учёт и визуализация сети';
        $appVersion = '1.1.0';

        try {
            // DSN и проверка существования базы
            if ($dbType === 'sqlite') {
                $dbPath = __DIR__ . "/../data/$dbname.sqlite";
                if (!is_file($dbPath)) {
                    if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0777, true);
                    touch($dbPath);
                }
                $dsn = "sqlite:$dbPath";
                $pdo = new PDO($dsn);
            } else {
                if (empty($host) || empty($dbname)) throw new Exception("Укажите хост и имя базы данных");
                $dsn = $dbType === 'mysql'
                    ? "mysql:host=$host;dbname=$dbname;charset=utf8mb4"
                    : "pgsql:host=$host;dbname=$dbname";
                $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }

            $tables = [
                'rooms' => match ($dbType) {
                 	'mysql' => "CREATE TABLE IF NOT EXISTS rooms (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(50) NOT NULL,
                            description TEXT
                        )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS rooms (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(50) NOT NULL,
                        description TEXT
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS rooms (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        description TEXT
                    )",
                },
                'devices' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS devices (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        room_id INT NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        type ENUM('ПК','Сервер','Принтер','Маршрутизатор','Свитч','МФУ','Интерактивная доска','Прочее') NOT NULL,
                        ip VARCHAR(15),
                        mac VARCHAR(17),
                        inventory_number VARCHAR(50),
                        status ENUM('В работе','На ремонте','Списан','На хранении','Числится за кабинетом') NOT NULL,
                        comment TEXT,
                        icon VARCHAR(255),
                        FOREIGN KEY (room_id) REFERENCES rooms(id)
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS devices (
                        id SERIAL PRIMARY KEY,
                        room_id INT NOT NULL REFERENCES rooms(id),
                        name VARCHAR(100) NOT NULL,
                        type VARCHAR(50) NOT NULL CHECK (type IN ('ПК','Сервер','Принтер','Маршрутизатор','Свитч','МФУ','Интерактивная доска','Прочее')),
                        ip VARCHAR(15),
                        mac VARCHAR(17),
                        inventory_number VARCHAR(50),
                        status VARCHAR(50) NOT NULL CHECK (status IN ('В работе','На ремонте','Списан','На хранении','Числится за кабинетом')),
                        comment TEXT,
                        icon VARCHAR(255)
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS devices (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        room_id INTEGER NOT NULL,
                        name TEXT NOT NULL,
                        type TEXT NOT NULL,
                        ip TEXT,
                        mac TEXT,
                        inventory_number TEXT,
                        status TEXT NOT NULL,
                        comment TEXT,
                        icon TEXT,
                        FOREIGN KEY (room_id) REFERENCES rooms(id)
                    )",
                },
                'switch_links' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS switch_links (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        device_id INT NOT NULL,
                        connected_to_device_id INT NOT NULL,
                        FOREIGN KEY (device_id) REFERENCES devices(id),
                        FOREIGN KEY (connected_to_device_id) REFERENCES devices(id)
                    )",
                	'pgsql' => "CREATE TABLE IF NOT EXISTS switch_links (
                	    id SERIAL PRIMARY KEY,
                	    device_id INT NOT NULL REFERENCES devices(id),
                	    connected_to_device_id INT NOT NULL REFERENCES devices(id)
                	)",
                	'sqlite' => "CREATE TABLE IF NOT EXISTS switch_links (
                	    id INTEGER PRIMARY KEY AUTOINCREMENT,
                	    device_id INTEGER NOT NULL,
                	    connected_to_device_id INTEGER NOT NULL,
                	    FOREIGN KEY (device_id) REFERENCES devices(id),
                	    FOREIGN KEY (connected_to_device_id) REFERENCES devices(id)
                	)",
                },
                'teachers' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS teachers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        full_name VARCHAR(255) NOT NULL
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS teachers (
                        id SERIAL PRIMARY KEY,
                        full_name VARCHAR(255) NOT NULL
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS teachers (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        full_name TEXT NOT NULL
                    )",
                },
                'laptops' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS laptops (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        number INT NOT NULL,
                        teacher_id INT NOT NULL,
                        room_id INT,
                        start_date DATE,
                        end_date DATE,
                        status ENUM('взят','сдан') DEFAULT 'взят',
                        comment TEXT,
                        is_permanent TINYINT(1) DEFAULT 0,
                        FOREIGN KEY (teacher_id) REFERENCES teachers(id),
                        FOREIGN KEY (room_id) REFERENCES rooms(id)
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS laptops (
                        id SERIAL PRIMARY KEY,
                        number INT NOT NULL,
                        teacher_id INT NOT NULL REFERENCES teachers(id),
                        room_id INT REFERENCES rooms(id),
                        start_date DATE,
                        end_date DATE,
                        status VARCHAR(10) DEFAULT 'взят' CHECK (status IN ('взят','сдан')),
                        comment TEXT,
                        is_permanent BOOLEAN DEFAULT FALSE
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS laptops (
					    id INTEGER PRIMARY KEY AUTOINCREMENT,
					    number INTEGER NOT NULL,
					    teacher_id INTEGER NOT NULL,
					    room_id INTEGER,
					    start_date TEXT,
					    end_date TEXT,
					    status TEXT DEFAULT 'взят',
					    comment TEXT,
					    is_permanent INTEGER DEFAULT 0,
					    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
					    FOREIGN KEY (room_id) REFERENCES rooms(id)
					)",
                },
                'documentation' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS documentation (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        content TEXT NOT NULL,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS documentation (
                        id SERIAL PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        content TEXT NOT NULL,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS documentation (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        title TEXT NOT NULL,
                        content TEXT NOT NULL,
                        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                    )",
                },
                'servers' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS servers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        ip VARCHAR(45) NOT NULL,
                        user VARCHAR(50) NOT NULL DEFAULT 'monitor',
                        services TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS servers (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        ip VARCHAR(45) NOT NULL,
                        user VARCHAR(50) NOT NULL DEFAULT 'monitor',
                        services TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS servers (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        ip TEXT NOT NULL,
                        user TEXT NOT NULL DEFAULT 'monitor',
                        services TEXT,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP
                    )",
                },
                'server_stats' => match ($dbType) {
                    'mysql' => "CREATE TABLE IF NOT EXISTS server_stats (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        server_id INT NOT NULL,
                        cpu_used FLOAT NOT NULL,
                        mem_used INT NOT NULL,
                        mem_total INT NOT NULL,
                        disk TEXT,
                        services TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
                    )",
                    'pgsql' => "CREATE TABLE IF NOT EXISTS server_stats (
                        id SERIAL PRIMARY KEY,
                        server_id INT NOT NULL REFERENCES servers(id) ON DELETE CASCADE,
                        cpu_used REAL NOT NULL,
                        mem_used INT NOT NULL,
                        mem_total INT NOT NULL,
                        disk TEXT,
                        services TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    'sqlite' => "CREATE TABLE IF NOT EXISTS server_stats (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        server_id INTEGER NOT NULL,
                        cpu_used REAL NOT NULL,
                        mem_used INTEGER NOT NULL,
                        mem_total INTEGER NOT NULL,
                        disk TEXT,
                        services TEXT,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
                    )",
                },
            ];

            foreach ($tables as $sql) $pdo->exec($sql);

            // Сохраняем конфиг
			$configData = <<<PHP
<?php
define('DB_TYPE', '$dbType');
define('DB_DSN', '$dsn');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('ADMIN_LOGIN', '$adminUser');
define('ADMIN_PASSWORD', '$adminPass');
define('SITE_TITLE', '$siteTitle');
define('APP_VERSION', '$appVersion');
PHP;
            $configSaved = @file_put_contents($configFile, $configData);

            if ($configSaved !== false) {
                file_put_contents($installedFlag, 'installed');
                $success = true;
            } else {
                $manualConfig = htmlspecialchars($configData);
                $error = "⚠️ Не удалось создать файл includes/config.php; Создайте его вручную и вставьте следующий код:";
            }

        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Установка Adminis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function toggleDbFields() {
      const dbType = document.querySelector('[name="db_type"]').value;
      const isSQLite = dbType === 'sqlite';
      ['host', 'user', 'pass'].forEach(id => {
        document.getElementById(id).disabled = isSQLite;
        document.getElementById(id).closest('.mb-3').style.display = isSQLite ? 'none' : 'block';
      });
    }
    window.addEventListener('DOMContentLoaded', toggleDbFields);
  </script>
</head>
<body>
  <div class="container py-5" style="max-width: 720px;">
    <div class="text-center mb-4">
      <h1 class="h3">🚀 Установка платформы Adminis</h1>
      <p class="text-muted">Добро пожаловать в мастер установки</p>
    </div>

    <form method="post" action="">
      <div class="mb-4">
        <h4 class="text-center">✅ Проверка окружения</h4>
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Компонент</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requirements as $check => $result): ?>
              <tr>
                <td><?= htmlspecialchars($check) ?></td>
                <td><?= $result ? '✅' : '❌' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mb-4">
        <h4 class="text-center">⚙️ Настройки базы данных</h4>
        <div class="mb-3">
          <label class="form-label">Тип БД</label>
          <select name="db_type" class="form-select" onchange="toggleDbFields()">
            <?php foreach ($supported as $type => $available): ?>
              <option value="<?= $type ?>" <?= $available ? '' : 'disabled' ?>>
                <?= strtoupper($type) ?> <?= $available ? '' : '(недоступно)' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Хост</label>
          <input type="text" class="form-control" name="host" id="host" placeholder="localhost">
        </div>

        <div class="mb-3">
          <label class="form-label">Имя базы данных / файл</label>
          <input type="text" class="form-control" name="dbname" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Пользователь</label>
          <input type="text" class="form-control" name="user" id="user">
        </div>

        <div class="mb-3">
          <label class="form-label">Пароль</label>
          <input type="password" class="form-control" name="pass" id="pass">
        </div>

        <div class="mb-3">
          <label class="form-label">Заголовок сайта</label>
          <input type="text" class="form-control" name="site_title" value="📡 Учёт и визуализация сети" required>
        </div>
      </div>

      <div class="mb-4">
        <h4 class="text-center">🔐 Данные администратора</h4>
        <div class="mb-3">
          <label class="form-label">Логин</label>
          <input type="text" class="form-control" name="admin_user" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Пароль</label>
          <input type="password" class="form-control" name="admin_pass" required>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php if (isset($manualConfig)): ?>
          <pre class="bg-light p-3 border rounded"><?= $manualConfig ?></pre>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success text-center">
          ✅ Установка завершена!
          <div class="mt-3">
            <a href="../index.php" class="btn btn-outline-success">Перейти к сайту</a>
          </div>
        </div>
      <?php else: ?>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">Установить Adminis</button>
        </div>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>
