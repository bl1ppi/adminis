<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$teachers = $pdo->query("SELECT * FROM teachers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Преподаватели</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        td.icon-cell img {
            width: 24px;
            height: 24px;
            vertical-align: middle;
            margin-right: 6px;
        }
        tr.clickable-row {
            cursor: pointer;
        }
        .table-container {
            max-width: 80%;
        }
        td.comment-cell {
            max-width: 300px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="container table-container">
        <div class="mb-4">
            <h1 class="mb-3">Список преподавателей</h1>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">← Назад к ноутбукам</a>
            <a href="add_teacher.php" class="btn btn-outline-success btn-sm">➕ Добавить преподавателя</a>
        </div>

        <table class="table table-bordered table-sm table-hover align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $t): ?>
                    <tr class="clickable-row" onclick="window.location.href='edit_teacher.php?id=<?= $t['id'] ?>'">
                        <td><?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['full_name']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
