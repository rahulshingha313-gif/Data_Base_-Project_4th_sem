<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();

$reviews = $db->prepare(
    "SELECT r.*, u.full_name AS reviewer_name, tp.title FROM reviews r
     JOIN users u ON u.user_id = r.reviewer_user_id
     JOIN tuition_posts tp ON tp.tuition_post_id = r.tuition_post_id
     WHERE r.reviewee_user_id = ? ORDER BY r.created_at DESC"
);
$reviews->execute([$user['user_id']]);
$reviews = $reviews->fetchAll();

$pageTitle = 'My Reviews';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <h2>Reviews I've Received</h2>
        <?php if (!$reviews): ?>
            <div class="card"><p class="empty-state">No reviews yet. Complete a tuition to start receiving reviews.</p></div>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="card">
                    <div class="flex-between">
                        <strong><?= e($r['reviewer_name']) ?></strong>
                        <span class="rating-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5-$r['rating']) ?></span>
                    </div>
                    <p class="text-muted">For: <?= e($r['title']) ?> &middot; <?= timeAgo($r['created_at']) ?></p>
                    <p><?= nl2br(e($r['review_text'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
