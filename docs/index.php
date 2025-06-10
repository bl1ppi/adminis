<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
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
            height: calc(100vh - 50px);
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
            cursor: pointer;
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
        .action-links {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <h3>📘 Разделы</h3>
        <div id="doc-list">Загрузка...</div>
        <hr>
        <div class="action-links">
            <a id="edit-link" href="#">✏️ Редактировать</a>
            <a href="add_docs.php">➕ Добавить раздел</a>
        </div>
    </div>

    <div class="content">
        <h1 id="doc-title">Загрузка...</h1>
        <div id="doc-content"></div>
    </div>
</div>

<script>
function loadDocs(id = null) {
    const url = 'data.php' + (id ? `?id=${id}` : '');
    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Список
            const docList = document.getElementById('doc-list');
            docList.innerHTML = '';
            data.docs.forEach(doc => {
                const link = document.createElement('a');
                link.textContent = doc.title;
                link.className = (doc.id === data.current_id ? 'active' : '');
                link.onclick = () => loadDocs(doc.id);
                docList.appendChild(link);
            });

            // Контент
            document.getElementById('doc-title').textContent = data.current.title;
            document.getElementById('doc-content').innerHTML = data.current.content;
            document.getElementById('edit-link').href = 'edit_docs.php?id=' + data.current_id;
        });
}

loadDocs();
</script>
</body>
</html>
