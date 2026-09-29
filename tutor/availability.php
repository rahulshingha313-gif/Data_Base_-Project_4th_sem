<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$tpId = currentTutorProfileId();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    verifyCsrf();
    $day = $_POST['day_of_week'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';

    $validDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    if (!in_array($day, $validDays, true)) $errors[] = 'Please select a valid day.';
    if (!$start || !$end || $start >= $end) $errors[] = 'Please provide a valid time range (end after start).';

    if (!$errors) {
        $db->prepare('INSERT INTO tutor_availability (tutor_profile_id, day_of_week, start_time, end_time, is_available) VALUES (?,?,?,?,1)')
           ->execute([$tpId, $day, $start, $end]);
        setFlash('success', 'Availability slot added.');
        redirect('/tutor/availability.php');
    }
}

if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare('DELETE FROM tutor_availability WHERE availability_id = ? AND tutor_profile_id = ?')->execute([(int)$_GET['delete'], $tpId]);
    setFlash('success', 'Slot removed.');
    redirect('/tutor/availability.php');
}

if (isset($_GET['toggle'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['toggle'];
    $stmt = $db->prepare('SELECT is_available FROM tutor_availability WHERE availability_id = ? AND tutor_profile_id = ?');
    $stmt->execute([$id, $tpId]);
    if ($row = $stmt->fetch()) {
        $db->prepare('UPDATE tutor_availability SET is_available = ? WHERE availability_id = ?')->execute([$row['is_available'] ? 0 : 1, $id]);
    }
    redirect('/tutor/availability.php');
}

$slots = $db->prepare('SELECT * FROM tutor_availability WHERE tutor_profile_id = ? ORDER BY FIELD(day_of_week,"Mon","Tue","Wed","Thu","Fri","Sat","Sun"), start_time');
$slots->execute([$tpId]);
$slots = $slots->fetchAll();

$pageTitle = 'Set Availability';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="card">
            <h2>Add Availability Slot</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="" class="filter-bar">
                <?= csrfField() ?>
                <div>
                    <label>Day</label>
                    <select name="day_of_week" required>
                        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Start Time</label><input type="time" name="start_time" required></div>
                <div><label>End Time</label><input type="time" name="end_time" required></div>
                <div><button type="submit" name="add" value="1" class="btn btn-primary btn-block">Add Slot</button></div>
            </form>
        </div>

        <div class="card mt-2">
            <h3>My Weekly Availability</h3>
            <?php if (!$slots): ?>
                <p class="empty-state">No availability slots added yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Day</th><th>Time</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($slots as $s): ?>
                <tr>
                    <td><?= e($s['day_of_week']) ?></td>
                    <td><?= e(substr($s['start_time'],0,5)) ?> - <?= e(substr($s['end_time'],0,5)) ?></td>
                    <td><span class="badge badge-<?= $s['is_available']?'active':'closed' ?>"><?= $s['is_available']?'Available':'Unavailable' ?></span></td>
                    <td>
                        <a href="?toggle=<?= (int)$s['availability_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-secondary btn-sm">Toggle</a>
                        <a href="?delete=<?= (int)$s['availability_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this slot?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
