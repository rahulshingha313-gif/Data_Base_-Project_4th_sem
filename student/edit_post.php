<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$spId = currentStudentProfileId();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM tuition_posts WHERE tuition_post_id = ? AND student_profile_id = ?');
$stmt->execute([$id, $spId]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('error', 'Post not found or you do not have permission to edit it.');
    redirect('/student/my_posts.php');
}

$subjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim($_POST['title'] ?? '');
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $classLevel = trim($_POST['class_level'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $budgetMin = (float) ($_POST['budget_min'] ?? 0);
    $budgetMax = (float) ($_POST['budget_max'] ?? 0);
    $days = implode(',', $_POST['days'] ?? []);
    $time = trim($_POST['preferred_time'] ?? '');
    $mode = $_POST['mode'] ?? 'offline';
    $genderPref = $_POST['gender_preference'] ?? 'any';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'open';

    if ($title === '') $errors[] = 'Please enter a title.';

    if (!$errors) {
        $stmt = $db->prepare(
            'UPDATE tuition_posts SET subject_id=?, title=?, class_level=?, location=?, budget_min=?, budget_max=?, preferred_days=?, preferred_time=?, mode=?, gender_preference=?, description=?, status=?
             WHERE tuition_post_id=? AND student_profile_id=?'
        );
        $stmt->execute([$subjectId, $title, $classLevel, $location, $budgetMin, $budgetMax, $days, $time, $mode, $genderPref, $description, $status, $id, $spId]);
        setFlash('success', 'Tuition post updated.');
        redirect('/student/my_posts.php');
    }
}

$selectedDays = explode(',', $post['preferred_days'] ?? '');
$pageTitle = 'Edit Tuition Post';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <div class="card">
            <h2>Edit Tuition Post</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <label>Title</label>
                <input type="text" name="title" value="<?= e($post['title']) ?>" required>

                <div class="form-row">
                    <div>
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int)$s['subject_id'] ?>" <?= $s['subject_id']==$post['subject_id']?'selected':'' ?>><?= e($s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Class / Level</label>
                        <input type="text" name="class_level" value="<?= e($post['class_level']) ?>">
                    </div>
                </div>

                <label>Location</label>
                <input type="text" name="location" value="<?= e($post['location']) ?>">

                <div class="form-row">
                    <div><label>Budget Min</label><input type="number" step="0.01" name="budget_min" value="<?= e($post['budget_min']) ?>"></div>
                    <div><label>Budget Max</label><input type="number" step="0.01" name="budget_max" value="<?= e($post['budget_max']) ?>"></div>
                </div>

                <label>Preferred Days</label>
                <div class="grid grid-4">
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                        <label class="checkbox-row"><input type="checkbox" name="days[]" value="<?= $d ?>" <?= in_array($d,$selectedDays)?'checked':'' ?>> <?= $d ?></label>
                    <?php endforeach; ?>
                </div>

                <div class="form-row">
                    <div><label>Preferred Time</label><input type="text" name="preferred_time" value="<?= e($post['preferred_time']) ?>"></div>
                    <div>
                        <label>Mode</label>
                        <select name="mode">
                            <?php foreach (['offline','online','both'] as $m): ?>
                                <option value="<?= $m ?>" <?= $post['mode']==$m?'selected':'' ?>><?= ucfirst($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Tutor Gender Preference</label>
                        <select name="gender_preference">
                            <?php foreach (['any','male','female'] as $g): ?>
                                <option value="<?= $g ?>" <?= $post['gender_preference']==$g?'selected':'' ?>><?= ucfirst($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status">
                            <?php foreach (['open','in_progress','completed','closed'] as $st): ?>
                                <option value="<?= $st ?>" <?= $post['status']==$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Description</label>
                <textarea name="description"><?= e($post['description']) ?></textarea>

                <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
