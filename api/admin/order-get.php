<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
$order = DB::queryOne(
    "SELECT o.*, p.method as paymentMethod, p.status as paymentStatus
     FROM orders o JOIN payments p ON o.payment_id = p.id WHERE o.id=?",
    [$id]
);
if (!$order) { echo json_encode(['error'=>true,'message'=>'Không tìm thấy']); exit; }

$items = DB::query("SELECT * FROM order_items WHERE order_id=?", [$id]);
$subtotal = array_sum(array_map(fn($i)=>$i['skuPrice']*$i['quantity'], $items));

echo json_encode(['error'=>false,'order'=>$order,'items'=>$items,'subtotal'=>$subtotal]);