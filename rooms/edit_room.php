<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
require_once 'room_model.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID кабинета.");
}

$room_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Кабинет не найден.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = "Название кабинета не может быть пустым.";
        } else {
            updateRoom($pdo, $room_id, $name, $description);
            header("Location: room.php?id=$room_id");
            exit;
        }
    }

    if (isset($_POST['duplicate'])) {
        $newId = duplicateRoom($pdo, $room_id);
        header("Location: edit_room.php?id=$newId");
        exit;
    }

    if (isset($_POST['delete'])) {
        deleteRoom($pdo, $room_id);
        header("Location: room.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 m-0">Редактировать кабинет</h1>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="card card-body shadow-sm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название кабинета</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($room['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($room['description']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-center gap-4">
                        <button type="submit" name="update" class="btn btn-outline-success">💾 Сохранить</button>
                        <button type="submit" name="delete" class="btn btn-outline-danger" onclick="return confirm('Вы действительно хотите удалить кабинет и все его устройства?');">🗑 Удалить</button>
                        <button type="submit" name="duplicate" class="btn btn-outline-secondary">📄 Дублировать</button>
                        <a href="room.php?id=<?= $room_id ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
