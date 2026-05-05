<?php
?>
<div class="review_item_compact" style="padding:15px 0; border-bottom:1px solid #f0f0f0;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
        <!-- Avatar -->
        <?php if (!empty($review['user']['avatar'])): ?>
            <img src="<?= htmlspecialchars($review['user']['avatar']) ?>"
                 alt="<?= htmlspecialchars($review['user']['name'] ?? '') ?>"
                 style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
        <?php else: ?>
            <i class="fa-solid fa-circle-user" style="font-size:36px; color:#ccc;"></i>
        <?php endif; ?>

        <div>
            <strong style="font-size:14px;"><?= htmlspecialchars($review['user']['name'] ?? 'Ẩn danh') ?></strong>
            <!-- Rating stars -->
            <div>
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="fa-solid fa-star" style="color: <?= $s <= ($review['rating'] ?? 0) ? '#ffc107' : '#ddd' ?>;"></i>
                <?php endfor; ?>
            </div>
        </div>
        <span style="margin-left:auto; font-size:12px; color:#999;">
            <?= date('d/m/Y', strtotime($review['createdAt'] ?? 'now')) ?>
        </span>
    </div>

    <!-- Nội dung -->
    <p style="font-size:14px; color:#333; margin:0 0 8px;">
        <?= nl2br(htmlspecialchars($review['content'] ?? '')) ?>
    </p>

    <!-- Ảnh đính kèm (nếu có) -->
    <?php if (!empty($review['medias'])): ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
        <?php foreach ($review['medias'] as $media): ?>
            <?php if ($media['type'] === 'IMAGE'): ?>
            <img src="<?= htmlspecialchars($media['url']) ?>"
                 alt="Review image"
                 style="width:80px; height:80px; object-fit:cover; border-radius:4px; cursor:pointer;"
                 onclick="this.style.width='auto'; this.style.height='200px';">
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>