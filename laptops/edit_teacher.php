<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Некорректный ID преподавателя.");
}

$id = (int) $_GET['id'];

// Получаем текущие данные преподавателя
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die("Преподаватель не найден.");
}

// Обработка формы
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Удаление
        $pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
        header("Location: teachers.php");
        exit;
    }

    $full_name = trim($_POST['full_name'] ?? '');

    if ($full_name === '') {
        $error = "ФИО не может быть пустым.";
    } else {
        $stmt = $pdo->prepare("UPDATE teachers SET full_name = ? WHERE id = ?");
        $stmt->execute([$full_name, $id]);
        header("Location: teachers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать преподавателя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function confirmDelete() {
            return confirm("Вы действительно хотите удалить этого преподавателя?");
        }
    </script>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 m-0">Редактировать преподавателя</h1>
                </div>

                <?php if ($error): ?>
                    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="post" class="card card-body shadow-sm">
                    <div class="mb-3">
                        <label class="form-label">ФИО:</label>
                        <input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-4">
                        <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
                        <button type="submit" class="btn btn-outline-danger" name="delete" onclick="return confirmDelete()" style="margin-left: 10px; color: red;">🗑️ Удалить</button>
                        <a href="teachers.php" class="btn btn-outline-secondary">🚫 Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
