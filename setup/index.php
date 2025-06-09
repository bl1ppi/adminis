<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$configFile = __DIR__ . '/../includes/config.php';
$installedFlag = __DIR__ . '/.installed';

if (file_exists($configFile)) {
    header('Location: /../index.php');
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
PHP;
            $configSaved = @file_put_contents($configFile, $configData);

            if ($configSaved !== false) {
                file_put_contents($installedFlag, 'installed');
                $success = true;
            } else {
                $manualConfig = htmlspecialchars($configData);
                $error = "⚠️ Не удалось создать файл <code>config.php</code>. Создайте его вручную и вставьте следующий код:";
            }

        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Установка Adminis</title>
    <style>
		body {
		  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		  background-color: #f7f9fc;
		  margin: 0;
		  padding: 0;
		  height: 100vh;
		  color: #333;
		  display: flex;            
		  justify-content: center;  
		  align-items: flex-start;  
		  padding-top: 40px;
		  box-sizing: border-box;
		}
		
		.container {
		  background-color: #ffffff;
		  padding: 40px;
		  border-radius: 12px;
		  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
		  width: 100%;
		  max-width: 700px;
		  box-sizing: border-box;
		  display: flex;
		  flex-direction: column;  
		  gap: 16px;
		}
		
		h1,h2,h3 {
		  margin: 0;
		  font-size: 24px;
		  text-align: center;
		  color: #222;
		}
		
		p {
		  text-align: center;
		  margin: 0;
		  color: #555;
		}
		
		form {
		  display: flex;
		  flex-direction: column;
		  gap: 10px;
		}
			
		label {
		  font-weight: 600;
		  margin-bottom: 6px;
		  display: block;
		}
		
		input[type="text"],
		input[type="password"],
		input[type="email"],
		select {
		  padding: 10px;
		  border: 1px solid #ccc;
		  border-radius: 6px;
		  width: 100%;
		  box-sizing: border-box;
		}
		
		button {
		  padding: 10px 15px;
		  background-color: #0077cc;
		  color: white;
		  border: none;
		  border-radius: 6px;
		  cursor: pointer;
		  font-size: 16px;
		  transition: background-color 0.3s ease;
		}
		
		button:hover {
		  background-color: #005fa3;
		}
		
		.note {
		  font-size: 14px;
		  color: #666;
		  text-align: center;
		  margin-top: 10px;
		}
    </style>
    <script>
        function toggleDbFields() {
            const dbType = document.querySelector('[name="db_type"]').value;
            const isSQLite = dbType === 'sqlite';
            ['host', 'user', 'pass'].forEach(id => {
                document.getElementById(id).disabled = isSQLite;
                document.getElementById(id).closest('label').style.display = isSQLite ? 'none' : 'block';
            });
        }
        window.addEventListener('DOMContentLoaded', toggleDbFields);
    </script>
</head>
<body>
	<div class="container">
		<h1>Добро пожаловать в мастер установки платформы Adminis.</h1>
	    <form method="post" action="">
	        <h2>Проверка окружения:</h2>
	        <table>
	            <?php foreach ($requirements as $check => $result): ?>
	                <tr>
	                    <td><?= htmlspecialchars($check) ?></td>
	                    <td class="<?= $result ? 'ok' : 'fail' ?>">
	                        <?= $result ? '✅' : '❌' ?>
	                    </td>
	                </tr>
	            <?php endforeach; ?>
	        </table>
	        <h3>Подключение к базе данных</h3>
	        <label>Тип БД:
	            <select name="db_type" onchange="toggleDbFields()">
	                <?php foreach ($supported as $type => $available): ?>
	                    <option value="<?= $type ?>" <?= $available ? '' : 'disabled' ?>>
	                        <?= strtoupper($type) ?> <?= $available ? '' : '(недоступно)' ?>
	                    </option>
	                <?php endforeach; ?>
	            </select>
	        </label>
	        <label>Хост:
	            <input type="text" name="host" id="host" placeholder="localhost">
	        </label>
	        <label>Имя базы данных / файл:
	            <input type="text" name="dbname" required>
	        </label>
	        <label>Пользователь:
	            <input type="text" name="user" id="user">
	        </label>
	        <label>Пароль:
	            <input type="password" name="pass" id="pass">
	        </label>

			<label>Заголовок сайта:
			    <input type="text" name="site_title" value="📡 Учёт и визуализация сети" required>
			</label>

	        <h3>Настройка администратора</h3>
	        <label>Логин:
	            <input type="text" name="admin_user" required>
	        </label>
	        <label>Пароль:
	            <input type="password" name="admin_pass" required>
	        </label>

	        <button type="submit">Установить Adminis</button>
	        <?php if ($success): ?>
	            <h3>✅ Установка завершена!</h3>
	            <p><a href="../index.php">Перейти к сайту</a></p>
	        <?php elseif ($error): ?>
	            <div style="background: #ffecec; border: 1px solid #ff5c5c; padding: 10px; border-radius: 6px; margin-top: 15px;">
	                <?= $error ?>
	            </div>
	            <?php if (isset($manualConfig)): ?>
	                <pre style="background:#f4f4f4; padding:10px; border-radius:6px; overflow:auto;"><?= $manualConfig ?></pre>
	            <?php endif; ?>
	        <?php endif; ?>
	    </form>
    </div>
</body>
</html>
