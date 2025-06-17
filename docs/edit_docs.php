<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM documentation WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Раздел не найден.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';

    if ($title !== '' && $content !== '') {
        $stmt = $pdo->prepare("UPDATE documentation SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        header("Location: index.php?id=$id");
        exit;
    } else {
        $error = "Поля не могут быть пустыми.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Редактировать: <?= htmlspecialchars($doc['title']) ?></title>
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
  <h1 class="mb-4 text-center">✏️ Редактирование раздела</h1>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return submitForm();">
    <div class="mb-3">
      <label class="form-label">Название:</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($doc['title']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Содержимое:</label>
      <div id="editor"><?= $doc['content'] ?></div>
      <input type="hidden" name="content" id="hiddenContent">
    </div>

    <div class="d-flex justify-content-center gap-3 mt-4">
      <button type="submit" class="btn btn-outline-success">💾 Сохранить</button>
      <a href="index.php?id=<?= $id ?>" class="btn btn-outline-secondary">🚫 Отмена</a>
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

