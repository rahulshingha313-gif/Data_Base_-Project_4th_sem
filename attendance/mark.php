<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();
$scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

$sched = $db->prepare('SELECT ts.*, tp.title FROM tuition_schedule ts JOIN tuition_posts tp ON tp.tuition_post_id = ts.tuition_post_id WHERE ts.schedule_id = ? AND ts.tutor_user_id = ?');
$sched->execute([$scheduleId, $user['user_id']]);
$sched = $sched->fetch();

if (!$sched) {
    setFlash('error', 'Class not found or access denied.');
    redirect('/attendance/tutor_view.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!in_array($status, ['present','absent','late'], true)) $errors[] = 'Please choose a valid status.';

    if (!$errors) {
        $existing = $db->prepare('SELECT attendance_id FROM attendance WHERE schedule_id = ?');
        $existing->execute([$scheduleId]);
        if ($row = $existing->fetch()) {
            $db->prepare('UPDATE attendance SET status=?, notes=?, marked_by=? WHERE attendance_id=?')
               ->execute([$status, $notes, $user['user_id'], $row['attendance_id']]);
        } else {
            $db->prepare('INSERT INTO attendance (schedule_id, status, notes, marked_by) VALUES (?,?,?,?)')
               ->execute([$scheduleId, $status, $notes, $user['user_id']]);
        }
        setFlash('success', 'Attendance recorded.');
        redirect('/attendance/tutor_view.php');
    }
}

$current = $db->prepare('SELECT * FROM attendance WHERE schedule_id = ?');
$current->execute([$scheduleId]);
$current = $current->fetch();

$pageTitle = 'Mark Attendance';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="card">
            <h2>Mark Attendance</h2>
            <p class="text-muted"><?= e($sched['title']) ?> &middot; <?= e($sched['class_date']) ?> <?= e(substr($sched['start_time'],0,5)) ?>-<?= e(substr($sched['end_time'],0,5)) ?></p>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="schedule_id" value="<?= $scheduleId ?>">
                <label>Status</label>
                <select name="status" required>
                    <?php foreach (['present','absent','late'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($current['status'] ?? '')==$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Notes</label>
                <textarea name="notes" placeholder="Optional notes about the class"><?= e($current['notes'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary mt-3">Save Attendance</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
