<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$current_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем список всех документов
$docs = $pdo->query("SELECT id, title FROM documentation ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Получаем текущий документ
$current_doc = null;
if ($current_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM documentation WHERE id = ?");
    $stmt->execute([$current_id]);
    $current_doc = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Если не выбран конкретный документ, берём первый из списка
if (!$current_doc && count($docs) > 0) {
    $current_id = $docs[0]['id'];
    $current_doc = $pdo->query("SELECT * FROM documentation WHERE id = $current_id")->fetch(PDO::FETCH_ASSOC);
}

// Функция для обработки контента
function processQuillContent($content) {
    return html_entity_decode($content);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>📘 Документация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .layout-wrapper {
            display: flex;
        }
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        .content {
            flex-grow: 1;
            padding: 30px;
        }
        #doc-list a {
            display: block;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: #212529;
            transition: background-color 0.2s, color 0.2s;
            margin-bottom: 5px;
        }
        #doc-list a:hover {
            background-color: #e9ecef;
            color: #0d6efd;
            text-decoration: none;
        }
        #doc-list a.active {
            background-color: #d0ebff;
            color: #0b5ed7;
            font-weight: 500;
        }
        .ql-container.ql-snow {
            border: none !important;
            font-family: inherit;
        }
        .ql-editor {
            padding: 0 !important;
            white-space: normal !important;
        }
        .ql-snow .ql-editor img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <div class="sidebar min-vh-100 bg-light p-3">
        <h5 class="mb-3">📘 Разделы</h5>
        <div class="mb-3" id="doc-list">
            <?php foreach ($docs as $doc): ?>
                <a href="?id=<?= $doc['id'] ?>" class="<?= $doc['id'] == $current_id ? 'active' : '' ?>">
                    <?= htmlspecialchars($doc['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($current_doc): ?>
            <a class="btn btn-outline-primary w-100 mb-3" href="edit_docs.php?id=<?= $current_id ?>">✏ Редактировать</a>
        <?php endif; ?>
        <a class="btn btn-outline-success w-100 mb-3" href="add_docs.php">➕ Добавить раздел</a>
    </div>

    <div class="content">
        <?php if ($current_doc): ?>
            <h1 class="mb-3"><?= htmlspecialchars($current_doc['title']) ?></h1>
            <div class="ql-container ql-snow">
                <div class="ql-editor ql-snow"><?= processQuillContent($current_doc['content']) ?></div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Выберите раздел документации</div>
        <?php endif; ?>
    </div>
</div>

<script>
// Обработка кликов по списку документов
document.getElementById('doc-list').addEventListener('click', function(e) {
    if (e.target.tagName === 'A') {
        e.preventDefault();
        const id = new URL(e.target.href).searchParams.get('id');
        window.location.href = `?id=${id}`;
    }
});

// Фикс для изображений - добавляем классы после загрузки
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.querySelector('.ql-editor');
    if (editor) {
        editor.classList.add('ql-snow');
        editor.style.whiteSpace = 'normal';
    }
});
</script>
</body>
</html>
