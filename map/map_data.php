<?php
require_once '../includes/db.php';
require_once 'map_model.php';

header('Content-Type: application/json');
echo json_encode(getMapData($pdo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
