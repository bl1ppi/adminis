<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$current_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete']) && !isset($_POST['create'])) || isset($_GET['edit']);

// Обработка создания нового раздела
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $stmt = $pdo->prepare("INSERT INTO documentation (title, content) VALUES (?, ?)");
    $stmt->execute(['Новый раздел', '']);
    $new_id = $pdo->lastInsertId();
    header("Location: index.php?id=$new_id&edit=1");
    exit;
}

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $delete_id = (int)($_POST['id'] ?? 0);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM documentation WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: index.php");
        exit;
    }
}

$docs = $pdo->query("SELECT id, title FROM documentation ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$current_doc = null;
if ($current_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM documentation WHERE id = ?");
    $stmt->execute([$current_id]);
    $current_doc = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$current_doc && count($docs) > 0) {
    $current_id = $docs[0]['id'];
    $current_doc = $pdo->query("SELECT * FROM documentation WHERE id = $current_id")->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete']) && !isset($_POST['create'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';

    if ($id > 0 && $title !== '' && $content !== '') {
        $stmt = $pdo->prepare("UPDATE documentation SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        header("Location: index.php?id=$id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📘 Документация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.5.0/github-markdown.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" />
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }
        .layout-wrapper { 
            display: flex; 
            min-height: 100vh;
        }
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            padding: 20px;
            border-right: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .content {
            flex-grow: 1;
            padding: 30px 50px; /* Увеличиваем боковые отступы */
            background-color: #ffffff;
            max-width: calc(100% - 250px); /* Учитываем ширину сайдбара */
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
        }
        #doc-list a.active {
            background-color: #d0ebff;
            color: #0b5ed7;
            font-weight: 500;
        }
        .editor-toolbar.fullscreen, 
        .EasyMDEContainer.fullscreen {
            z-index: 1050;
        }
        /* Markdown стили */
        .markdown-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .markdown-body {
            background-color: transparent;
            color: #24292e;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }
        .markdown-body h1, 
        .markdown-body h2, 
        .markdown-body h3, 
        .markdown-body h4, 
        .markdown-body h5, 
        .markdown-body h6 {
            color: #24292e;
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
            border-bottom: 1px solid #eaecef;
            padding-bottom: 0.3em;
        }
        .markdown-body p {
            margin-top: 0;
            margin-bottom: 16px;
        }
        .markdown-body a {
            color: #0366d6;
            text-decoration: none;
        }
        .markdown-body a:hover {
            text-decoration: underline;
        }
        .markdown-body code {
            background-color: rgba(27,31,35,0.05);
            border-radius: 6px;
            padding: 0.2em 0.4em;
            font-family: ui-monospace, SFMono-Regular, SF Mono, Menlo, Consolas, Liberation Mono, monospace;
            font-size: 85%;
        }
        .markdown-body pre {
            background-color: #f6f8fa;
            border-radius: 6px;
            padding: 16px;
            overflow: auto;
            line-height: 1.45;
            border: 1px solid #e1e4e8;
        }
        .markdown-body pre code {
            background-color: transparent;
            padding: 0;
            border-radius: 0;
        }
        .markdown-body blockquote {
            border-left: 4px solid #dfe2e5;
            color: #6a737d;
            padding: 0 1em;
            margin: 0 0 16px 0;
        }
        /* Кнопки */
        .edit-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .edit-buttons button,
        .edit-buttons a.btn {
            min-width: 100px;
            flex: 1;
        }
        /* Адаптивность */
        @media (max-width: 768px) {
            .layout-wrapper {
                flex-direction: column;
            }
            .sidebar {
                min-width: 100%;
                max-width: 100%;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
        }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <div class="sidebar">
        <h5 class="mb-3">📘 Разделы</h5>
        <div class="mb-3" id="doc-list">
            <?php foreach ($docs as $doc): ?>
                <a href="?id=<?= $doc['id'] ?>" class="<?= $doc['id'] == $current_id ? 'active' : '' ?>">
                    <?= htmlspecialchars($doc['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form method="post" style="margin: 0;">
            <button type="submit" name="create" class="btn btn-outline-success w-100 mb-3">➕ Добавить раздел</button>
        </form>
    </div>

    <div class="content">
        <?php if ($current_doc): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <?php if (!$editing): ?>
                    <h1 class="mb-0"><?= htmlspecialchars($current_doc['title']) ?></h1>
                    <a class="btn btn-outline-primary" href="?id=<?= $current_id ?>&edit=1">✏ Редактировать</a>
                <?php else: ?>
                    <input type="text" name="title" class="form-control me-3" value="<?= htmlspecialchars($current_doc['title']) ?>" form="editForm">
                    <div class="edit-buttons">
                        <button type="submit" form="editForm" class="btn btn-outline-success">💾 Сохранить</button>
                        <form method="post" onsubmit="return confirm('Удалить раздел?');" style="margin:0; flex: 1;">
                            <input type="hidden" name="id" value="<?= $current_doc['id'] ?>">
                            <button type="submit" name="delete" class="btn btn-outline-danger w-100">🗑 Удалить</button>
                        </form>
                        <a href="?id=<?= $current_id ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$editing): ?>
                <div class="markdown-container">
                    <div id="markdown-view" class="markdown-body"></div>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        // Настройки marked
                        marked.setOptions({
                            gfm: true,
                            breaks: true,
                            highlight: function(code, lang) {
                                if (hljs.getLanguage(lang)) {
                                    return hljs.highlight(lang, code).value;
                                }
                                return hljs.highlightAuto(code).value;
                            }
                        });

                        const raw = <?= json_encode($current_doc['content']) ?>;
                        const html = marked.parse(raw);
                        document.getElementById('markdown-view').innerHTML = html;

                        // Подсветка кода
                        document.querySelectorAll('pre code').forEach(block => {
                            hljs.highlightElement(block);
                        });
                    });
                </script>
            <?php else: ?>
                <form method="post" id="editForm" onsubmit="return submitForm();">
                    <input type="hidden" name="id" value="<?= $current_doc['id'] ?>">
                    <input type="hidden" name="content" id="hiddenContent">
                    <textarea id="editor"><?= htmlspecialchars($current_doc['content']) ?></textarea>
                </form>
                
                <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
                <script>
                    const easyMDE = new EasyMDE({ 
                        element: document.getElementById('editor'),
                        spellChecker: false,
                        autofocus: true,
                        status: false,
                        placeholder: "Введите документацию в Markdown...",
                        sideBySideFullscreen: false,
                        toolbar: [
                            'bold', 'italic', 'heading', '|',
                            'quote', 'unordered-list', 'ordered-list', '|',
                            'link', 'image', '|',
                            'preview', 'side-by-side', 'fullscreen', '|',
                            'guide'
                        ]
                    });

                    function submitForm() {
                        document.getElementById('hiddenContent').value = easyMDE.value();
                        return true;
                    }
                </script>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Выберите раздел документации</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
