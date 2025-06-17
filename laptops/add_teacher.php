<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    if ($full_name === '') {
        $error = "Введите ФИО.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO teachers (full_name) VALUES (?)");
        $stmt->execute([$full_name]);
        header("Location: teachers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить преподавателя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 m-0">Добавить преподавателя</h1>
                </div>

                <?php if ($error): ?>
                    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="post" class="card card-body shadow-sm">
                    <div class="mb-3">
                        <label class="form-label">ФИО преподавателя:</label>
                        <input class="form-control" type="text" name="full_name" required>
                    </div>

                    <div class="d-flex justify-content-center gap-4">
                        <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
                        <a href="teachers.php" class="btn btn-outline-secondary">🚫 Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
