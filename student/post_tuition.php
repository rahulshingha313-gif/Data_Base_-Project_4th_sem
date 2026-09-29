<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$spId = currentStudentProfileId();
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

    if ($title === '') $errors[] = 'Please enter a title.';
    if (!$subjectId) $errors[] = 'Please choose a subject.';
    if ($budgetMax < $budgetMin) $errors[] = 'Maximum budget must be greater than or equal to minimum budget.';

    if (!$errors) {
        $stmt = $db->prepare(
            'INSERT INTO tuition_posts (student_profile_id, subject_id, title, class_level, location, budget_min, budget_max, preferred_days, preferred_time, mode, gender_preference, description)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$spId, $subjectId, $title, $classLevel, $location, $budgetMin, $budgetMax, $days, $time, $mode, $genderPref, $description]);
        setFlash('success', 'Tuition post created successfully.');
        redirect('/student/my_posts.php');
    }
    setOld($_POST);
}

$pageTitle = 'Post a Tuition';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <div class="card">
            <h2>Post a Tuition Requirement</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <label>Title</label>
                <input type="text" name="title" value="<?= old('title') ?>" placeholder="e.g. Need Math Tutor for Class 9" required>

                <div class="form-row">
                    <div>
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int)$s['subject_id'] ?>"><?= e($s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Class / Level</label>
                        <input type="text" name="class_level" value="<?= old('class_level') ?>">
                    </div>
                </div>

                <label>Location</label>
                <input type="text" name="location" value="<?= old('location') ?>" placeholder="Area, City">

                <div class="form-row">
                    <div>
                        <label>Budget Min (BDT/month)</label>
                        <input type="number" step="0.01" name="budget_min" value="<?= old('budget_min','0') ?>">
                    </div>
                    <div>
                        <label>Budget Max (BDT/month)</label>
                        <input type="number" step="0.01" name="budget_max" value="<?= old('budget_max','0') ?>">
                    </div>
                </div>

                <label>Preferred Days</label>
                <div class="grid grid-4">
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                        <label class="checkbox-row"><input type="checkbox" name="days[]" value="<?= $d ?>"> <?= $d ?></label>
                    <?php endforeach; ?>
                </div>

                <div class="form-row">
                    <div>
                        <label>Preferred Time</label>
                        <input type="text" name="preferred_time" value="<?= old('preferred_time') ?>" placeholder="e.g. Evening">
                    </div>
                    <div>
                        <label>Mode</label>
                        <select name="mode">
                            <option value="offline">Offline</option>
                            <option value="online">Online</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                </div>

                <label>Tutor Gender Preference</label>
                <select name="gender_preference">
                    <option value="any">Any</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>

                <label>Description</label>
                <textarea name="description" placeholder="Describe what you're looking for..."><?= old('description') ?></textarea>

                <button type="submit" class="btn btn-primary mt-3">Publish Tuition Post</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; clearOld(); ?>
