<?php
require_once __DIR__ . '/../core/db.php';
header('Content-Type: application/json');
$catId = intval($_GET['cat_id'] ?? 0);
if ($catId <= 0) { echo json_encode(['error' => true]); exit; }
$subs = DB::query("SELECT id, name FROM categories WHERE parentCategoryId = ?", [$catId]);
echo json_encode(['error' => false, 'data' => $subs]);