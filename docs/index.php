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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
        .p-center, .href-center {
            text-align: center;
            display: block;
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
        #doc-content {
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        
        #doc-content h1 {
            font-size: 24px;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.3rem;
        }
        
        #doc-content h2 {
            font-size: 20px;
            margin-top: 1.25rem;
            margin-bottom: 0.6rem;
            color: #2c3e50;
        }
        
        #doc-content h3 {
            font-size: 17px;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #34495e;
        }
        
        #doc-content p {
            margin-bottom: 0.8rem;
        }
        
        #doc-content ul {
            margin-bottom: 1rem;
            padding-left: 1.2rem;
        }
        
        #doc-content li {
            margin-bottom: 0.4rem;
        }
    </style>
</head>

<body>
<div class="layout-wrapper">
    <div class="sidebar min-vh-100 bg-light p-3">
        <h5 class="mb-3">📘 Разделы</h5>
        <div class="mb-3" id="doc-list">Загрузка...</div>

        <a class="btn btn-outline-primary w-100 mb-3" id="edit-link" href="#">✏️ Редактировать</a>
        <a class="btn btn-outline-success w-100 mb-3" href="add_docs.php">➕ Добавить раздел</a>
    </div>

    <div class="content">
        <h1 class="mb-3" id="doc-title">Загрузка...</h1>
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
