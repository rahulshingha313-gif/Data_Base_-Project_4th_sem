<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$user = currentUser();
$spId = currentStudentProfileId();

$profile = $db->prepare('SELECT * FROM student_profiles WHERE student_profile_id = ?');
$profile->execute([$spId]);
$profile = $profile->fetch();

$completion = 0;
if ($profile) {
    $fields = ['class_level','institution','guardian_name','guardian_phone','address','bio'];
    $filled = 0;
    foreach ($fields as $f) if (!empty($profile[$f])) $filled++;
    $completion = round(($filled / count($fields)) * 100);
}

$myPostsCount = $db->prepare('SELECT COUNT(*) FROM tuition_posts WHERE student_profile_id = ?');
$myPostsCount->execute([$spId]);
$myPostsCount = $myPostsCount->fetchColumn();

$pendingApps = $db->prepare(
    "SELECT COUNT(*) FROM applications a JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
     WHERE tp.student_profile_id = ? AND a.status = 'pending'"
);
$pendingApps->execute([$spId]);
$pendingApps = $pendingApps->fetchColumn();

$upcoming = $db->prepare(
    "SELECT * FROM tuition_schedule WHERE student_user_id = ? AND status = 'scheduled' AND class_date >= CURDATE() ORDER BY class_date, start_time LIMIT 5"
);
$upcoming->execute([$user['user_id']]);
$upcoming = $upcoming->fetchAll();

$paymentSummary = $db->prepare(
    "SELECT status, COUNT(*) c, SUM(amount) total FROM payments WHERE student_user_id = ? GROUP BY status"
);
$paymentSummary->execute([$user['user_id']]);
$paymentSummary = $paymentSummary->fetchAll();

$attendanceSummary = $db->prepare(
    "SELECT a.status, COUNT(*) c FROM attendance a
     JOIN tuition_schedule ts ON ts.schedule_id = a.schedule_id
     WHERE ts.student_user_id = ? GROUP BY a.status"
);
$attendanceSummary->execute([$user['user_id']]);
$attendanceSummary = $attendanceSummary->fetchAll();

$favCount = $db->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
$favCount->execute([$user['user_id']]);
$favCount = $favCount->fetchColumn();

$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <h2>Welcome back, <?= e($user['full_name']) ?></h2>

        <div class="grid grid-4">
            <div class="card stat-card"><div class="value"><?= $completion ?>%</div><div class="label">Profile Completion</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$myPostsCount ?></div><div class="label">My Tuition Posts</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$pendingApps ?></div><div class="label">Pending Applications</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$favCount ?></div><div class="label">Saved Favorites</div></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div class="card">
                <h3>Upcoming Classes</h3>
                <?php if (!$upcoming): ?>
                    <p class="empty-state">No upcoming classes scheduled.</p>
                <?php else: ?>
                    <table>
                        <tr><th>Date</th><th>Time</th><th>Topic</th></tr>
                        <?php foreach ($upcoming as $s): ?>
                        <tr>
                            <td><?= e($s['class_date']) ?></td>
                            <td><?= e(substr($s['start_time'],0,5)) ?>-<?= e(substr($s['end_time'],0,5)) ?></td>
                            <td><?= e($s['topic'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/schedule/index.php?view=student" class="btn btn-outline btn-sm mt-2">View Full Schedule</a>
            </div>

            <div class="card">
                <h3>Payment Summary</h3>
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
                <a href="<?= BASE_URL ?>/payments/student_view.php" class="btn btn-outline btn-sm mt-2">View All Payments</a>
            </div>
        </div>

        <div class="card mt-2">
            <h3>Attendance Summary</h3>
            <?php if (!$attendanceSummary): ?>
                <p class="empty-state">No attendance records yet.</p>
            <?php else: ?>
                <div class="grid grid-3">
                <?php foreach ($attendanceSummary as $a): ?>
                    <div><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span> &times; <?= (int)$a['c'] ?></div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
