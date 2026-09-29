<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$tpId = currentTutorProfileId();
$subjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim($_POST['title'] ?? '');
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $classLevel = trim($_POST['class_level'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $rate = (float) ($_POST['rate'] ?? 0);
    $mode = $_POST['mode'] ?? 'both';
    $description = trim($_POST['description'] ?? '');

    if ($title === '') $errors[] = 'Please enter a title.';
    if (!$subjectId) $errors[] = 'Please choose a subject.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO tutor_posts (tutor_profile_id, subject_id, title, class_level, location, rate, mode, description) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$tpId, $subjectId, $title, $classLevel, $location, $rate, $mode, $description]);
        setFlash('success', 'Your availability has been advertised.');
        redirect('/tutor/my_posts.php');
    }
    setOld($_POST);
}

$pageTitle = 'Advertise Availability';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="card">
            <h2>Advertise Your Availability</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <label>Title</label>
                <input type="text" name="title" value="<?= old('title') ?>" placeholder="e.g. Physics Tuition Available - SSC/HSC" required>
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
                    <div><label>Class / Level</label><input type="text" name="class_level" value="<?= old('class_level') ?>"></div>
                </div>
                <label>Location</label>
                <input type="text" name="location" value="<?= old('location') ?>">
                <div class="form-row">
                    <div><label>Rate (BDT/month)</label><input type="number" step="0.01" name="rate" value="<?= old('rate','0') ?>"></div>
                    <div>
                        <label>Mode</label>
                        <select name="mode">
                            <option value="both">Both</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>
                <label>Description</label>
                <textarea name="description"><?= old('description') ?></textarea>
                <button type="submit" class="btn btn-primary mt-3">Publish</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; clearOld(); ?>
