<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();

if (isset($_GET['remove'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare('DELETE FROM favorites WHERE favorite_id = ? AND user_id = ?')->execute([(int)$_GET['remove'], $user['user_id']]);
    setFlash('success', 'Removed from favorites.');
    redirect('/student/favorites.php');
}

$postFavs = $db->prepare(
    "SELECT f.favorite_id, tp.* , s.subject_name FROM favorites f
     JOIN tuition_posts tp ON tp.tuition_post_id = f.tuition_post_id
     JOIN subjects s ON s.subject_id = tp.subject_id
     WHERE f.user_id = ? AND f.tuition_post_id IS NOT NULL"
);
$postFavs->execute([$user['user_id']]);
$postFavs = $postFavs->fetchAll();

$tutorFavs = $db->prepare(
    "SELECT f.favorite_id, tpf.*, u.full_name FROM favorites f
     JOIN tutor_profiles tpf ON tpf.tutor_profile_id = f.tutor_profile_id
     JOIN users u ON u.user_id = tpf.user_id
     WHERE f.user_id = ? AND f.tutor_profile_id IS NOT NULL"
);
$tutorFavs->execute([$user['user_id']]);
$tutorFavs = $tutorFavs->fetchAll();

$pageTitle = 'Favorites';
include __DIR__ . '/../includes/header.php';
$sidebarFile = hasRole('student') ? 'sidebar_student.php' : 'sidebar_tutor.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/' . $sidebarFile; ?>
    <div>
        <h2>Saved Tuition Posts</h2>
        <div class="grid grid-3">
            <?php foreach ($postFavs as $p): ?>
                <div class="card">
                    <h3><?= e($p['title']) ?></h3>
                    <p class="text-muted"><?= e($p['subject_name']) ?> &middot; <?= e($p['location']) ?></p>
                    <p><?= money($p['budget_min']) ?> - <?= money($p['budget_max']) ?></p>
                    <a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$p['tuition_post_id'] ?>" class="btn btn-outline btn-sm">View</a>
                    <a href="?remove=<?= (int)$p['favorite_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm">Remove</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$postFavs): ?><p class="empty-state">No saved tuition posts.</p><?php endif; ?>
        </div>

        <h2 class="mt-3">Saved Tutors</h2>
        <div class="grid grid-3">
            <?php foreach ($tutorFavs as $t): ?>
                <div class="card">
                    <h3><?= e($t['full_name']) ?></h3>
                    <p class="text-muted"><?= e($t['education']) ?></p>
                    <p>Rating: <?= e($t['avg_rating']) ?>/5</p>
                    <a href="<?= BASE_URL ?>/tutor/view_tutor.php?id=<?= (int)$t['tutor_profile_id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
                    <a href="?remove=<?= (int)$t['favorite_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm">Remove</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$tutorFavs): ?><p class="empty-state">No saved tutors.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
