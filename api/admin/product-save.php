<?php
require_once __DIR__ . '/../../core/auth.php';
Auth::requireAdmin();
header('Content-Type: application/json');

Auth::requireCsrf();
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['action'] ?? 'save';
$id     = intval($body['id'] ?? 0);

function updateProductReferences(int $fromId, int $toId): void {
    DB::execute("UPDATE product_categories SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
    DB::execute("UPDATE product_translations SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
    DB::execute("UPDATE product_variants SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
    DB::execute("UPDATE product_skus SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
    DB::execute("UPDATE reviews SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
    DB::execute("UPDATE order_items SET product_id = ? WHERE product_id = ?", [$toId, $fromId]);
}

function shiftProductIdsAfterDelete(int $deletedId): int {
    $rows = DB::query(
        "SELECT id FROM products WHERE id > ? ORDER BY id ASC",
        [$deletedId]
    );

    foreach ($rows as $row) {
        $oldId = (int)$row['id'];
        $newId = $oldId - 1;

        DB::execute("UPDATE products SET id = ? WHERE id = ?", [$newId, $oldId]);
        updateProductReferences($oldId, $newId);
    }

    return (int)(DB::queryOne("SELECT COALESCE(MAX(id), 0) + 1 AS nextId FROM products")['nextId'] ?? 1);
}

if ($action === 'delete' && $id > 0) {
    try {
        $product = DB::queryOne("SELECT id FROM products WHERE id = ?", [$id]);
        if (!$product) {
            echo json_encode(['error' => true, 'message' => 'Khong tim thay san pham']);
            exit;
        }

        $hasOrders = (int)(DB::queryOne(
            "SELECT COUNT(*) AS total FROM order_items WHERE product_id = ?",
            [$id]
        )['total'] ?? 0);

        if ($hasOrders > 0) {
            echo json_encode([
                'error' => true,
                'message' => 'San pham da co trong don hang, khong the sap xep lai ID an toan.'
            ]);
            exit;
        }

        DB::beginTransaction();
        DB::execute("SET FOREIGN_KEY_CHECKS = 0");

        DB::execute(
            "DELETE rm FROM review_medias rm
             JOIN reviews r ON r.id = rm.review_id
             WHERE r.product_id = ?",
            [$id]
        );
        DB::execute(
            "DELETE c FROM cart c
             JOIN product_skus s ON s.id = c.sku_id
             WHERE s.product_id = ?",
            [$id]
        );
        DB::execute("DELETE FROM reviews WHERE product_id = ?", [$id]);
        DB::execute("DELETE FROM product_categories WHERE product_id = ?", [$id]);
        DB::execute("DELETE FROM product_translations WHERE product_id = ?", [$id]);
        DB::execute("DELETE FROM product_variants WHERE product_id = ?", [$id]);
        DB::execute("DELETE FROM product_skus WHERE product_id = ?", [$id]);
        DB::execute("DELETE FROM products WHERE id = ?", [$id]);

        $nextId = shiftProductIdsAfterDelete($id);

        DB::execute("SET FOREIGN_KEY_CHECKS = 1");
        DB::commit();
        DB::execute("ALTER TABLE products AUTO_INCREMENT = $nextId");

        echo json_encode(['error' => false, 'message' => 'Da xoa san pham va sap xep lai ID']);
    } catch (Throwable $e) {
        try { DB::execute("SET FOREIGN_KEY_CHECKS = 1"); } catch (Throwable $ignored) {}
        if (DB::connect()->inTransaction()) {
            DB::rollback();
        }
        echo json_encode(['error' => true, 'message' => $e->getMessage()]);
    }
    exit;
}

$name         = trim($body['name']         ?? '');
$basePrice    = floatval($body['basePrice']    ?? 0);
$virtualPrice = floatval($body['virtualPrice'] ?? 0);
$images       = $body['images'] ?? [];
$imagesJson   = json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (empty($name) || $basePrice <= 0) {
    echo json_encode(['error' => true, 'message' => 'Ten va gia khong duoc de trong']);
    exit;
}

try {
    DB::beginTransaction();

    if ($id > 0) {
        DB::execute(
            "UPDATE products SET name=?, basePrice=?, virtualPrice=?, images=?, updatedAt=NOW() WHERE id=?",
            [$name, $basePrice, $virtualPrice, $imagesJson, $id]
        );
    } else {
        DB::execute(
            "INSERT INTO products (name, basePrice, virtualPrice, images, publishedAt, createdAt, updatedAt)
             VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())",
            [$name, $basePrice, $virtualPrice, $imagesJson]
        );
        $id = DB::lastInsertId();

        $categoryId = intval($body['category'] ?? 0);
        if ($categoryId > 0) {
            DB::execute(
                "INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)",
                [$id, $categoryId]
            );
        }

        $colors   = $body['colors']   ?? [];
        $sizes    = $body['sizes']    ?? [];
        $skuPrice = floatval($body['skuPrice']  ?? $basePrice);
        $skuStock = intval($body['skuStock']    ?? 0);

        if (!empty($colors)) {
            DB::execute(
                "INSERT INTO product_variants (product_id, name, options) VALUES (?, 'Màu sắc', ?)",
                [$id, json_encode($colors, JSON_UNESCAPED_UNICODE)]
            );
        }
        if (!empty($sizes)) {
            DB::execute(
                "INSERT INTO product_variants (product_id, name, options) VALUES (?, 'Kích thước', ?)",
                [$id, json_encode($sizes, JSON_UNESCAPED_UNICODE)]
            );
        }

        if (!empty($colors) && !empty($sizes)) {
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    DB::execute(
                        "INSERT INTO product_skus (product_id, value, price, stock) VALUES (?, ?, ?, ?)",
                        [$id, "$color-$size", $skuPrice, $skuStock]
                    );
                }
            }
        } elseif (!empty($colors)) {
            foreach ($colors as $color) {
                DB::execute(
                    "INSERT INTO product_skus (product_id, value, price, stock) VALUES (?, ?, ?, ?)",
                    [$id, $color, $skuPrice, $skuStock]
                );
            }
        } elseif (!empty($sizes)) {
            foreach ($sizes as $size) {
                DB::execute(
                    "INSERT INTO product_skus (product_id, value, price, stock) VALUES (?, ?, ?, ?)",
                    [$id, $size, $skuPrice, $skuStock]
                );
            }
        } else {
            DB::execute(
                "INSERT INTO product_skus (product_id, value, price, stock) VALUES (?, 'Mặc định', ?, ?)",
                [$id, $skuPrice, $skuStock]
            );
        }
    }

    DB::commit();
    echo json_encode(['error' => false, 'message' => 'Thanh cong', 'id' => $id]);
} catch (Throwable $e) {
    if (DB::connect()->inTransaction()) {
        DB::rollback();
    }
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
