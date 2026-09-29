<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();

$records = $db->prepare(
    "SELECT ts.*, tp.title, at.status AS attendance_status, at.notes
     FROM tuition_schedule ts
     JOIN tuition_posts tp ON tp.tuition_post_id = ts.tuition_post_id
     LEFT JOIN attendance at ON at.schedule_id = ts.schedule_id
     WHERE ts.tutor_user_id = ? ORDER BY ts.class_date DESC"
);
$records->execute([$user['user_id']]);
$records = $records->fetchAll();

$pageTitle = 'Attendance';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <h2>Attendance Records</h2>
        <div class="card">
            <?php if (!$records): ?>
                <p class="empty-state">No classes yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Tuition</th><th>Date</th><th>Status</th><th>Attendance</th><th>Notes</th><th>Actions</th></tr>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['class_date']) ?></td>
                    <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                    <td><?= $r['attendance_status'] ? '<span class="badge badge-'.e($r['attendance_status']).'">'.e($r['attendance_status']).'</span>' : '<span class="text-muted">Not marked</span>' ?></td>
                    <td><?= e($r['notes'] ?? '-') ?></td>
                    <td><a href="<?= BASE_URL ?>/attendance/mark.php?schedule_id=<?= (int)$r['schedule_id'] ?>" class="btn btn-outline btn-sm">Mark/Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
