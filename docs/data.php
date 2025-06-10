<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$docs = $pdo->query("SELECT id, title FROM documentation ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$current_id = isset($_GET['id']) ? (int)$_GET['id'] : ($docs[0]['id'] ?? 0);

$stmt = $pdo->prepare("SELECT title, content FROM documentation WHERE id = ?");
$stmt->execute([$current_id]);
$current_doc = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'docs' => $docs,
    'current_id' => $current_id,
    'current' => $current_doc
], JSON_UNESCAPED_UNICODE);
