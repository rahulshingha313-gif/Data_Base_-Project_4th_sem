<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();
$tpId = currentTutorProfileId();

$profile = $db->prepare('SELECT * FROM tutor_profiles WHERE tutor_profile_id = ?');
$profile->execute([$tpId]);
$profile = $profile->fetch();

$completion = 0;
if ($profile) {
    $fields = ['education','institution','experience_years','expected_salary','teaching_location','bio'];
    $filled = 0;
    foreach ($fields as $f) if (!empty($profile[$f])) $filled++;
    $completion = round(($filled / count($fields)) * 100);
}

$appCounts = $db->prepare("SELECT status, COUNT(*) c FROM applications WHERE tutor_profile_id = ? GROUP BY status");
$appCounts->execute([$tpId]);
$appCounts = $appCounts->fetchAll();

$acceptedCount = 0; $pendingCount = 0;
foreach ($appCounts as $c) {
    if ($c['status'] === 'accepted') $acceptedCount = $c['c'];
    if ($c['status'] === 'pending') $pendingCount = $c['c'];
}

$upcoming = $db->prepare(
    "SELECT * FROM tuition_schedule WHERE tutor_user_id = ? AND status = 'scheduled' AND class_date >= CURDATE() ORDER BY class_date, start_time LIMIT 5"
);
$upcoming->execute([$user['user_id']]);
$upcoming = $upcoming->fetchAll();

$paymentSummary = $db->prepare("SELECT status, COUNT(*) c, SUM(amount) total FROM payments WHERE tutor_user_id = ? GROUP BY status");
$paymentSummary->execute([$user['user_id']]);
$paymentSummary = $paymentSummary->fetchAll();

$pageTitle = 'Tutor Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <h2>Welcome back, <?= e($user['full_name']) ?></h2>

        <div class="grid grid-4">
            <div class="card stat-card"><div class="value"><?= $completion ?>%</div><div class="label">Profile Completion</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$pendingCount ?></div><div class="label">Pending Applications</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$acceptedCount ?></div><div class="label">Accepted Tuitions</div></div>
            <div class="card stat-card"><div class="value"><?= e($profile['avg_rating'] ?? '0') ?>/5</div><div class="label">Average Rating (<?= (int)($profile['total_reviews'] ?? 0) ?> reviews)</div></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div class="card">
                <h3>Upcoming Classes</h3>
                <?php if (!$upcoming): ?>
                    <p class="empty-state">No upcoming classes.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Date</th><th>Time</th><th>Topic</th></tr>
                        <?php foreach ($upcoming as $s): ?>
                        <tr><td><?= e($s['class_date']) ?></td><td><?= e(substr($s['start_time'],0,5)) ?>-<?= e(substr($s['end_time'],0,5)) ?></td><td><?= e($s['topic'] ?: '-') ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/schedule/index.php?view=tutor" class="btn btn-outline btn-sm mt-2">View Full Schedule</a>
            </div>
            <div class="card">
                <h3>Payment History Summary</h3>
                <?php if (!$paymentSummary): ?>
                    <p class="empty-state">No payment records yet.</p>
                <?php else: ?>
                    <?php foreach ($paymentSummary as $p): ?>
                    <div class="flex-between mt-1">
                        <span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span>
                        <span><?= (int)$p['c'] ?> record(s) &mdash; <?= money($p['total']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/payments/tutor_view.php" class="btn btn-outline btn-sm mt-2">View All Payments</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
