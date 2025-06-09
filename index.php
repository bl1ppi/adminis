<?php
if (!file_exists(__DIR__ . '/includes/config.php')) {
    header('Location: setup/index.php');
    exit;
}
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/config.php';

// Получаем статистику
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalDevices = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
$totalLaptops = $pdo->query("SELECT COUNT(*) FROM laptops")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Учёт сети филиала КузГТУ</title>
  <link rel="stylesheet" href="includes/style.css">
</head>
<body>

<div class="container">
  <h1 class="main-heading"><?= defined('SITE_TITLE') ? SITE_TITLE : '📡 Заголовок по умолчанию' ?></h1>

  <div class="card-grid">
    <a href="map/" class="card">
      <div class="card-icon">🗺️</div>
      <div class="card-title">Карта сети</div>
      <div class="card-desc">Интерактивная визуализация всех подключённых устройств и связей.</div>
    </a>

    <a href="rooms/" class="card">
      <div class="card-icon">🏫</div>
      <div class="card-title">Кабинеты</div>
      <div class="card-desc">Управление кабинетами, добавление и редактирование оборудования.</div>
    </a>

    <a href="laptops/" class="card">
      <div class="card-icon">💻</div>
      <div class="card-title">Учёт ноутбуков</div>
      <div class="card-desc">Выдача ноутбуков преподавателям, статусы, история, фильтры.</div>
    </a>

    <a href="docs/" class="card">
      <div class="card-icon">📘</div>
      <div class="card-title">Документация</div>
      <div class="card-desc">Инструкции по установке ПО, конфигурации серверов, ссылки.</div>
    </a>
  </div>

  <div class="stats">
    <h2>📊 Статистика</h2>
    <div class="stats-grid">
      <div class="stat-block">
        <h3><?= $totalRooms ?></h3>
        <p>Кабинетов</p>
      </div>
      <div class="stat-block">
        <h3><?= $totalDevices ?></h3>
        <p>Устройств</p>
      </div>
      <div class="stat-block">
        <h3><?= $totalLaptops ?></h3>
        <p>Записей ноутбуков</p>
      </div>
      <div class="stat-block">
        <h3><?= $totalTeachers ?></h3>
        <p>Преподавателей</p>
      </div>
    </div>
  </div>

  <div class="logout">
    <a href="logout.php">🚪 Выйти из аккаунта</a>
  </div>
</div>

</body>
</html>
