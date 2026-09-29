<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();
$tpId = currentTutorProfileId();

if (!$tpId) {
    $db->prepare('INSERT INTO tutor_profiles (user_id) VALUES (?)')->execute([$user['user_id']]);
    $tpId = (int) $db->lastInsertId();
}

$allSubjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $experience = (float) ($_POST['experience_years'] ?? 0);
    $salary = (float) ($_POST['expected_salary'] ?? 0);
    $location = trim($_POST['teaching_location'] ?? '');
    $mode = $_POST['teaching_mode'] ?? 'both';
    $bio = trim($_POST['bio'] ?? '');
    $subjectIds = array_map('intval', $_POST['subjects'] ?? []);

    if ($fullName === '') $errors[] = 'Full name is required.';

    if (!$errors) {
        $db->prepare(
            'UPDATE tutor_profiles SET education=?, institution=?, experience_years=?, expected_salary=?, teaching_location=?, teaching_mode=?, bio=? WHERE tutor_profile_id=?'
        )->execute([$education, $institution, $experience, $salary, $location, $mode, $bio, $tpId]);

        $db->prepare('UPDATE users SET full_name=?, phone=? WHERE user_id=?')->execute([$fullName, $phone, $user['user_id']]);

        // Sync subjects: remove old, insert new
        $db->prepare('DELETE FROM tutor_subjects WHERE tutor_profile_id = ?')->execute([$tpId]);
        $insertSubj = $db->prepare('INSERT IGNORE INTO tutor_subjects (tutor_profile_id, subject_id) VALUES (?, ?)');
        foreach ($subjectIds as $sid) {
            if ($sid > 0) $insertSubj->execute([$tpId, $sid]);
        }

        refreshSessionUser();
        setFlash('success', 'Profile updated successfully.');
        redirect('/tutor/profile.php');
    }
}

$profile = $db->prepare('SELECT * FROM tutor_profiles WHERE tutor_profile_id = ?');
$profile->execute([$tpId]);
$profile = $profile->fetch();

$userRow = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$userRow->execute([$user['user_id']]);
$userRow = $userRow->fetch();

$mySubjectIds = $db->prepare('SELECT subject_id FROM tutor_subjects WHERE tutor_profile_id = ?');
$mySubjectIds->execute([$tpId]);
$mySubjectIds = array_column($mySubjectIds->fetchAll(), 'subject_id');

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="card">
            <h2>Tutor Profile <?php if ($profile['is_profile_verified']): ?><span class="badge badge-active">Verified</span><?php else: ?><span class="badge badge-pending">Unverified</span><?php endif; ?></h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="form-row">
                    <div><label>Full Name</label><input type="text" name="full_name" value="<?= e($userRow['full_name']) ?>" required></div>
                    <div><label>Phone</label><input type="text" name="phone" value="<?= e($userRow['phone'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div><label>Education</label><input type="text" name="education" value="<?= e($profile['education'] ?? '') ?>" placeholder="e.g. BSc in CSE"></div>
                    <div><label>Institution / University</label><input type="text" name="institution" value="<?= e($profile['institution'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div><label>Experience (years)</label><input type="number" step="0.5" name="experience_years" value="<?= e((string)($profile['experience_years'] ?? 0)) ?>"></div>
                    <div><label>Expected Salary (BDT/month)</label><input type="number" step="0.01" name="expected_salary" value="<?= e((string)($profile['expected_salary'] ?? 0)) ?>"></div>
                </div>
                <div class="form-row">
                    <div><label>Teaching Location</label><input type="text" name="teaching_location" value="<?= e($profile['teaching_location'] ?? '') ?>"></div>
                    <div>
                        <label>Teaching Mode</label>
                        <select name="teaching_mode">
                            <?php foreach (['online','offline','both'] as $m): ?>
                                <option value="<?= $m ?>" <?= ($profile['teaching_mode'] ?? '')==$m?'selected':'' ?>><?= ucfirst($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label>Preferred Subjects</label>
                <div class="grid grid-4">
                    <?php foreach ($allSubjects as $s): ?>
                        <label class="checkbox-row"><input type="checkbox" name="subjects[]" value="<?= (int)$s['subject_id'] ?>" <?= in_array($s['subject_id'],$mySubjectIds)?'checked':'' ?>> <?= e($s['subject_name']) ?></label>
                    <?php endforeach; ?>
                </div>
                <label>Bio / Teaching Philosophy</label>
                <textarea name="bio"><?= e($profile['bio'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary mt-3">Save Profile</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
