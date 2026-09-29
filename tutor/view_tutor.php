<?php
require_once __DIR__ . '/../config/config.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT tpf.*, u.full_name, u.gender, u.user_id FROM tutor_profiles tpf JOIN users u ON u.user_id = tpf.user_id WHERE tpf.tutor_profile_id = ?"
);
$stmt->execute([$id]);
$tutor = $stmt->fetch();

if (!$tutor) {
    setFlash('error', 'Tutor not found.');
    redirect('/tutor/browse_tutors.php');
}

$subjects = $db->prepare(
    "SELECT s.subject_name FROM tutor_subjects ts JOIN subjects s ON s.subject_id = ts.subject_id WHERE ts.tutor_profile_id = ?"
);
$subjects->execute([$id]);
$subjects = array_column($subjects->fetchAll(), 'subject_name');

$reviews = $db->prepare(
    "SELECT r.*, u.full_name AS reviewer_name FROM reviews r JOIN users u ON u.user_id = r.reviewer_user_id
     WHERE r.reviewee_user_id = ? ORDER BY r.created_at DESC"
);
$reviews->execute([$tutor['user_id']]);
$reviews = $reviews->fetchAll();

$availability = $db->prepare('SELECT * FROM tutor_availability WHERE tutor_profile_id = ? AND is_available = 1 ORDER BY FIELD(day_of_week,"Mon","Tue","Wed","Thu","Fri","Sat","Sun")');
$availability->execute([$id]);
$availability = $availability->fetchAll();

$user = currentUser();
$isFavorited = false;
if ($user) {
    $check = $db->prepare('SELECT favorite_id FROM favorites WHERE user_id = ? AND tutor_profile_id = ?');
    $check->execute([$user['user_id'], $id]);
    $isFavorited = (bool) $check->fetch();
}

if (isset($_GET['favorite']) && $user) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    if ($isFavorited) {
        $db->prepare('DELETE FROM favorites WHERE user_id = ? AND tutor_profile_id = ?')->execute([$user['user_id'], $id]);
        setFlash('success', 'Removed from favorites.');
    } else {
        $db->prepare('INSERT INTO favorites (user_id, tutor_profile_id) VALUES (?, ?)')->execute([$user['user_id'], $id]);
        setFlash('success', 'Added to favorites.');
    }
    redirect('/tutor/view_tutor.php?id=' . $id);
}

$pageTitle = $tutor['full_name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <div class="card">
        <div class="flex-between">
            <div style="display:flex; gap:16px; align-items:center;">
                <div class="avatar" style="width:64px;height:64px;font-size:24px;"><?= strtoupper(substr($tutor['full_name'],0,1)) ?></div>
                <div>
                    <h2 class="mt-0"><?= e($tutor['full_name']) ?> <?php if ($tutor['is_profile_verified']): ?><span class="badge badge-active">Verified</span><?php endif; ?></h2>
                    <p class="text-muted"><?= e($tutor['education']) ?> &middot; <?= e($tutor['institution']) ?></p>
                    <p><span class="rating-stars"><?= str_repeat('★', (int)round($tutor['avg_rating'])) . str_repeat('☆', 5-(int)round($tutor['avg_rating'])) ?></span> <?= e($tutor['avg_rating']) ?>/5 (<?= (int)$tutor['total_reviews'] ?> reviews)</p>
                </div>
            </div>
            <?php if ($user && $user['user_id'] != $tutor['user_id']): ?>
                <a href="?id=<?= $id ?>&favorite=1&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-outline btn-sm"><?= $isFavorited?'Remove Favorite':'Save to Favorites' ?></a>
            <?php endif; ?>
        </div>

        <div class="grid grid-3 mt-2">
            <div><strong>Experience:</strong> <?= e($tutor['experience_years']) ?> years</div>
            <div><strong>Location:</strong> <?= e($tutor['teaching_location']) ?></div>
            <div><strong>Mode:</strong> <?= ucfirst(e($tutor['teaching_mode'])) ?></div>
            <div><strong>Expected Salary:</strong> <?= money($tutor['expected_salary']) ?></div>
            <div><strong>Subjects:</strong> <?= $subjects ? e(implode(', ', $subjects)) : 'Not specified' ?></div>
        </div>

        <div class="section-title">About</div>
        <p><?= nl2br(e($tutor['bio'] ?: 'No bio provided.')) ?></p>

        <?php if ($availability): ?>
        <div class="section-title">Weekly Availability</div>
        <table>
            <tr><th>Day</th><th>Time</th></tr>
            <?php foreach ($availability as $a): ?>
                <tr><td><?= e($a['day_of_week']) ?></td><td><?= e(substr($a['start_time'],0,5)) ?> - <?= e(substr($a['end_time'],0,5)) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if ($user): ?>
        <a href="<?= BASE_URL ?>/reports/submit_report.php?user_id=<?= $tutor['user_id'] ?>" class="btn btn-secondary btn-sm mt-2">Report this Tutor</a>
        <?php endif; ?>
    </div>

    <div class="card mt-2">
        <h3>Reviews (<?= count($reviews) ?>)</h3>
        <?php if (!$reviews): ?>
            <p class="empty-state">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <div class="card" style="background:var(--bg-navy-light)">
                    <div class="flex-between">
                        <strong><?= e($r['reviewer_name']) ?></strong>
                        <span class="rating-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5-$r['rating']) ?></span>
                    </div>
                    <p class="mt-1"><?= nl2br(e($r['review_text'])) ?></p>
                    <p class="text-muted"><?= timeAgo($r['created_at']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
