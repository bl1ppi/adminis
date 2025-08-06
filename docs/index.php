<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$current_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = ($_SERVER['REQUEST_METHOD'] === 'POST') || isset($_GET['edit']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        .layout-wrapper { display: flex; }
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
        .ql-container.ql-snow { border: none !important; font-family: inherit; }
        .ql-editor { padding: 0 !important; white-space: normal !important; }
        .ql-snow .ql-editor img {
            max-width: 100%; height: auto; display: block; margin: 10px 0;
        }
        #editor { min-height: 300px; }
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
        <a class="btn btn-outline-success w-100 mb-3" href="add_docs.php">➕ Добавить раздел</a>
    </div>

    <div class="content">
        <?php if ($current_doc): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <?php if (!$editing): ?>
                    <h1 class="mb-0"><?= htmlspecialchars($current_doc['title']) ?></h1>
                    <a class="btn btn-outline-primary" href="?id=<?= $current_id ?>&edit=1">✏ Редактировать</a>
                <?php else: ?>
                    <input type="text" name="title" class="form-control me-3" value="<?= htmlspecialchars($current_doc['title']) ?>" form="editForm">
                    <div class="d-flex gap-2">
                        <button type="submit" form="editForm" class="btn btn-outline-success">💾 Сохранить</button>
                        <a href="?id=<?= $current_id ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$editing): ?>
                <div class="ql-container ql-snow">
                    <div class="ql-editor ql-snow"> <?= processQuillContent($current_doc['content']) ?> </div>
                </div>
            <?php else: ?>
                <form method="post" id="editForm" onsubmit="return submitForm();">
                    <input type="hidden" name="id" value="<?= $current_doc['id'] ?>">
                    <input type="hidden" name="content" id="hiddenContent">
                    <div class="mb-3">
                        <div id="editor"> <?= htmlspecialchars_decode($current_doc['content']) ?> </div>
                    </div>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Выберите раздел документации</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($editing): ?>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
const quill = new Quill('#editor', {
    theme: 'snow',
    formats: ['bold', 'italic', 'underline', 'strike', 'link', 'image', 'code-block', 'list', 'bullet', 'indent']
});

function submitForm() {
    const content = quill.root.innerHTML;
    document.getElementById('hiddenContent').value = content;
    return true;
}
</script>
<?php endif; ?>
</body>
</html>
