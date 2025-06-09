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
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 6px 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f2f2f2; }
        a.button {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background: #e0e0e0;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Список преподавателей</h1>

    <p>
        <a href="add_teacher.php" class="button">➕ Добавить преподавателя</a>
        <a href="index.php" class="button">↩️ Назад к ноутбукам</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td>
                        <a href="edit_teacher.php?id=<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['full_name']) ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</body>
</html>
