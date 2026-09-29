<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$userId = (int) ($_GET['user_id'] ?? 0);

$profile = $db->prepare('SELECT tpf.*, u.full_name, u.email, u.phone, u.status FROM tutor_profiles tpf JOIN users u ON u.user_id = tpf.user_id WHERE tpf.user_id = ?');
$profile->execute([$userId]);
$profile = $profile->fetch();

if (!$profile) { setFlash('error', 'Tutor profile not found.'); redirect('/admin/users.php'); }

$posts = $db->prepare('SELECT * FROM tutor_posts WHERE tutor_profile_id = ? ORDER BY created_at DESC');
$posts->execute([$profile['tutor_profile_id']]);
$posts = $posts->fetchAll();

$reviews = $db->prepare('SELECT r.*, u.full_name AS reviewer_name FROM reviews r JOIN users u ON u.user_id = r.reviewer_user_id WHERE r.reviewee_user_id = ? ORDER BY r.created_at DESC');
$reviews->execute([$userId]);
$reviews = $reviews->fetchAll();

$pageTitle = 'Tutor Profile — ' . $profile['full_name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="text-muted">&larr; Back to Users</a>
        <div class="card mt-2">
            <div class="flex-between">
                <h2><?= e($profile['full_name']) ?> <?php if ($profile['is_profile_verified']): ?><span class="badge badge-active">Verified</span><?php else: ?><span class="badge badge-pending">Unverified</span><?php endif; ?></h2>
                <?php if (!$profile['is_profile_verified']): ?>
                <a href="<?= BASE_URL ?>/admin/tutor_verification.php?verify=<?= (int)$profile['tutor_profile_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-primary btn-sm">Verify Now</a>
                <?php endif; ?>
            </div>
            <p class="text-muted"><?= e($profile['email']) ?> &middot; <?= e($profile['phone'] ?? '') ?></p>
            <div class="grid grid-3 mt-2">
                <div><strong>Education:</strong> <?= e($profile['education'] ?? '-') ?></div>
                <div><strong>Institution:</strong> <?= e($profile['institution'] ?? '-') ?></div>
                <div><strong>Experience:</strong> <?= e($profile['experience_years']) ?> yrs</div>
                <div><strong>Expected Salary:</strong> <?= money($profile['expected_salary']) ?></div>
                <div><strong>Location:</strong> <?= e($profile['teaching_location'] ?? '-') ?></div>
                <div><strong>Rating:</strong> <?= e($profile['avg_rating']) ?>/5 (<?= (int)$profile['total_reviews'] ?>)</div>
            </div>
            <p class="mt-2"><?= nl2br(e($profile['bio'] ?? '')) ?></p>
        </div>

        <div class="card mt-2">
            <h3>Tutor Posts (<?= count($posts) ?>)</h3>
            <?php if (!$posts): ?><p class="empty-state">No posts.</p><?php else: ?>
            <table><tr><th>Title</th><th>Status</th><th>Created</th></tr>
                <?php foreach ($posts as $p): ?>
                <tr><td><?= e($p['title']) ?></td><td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td><td><?= timeAgo($p['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <div class="card mt-2">
            <h3>Reviews (<?= count($reviews) ?>)</h3>
            <?php if (!$reviews): ?><p class="empty-state">No reviews.</p><?php else: ?>
                <?php foreach ($reviews as $r): ?>
                <div class="card" style="background:var(--bg-navy-light)">
                    <div class="flex-between"><strong><?= e($r['reviewer_name']) ?></strong><span class="rating-stars"><?= str_repeat('★',$r['rating']) . str_repeat('☆',5-$r['rating']) ?></span></div>
                    <p><?= nl2br(e($r['review_text'])) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
