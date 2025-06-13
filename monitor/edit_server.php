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
  <title>✏️ Редактировать сервер</title>
  <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
  <h1>✏️ Редактировать сервер</h1>

  <?php if ($success): ?>
    <p style="color: green;">Изменения сохранены.</p>
    <p><a href="index.php">← Вернуться</a></p>
  <?php elseif ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Название:<br>
      <input type="text" name="name" value="<?= htmlspecialchars($server['name']) ?>" required>
    </label><br><br>

    <label>IP-адрес:<br>
      <input type="text" name="ip" value="<?= htmlspecialchars($server['ip']) ?>" required>
    </label><br><br>

    <label>Пользователь:<br>
      <input type="text" name="user" value="<?= htmlspecialchars($server['user']) ?>" required>
    </label><br><br>

    <label>Службы (через запятую):<br>
      <input type="text" name="services" value="<?= htmlspecialchars($server['services']) ?>">
    </label><br><br>

    <button type="submit">💾 Сохранить</button>
    <a href="index.php">Отмена</a>
  </form>
</body>
</html>
