<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$docs = $pdo->query("SELECT id, title FROM documentation ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$current_id = isset($_GET['id']) ? (int)$_GET['id'] : ($docs[0]['id'] ?? 0);

$stmt = $pdo->prepare("SELECT title, content FROM documentation WHERE id = ?");
$stmt->execute([$current_id]);
$current_doc = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>📘 Документация</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
        }

        .layout {
            display: flex;
            height: calc(100vh - 50px); /* вычитаем высоту navbar */
        }

        .sidebar {
            width: 250px;
            background: #f0f0f0;
            border-right: 1px solid #ccc;
            padding: 15px;
            box-sizing: border-box;
        }

        .sidebar h3 {
            margin-top: 0;
        }

        .sidebar a {
            display: block;
            margin: 5px 0;
            color: #0033cc;
            text-decoration: none;
        }

        .sidebar a.active {
            font-weight: bold;
            color: black;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .content h1 {
            margin-top: 0;
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <h3>📘 Разделы</h3>
        <?php foreach ($docs as $doc): ?>
            <a href="index.php?id=<?= $doc['id'] ?>" class="<?= $doc['id'] == $current_id ? 'active' : '' ?>">
                <?= htmlspecialchars($doc['title']) ?>
            </a>
        <?php endforeach; ?>
        <hr>
        <a href="edit_docs.php?id=<?= $current_id ?>">✏️ Редактировать</a>
        <a href="add_docs.php">➕ Добавить раздел</a>
    </div>

    <div class="content">
        <h1><?= htmlspecialchars($current_doc['title']) ?></h1>
        <?= $current_doc['content'] ?>
    </div>
</div>

</body>
</html>
