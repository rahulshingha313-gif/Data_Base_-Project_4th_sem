<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$user = currentUser();
$spId = currentStudentProfileId();

if (!$spId) {
    $db->prepare('INSERT INTO student_profiles (user_id) VALUES (?)')->execute([$user['user_id']]);
    $spId = (int) $db->lastInsertId();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $classLevel = trim($_POST['class_level'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $guardianName = trim($_POST['guardian_name'] ?? '');
    $guardianPhone = trim($_POST['guardian_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';

    if (!$errors) {
        $stmt = $db->prepare(
            'UPDATE student_profiles SET class_level=?, institution=?, guardian_name=?, guardian_phone=?, address=?, bio=? WHERE student_profile_id=?'
        );
        $stmt->execute([$classLevel, $institution, $guardianName, $guardianPhone, $address, $bio, $spId]);

        $db->prepare('UPDATE users SET full_name=?, phone=? WHERE user_id=?')
           ->execute([$fullName, $phone, $user['user_id']]);

        refreshSessionUser();
        setFlash('success', 'Profile updated successfully.');
        redirect('/student/profile.php');
    }
}

$profile = $db->prepare('SELECT * FROM student_profiles WHERE student_profile_id = ?');
$profile->execute([$spId]);
$profile = $profile->fetch();

$userRow = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$userRow->execute([$user['user_id']]);
$userRow = $userRow->fetch();

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <div class="card">
            <h2>Student Profile</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="form-row">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?= e($userRow['full_name']) ?>" required>
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= e($userRow['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Class / Level</label>
                        <input type="text" name="class_level" value="<?= e($profile['class_level'] ?? '') ?>" placeholder="e.g. Class 9">
                    </div>
                    <div>
                        <label>Institution</label>
                        <input type="text" name="institution" value="<?= e($profile['institution'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Guardian Name</label>
                        <input type="text" name="guardian_name" value="<?= e($profile['guardian_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Guardian Phone</label>
                        <input type="text" name="guardian_phone" value="<?= e($profile['guardian_phone'] ?? '') ?>">
                    </div>
                </div>
                <label>Address</label>
                <input type="text" name="address" value="<?= e($profile['address'] ?? '') ?>">
                <label>About Me</label>
                <textarea name="bio"><?= e($profile['bio'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary mt-3">Save Profile</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
