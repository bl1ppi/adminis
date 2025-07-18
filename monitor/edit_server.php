<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID.");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ?");
$stmt->execute([$id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    die("Сервер не найден.");
}

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
            "UPDATE servers SET name = ?, ip = ?, user = ?, services = ? WHERE id = ?"
        );
        $stmt->execute([$name, $ip, $user, $services, $id]);
        $success = true;

        // Обновить текущие данные
        $server['name'] = $name;
        $server['ip'] = $ip;
        $server['user'] = $user;
        $server['services'] = $services;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>✏ Редактировать сервер</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    pre {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 0.5rem;
      border: 1px solid #dee2e6;
    }
  </style>
</head>
<body>

<div class="container py-4">
  <div class="text-center mb-4">
    <h1 class="h3 mb-3">✏ Редактировать сервер</h1>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">Изменения сохранены. <a href="index.php">Вернуться к списку</a></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3 mt-4">
    <div class="col-md-6">
      <label class="form-label">Название сервера</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($server['name']) ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">IP-адрес</label>
      <input type="text" name="ip" class="form-control" value="<?= htmlspecialchars($server['ip']) ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Пользователь</label>
      <input type="text" name="user" class="form-control" value="<?= htmlspecialchars($server['user']) ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Службы (через запятую)</label>
      <input type="text" name="services" class="form-control" value="<?= htmlspecialchars($server['services']) ?>">
    </div>

    <div class="col-12 d-flex justify-content-center gap-3 mt-3">
      <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
      <a href="index.php" class="btn btn-outline-secondary">🚫 Отмена</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
