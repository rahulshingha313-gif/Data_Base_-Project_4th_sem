<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();

if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['delete'];
    $rev = $db->prepare('SELECT reviewee_user_id FROM reviews WHERE review_id = ?');
    $rev->execute([$id]);
    $revRow = $rev->fetch();
    $db->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([$id]);

    if ($revRow) {
        $agg = $db->prepare('SELECT AVG(rating) avg_r, COUNT(*) c FROM reviews WHERE reviewee_user_id = ?');
        $agg->execute([$revRow['reviewee_user_id']]);
        $aggRow = $agg->fetch();
        $db->prepare('UPDATE tutor_profiles SET avg_rating = ?, total_reviews = ? WHERE user_id = ?')
           ->execute([$aggRow['avg_r'] ? round($aggRow['avg_r'], 2) : 0, $aggRow['c'], $revRow['reviewee_user_id']]);
    }
    logAdminAction($admin['user_id'], 'delete_review', 'review', $id, 'Removed a review.');
    setFlash('success', 'Review deleted.');
    redirect('/admin/reviews.php');
}

$reviews = $db->query(
    "SELECT r.*, ru.full_name AS reviewer_name, re.full_name AS reviewee_name, tp.title
     FROM reviews r
     JOIN users ru ON ru.user_id = r.reviewer_user_id
     JOIN users re ON re.user_id = r.reviewee_user_id
     JOIN tuition_posts tp ON tp.tuition_post_id = r.tuition_post_id
     ORDER BY r.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Reviews';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Manage Reviews</h2>
        <div class="card">
            <?php if (!$reviews): ?><p class="empty-state">No reviews yet.</p><?php else: ?>
            <table>
                <tr><th>Reviewer</th><th>Reviewee</th><th>Tuition</th><th>Rating</th><th>Text</th><th>Actions</th></tr>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?= e($r['reviewer_name']) ?></td>
                    <td><?= e($r['reviewee_name']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><span class="rating-stars"><?= str_repeat('★',$r['rating']) . str_repeat('☆',5-$r['rating']) ?></span></td>
                    <td><?= e(mb_strimwidth($r['review_text'],0,60,'...')) ?></td>
                    <td><a href="?delete=<?= (int)$r['review_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this review?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
