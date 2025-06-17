<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';

    if ($title !== '' && $content !== '') {
        $stmt = $pdo->prepare("INSERT INTO documentation (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);
        $newId = $pdo->lastInsertId();
        header("Location: index.php?id=$newId");
        exit;
    } else {
        $error = "Заполните все поля.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Добавить новый раздел</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <style>
    #editor {
      height: 400px;
      background: white;
    }
    .ql-toolbar.ql-snow {
      border-radius: 5px 5px 0 0;
    }
    .ql-container.ql-snow {
      border-radius: 0 0 5px 5px;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <h1 class="mb-4 text-center">➕ Новый раздел документации</h1>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return submitForm();">
    <div class="mb-3">
      <label class="form-label">Название раздела:</label>
      <input type="text" name="title" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Содержимое:</label>
      <div id="editor"></div>
      <input type="hidden" name="content" id="hiddenContent">
    </div>

    <div class="d-flex justify-content-center gap-3 mt-4">
      <button type="submit" class="btn btn-outline-success">💾 Создать</button>
      <a href="index.php" class="btn btn-outline-secondary">🚫 Отмена</a>
    </div>
  </form>
</div>

<!-- Quill -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  const quill = new Quill('#editor', {
    theme: 'snow'
  });

  function submitForm() {
    document.getElementById('hiddenContent').value = quill.root.innerHTML;
    return true;
  }
</script>
</body>
</html>

