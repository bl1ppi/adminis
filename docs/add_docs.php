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
        header("Location: docs.php?id=$newId");
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
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <link rel="stylesheet" href="../includes/style.css">
  <style>
    body { font-family: sans-serif; margin: 20px; }
    input[type="text"] {
      width: 100%; font-size: 16px; padding: 6px; margin-bottom: 10px;
    }
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

<h1>➕ Новый раздел документации</h1>

<?php if (!empty($error)): ?>
  <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post" onsubmit="return submitForm();">
  <label>Название раздела:</label><br>
  <input type="text" name="title" required><br>

  <label>Содержимое:</label><br>
  <div id="editor"></div>

  <input type="hidden" name="content" id="hiddenContent">

  <button type="submit">💾 Создать</button>
  <a href="index.php">Отмена</a>
</form>

<!-- Quill -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  const quill = new Quill('#editor', {
    theme: 'snow'
  });

  function submitForm() {
    const html = quill.root.innerHTML;
    document.getElementById('hiddenContent').value = html;
    return true;
  }
</script>

</body>
</html>
