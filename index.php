<?php
if (!file_exists(__DIR__ . '/includes/config.php')) {
    header('Location: setup/index.php');
    exit;
}
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/config.php';

$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalDevices = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
$totalServers = $pdo->query("SELECT COUNT(*) FROM servers")->fetchColumn();
$totalLaptops = $pdo->query("SELECT COUNT(*) FROM laptops")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Учёт сети филиала КузГТУ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container py-4">
  <h1 class="text-center mb-5"><?= defined('SITE_TITLE') ? SITE_TITLE : '📡 Заголовок по умолчанию' ?></h1>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
    <div class="col">
      <a href="map/" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="display-4">🗺️</div>
            <h5 class="card-title">Карта сети</h5>
            <p class="card-text">Интерактивная визуализация всех подключённых устройств и связей.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col">
      <a href="rooms/" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="display-4">🏫</div>
            <h5 class="card-title">Кабинеты</h5>
            <p class="card-text">Управление кабинетами, добавление и редактирование оборудования.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col">
      <a href="monitor/" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="display-4">🖥</div>
            <h5 class="card-title">Мониторинг</h5>
            <p class="card-text">Дашбоард серверов, добавление и редактирование серверов.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col">
      <a href="laptops/" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="display-4">💻</div>
            <h5 class="card-title">Учёт ноутбуков</h5>
            <p class="card-text">Выдача ноутбуков преподавателям, статусы, история, фильтры.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col">
      <a href="docs/" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="display-4">📘</div>
            <h5 class="card-title">Документация</h5>
            <p class="card-text">Инструкции по установке ПО, конфигурации серверов, ссылки.</p>
          </div>
        </div>
      </a>
    </div>
  </div>

  <h2 class="text-center mb-4">📊 Статистика</h2>
  <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 text-center mb-5">
    <div class="col">
      <div class="border rounded py-3 shadow-sm">
        <h4><?= $totalRooms ?></h4>
        <p class="mb-0">Кабинетов</p>
      </div>
    </div>
    <div class="col">
      <div class="border rounded py-3 shadow-sm">
        <h4><?= $totalDevices ?></h4>
        <p class="mb-0">Устройств</p>
      </div>
    </div>
    <div class="col">
      <div class="border rounded py-3 shadow-sm">
        <h4><?= $totalServers ?></h4>
        <p class="mb-0">Серверов</p>
      </div>
    </div>
    <div class="col">
      <div class="border rounded py-3 shadow-sm">
        <h4><?= $totalLaptops ?></h4>
        <p class="mb-0">Записей ноутбуков</p>
      </div>
    </div>
    <div class="col">
      <div class="border rounded py-3 shadow-sm">
        <h4><?= $totalTeachers ?></h4>
        <p class="mb-0">Преподавателей</p>
      </div>
    </div>
  </div>

  <div class="text-center">
    <a href="logout.php" class="btn btn-outline-danger">🚪 Выйти из аккаунта</a>
  </div>
</div>

</body>
</html>
