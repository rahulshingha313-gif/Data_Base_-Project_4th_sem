<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();
$view = $_GET['view'] ?? (hasRole('tutor') ? 'tutor' : 'student');

// Handle creating a new scheduled class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    verifyCsrf();
    $applicationId = (int) $_POST['application_id'];
    $date = $_POST['class_date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $topic = trim($_POST['topic'] ?? '');

    $app = $db->prepare(
        "SELECT a.*, tp.tuition_post_id, tp.student_profile_id, sp.user_id AS student_user_id, tpf.user_id AS tutor_user_id
         FROM applications a
         JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
         JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
         JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
         WHERE a.application_id = ? AND a.status = 'accepted'"
    );
    $app->execute([$applicationId]);
    $app = $app->fetch();

    // Only the involved student or tutor can create the schedule
    if ($app && ($app['student_user_id'] == $user['user_id'] || $app['tutor_user_id'] == $user['user_id']) && $date && $start && $end) {
        $db->prepare(
            'INSERT INTO tuition_schedule (application_id, tuition_post_id, student_user_id, tutor_user_id, class_date, start_time, end_time, topic, status) VALUES (?,?,?,?,?,?,?,?,\'scheduled\')'
        )->execute([$applicationId, $app['tuition_post_id'], $app['student_user_id'], $app['tutor_user_id'], $date, $start, $end, $topic]);

        $notifyId = $app['student_user_id'] == $user['user_id'] ? $app['tutor_user_id'] : $app['student_user_id'];
        notify($notifyId, 'schedule_created', 'New Class Scheduled', 'A class was scheduled for ' . $date . ' at ' . $start . '.', '/schedule/index.php');
        setFlash('success', 'Class scheduled successfully.');
    } else {
        setFlash('error', 'Unable to create schedule for that application.');
    }
    redirect('/schedule/index.php?view=' . ($view === 'tutor' ? 'tutor' : 'student'));
}

// Handle status change (complete / cancel)
if (isset($_GET['status'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $schedId = (int) $_GET['id'];
    $newStatus = $_GET['status'];
    if (in_array($newStatus, ['completed','cancelled'], true)) {
        $stmt = $db->prepare('UPDATE tuition_schedule SET status = ? WHERE schedule_id = ? AND (student_user_id = ? OR tutor_user_id = ?)');
        $stmt->execute([$newStatus, $schedId, $user['user_id'], $user['user_id']]);
        setFlash('success', 'Class marked as ' . $newStatus . '.');
    }
    redirect('/schedule/index.php?view=' . $view);
}

$userIdField = $view === 'tutor' ? 'tutor_user_id' : 'student_user_id';
$schedules = $db->prepare("SELECT ts.*, tp.title FROM tuition_schedule ts JOIN tuition_posts tp ON tp.tuition_post_id = ts.tuition_post_id WHERE ts.$userIdField = ? ORDER BY ts.class_date DESC, ts.start_time DESC");
$schedules->execute([$user['user_id']]);
$schedules = $schedules->fetchAll();

// Accepted applications the user can create a schedule for
if ($view === 'tutor') {
    $accepted = $db->prepare(
        "SELECT a.application_id, tp.title FROM applications a
         JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
         JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
         WHERE tpf.user_id = ? AND a.status = 'accepted'"
    );
} else {
    $accepted = $db->prepare(
        "SELECT a.application_id, tp.title FROM applications a
         JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
         JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
         WHERE sp.user_id = ? AND a.status = 'accepted'"
    );
}
$accepted->execute([$user['user_id']]);
$accepted = $accepted->fetchAll();

$pageTitle = 'Schedule';
include __DIR__ . '/../includes/header.php';
$sidebarFile = hasRole('tutor') && $view === 'tutor' ? 'sidebar_tutor.php' : 'sidebar_student.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/' . $sidebarFile; ?>
    <div>
        <h2>Tuition Schedule (<?= ucfirst($view) ?> View)</h2>

        <?php if ($accepted): ?>
        <div class="card">
            <h3>Create New Class Schedule</h3>
            <form method="POST" action="?view=<?= $view ?>" class="filter-bar">
                <?= csrfField() ?>
                <div>
                    <label>Tuition</label>
                    <select name="application_id" required>
                        <?php foreach ($accepted as $a): ?>
                            <option value="<?= (int)$a['application_id'] ?>"><?= e($a['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Date</label><input type="date" name="class_date" required></div>
                <div><label>Start</label><input type="time" name="start_time" required></div>
                <div><label>End</label><input type="time" name="end_time" required></div>
                <div><label>Topic</label><input type="text" name="topic" placeholder="e.g. Algebra"></div>
                <div><button type="submit" name="create" value="1" class="btn btn-primary btn-block">Schedule</button></div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card mt-2">
            <?php if (!$schedules): ?>
                <p class="empty-state">No classes scheduled yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Tuition</th><th>Date</th><th>Time</th><th>Topic</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?= e($s['title']) ?></td>
                    <td><?= e($s['class_date']) ?></td>
                    <td><?= e(substr($s['start_time'],0,5)) ?>-<?= e(substr($s['end_time'],0,5)) ?></td>
                    <td><?= e($s['topic'] ?: '-') ?></td>
                    <td><span class="badge badge-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
                    <td>
                        <?php if ($s['status'] === 'scheduled'): ?>
                            <a href="?id=<?= (int)$s['schedule_id'] ?>&status=completed&view=<?= $view ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-primary btn-sm">Complete</a>
                            <a href="?id=<?= (int)$s['schedule_id'] ?>&status=cancelled&view=<?= $view ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm">Cancel</a>
                            <?php if ($view === 'tutor'): ?>
                            <a href="<?= BASE_URL ?>/attendance/mark.php?schedule_id=<?= (int)$s['schedule_id'] ?>" class="btn btn-outline btn-sm">Attendance</a>
                            <?php endif; ?>
                        <?php elseif ($s['status'] === 'completed' && $view === 'student'): ?>
                            <a href="<?= BASE_URL ?>/reviews/give_review.php?post_id=<?= (int)$s['tuition_post_id'] ?>&schedule_id=<?= (int)$s['schedule_id'] ?>" class="btn btn-outline btn-sm">Review</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
