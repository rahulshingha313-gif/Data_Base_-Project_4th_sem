<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) redirect('/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $role     = $_POST['role'] ?? 'student';
    $gender   = $_POST['gender'] ?? null;
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || strlen($fullName) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!in_array($role, ['student','tutor','both'], true)) $errors[] = 'Please choose a valid role.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $db = getDB();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO users (full_name, email, password_hash, phone, role, gender) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $email, $hash, $phone, $role, $gender ?: null]);
        $userId = (int) $db->lastInsertId();

        // Automatically create the matching profile row(s) so the role
        // immediately has somewhere to store profile data.
        if (in_array($role, ['student','both'], true)) {
            $db->prepare('INSERT INTO student_profiles (user_id) VALUES (?)')->execute([$userId]);
        }
        if (in_array($role, ['tutor','both'], true)) {
            $db->prepare('INSERT INTO tutor_profiles (user_id) VALUES (?)')->execute([$userId]);
        }

        notify($userId, 'welcome', 'Welcome to TutorHub', 'Your account was created successfully. Complete your profile to get started.', '/index.php');

        setFlash('success', 'Account created successfully! Please log in.');
        redirect('/auth/login.php');
    }

    setOld($_POST);
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="card form-narrow">
        <h2>Create your TutorHub account</h2>
        <p class="text-muted">Join as a student, a tutor, or both.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <?= csrfField() ?>
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= old('full_name') ?>" required>

            <label>Email Address</label>
            <input type="email" name="email" value="<?= old('email') ?>" required>

            <label>Phone Number</label>
            <input type="text" name="phone" value="<?= old('phone') ?>">

            <label>Gender</label>
            <select name="gender">
                <option value="">Prefer not to say</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>

            <label>I want to register as</label>
            <select name="role" required>
                <option value="student">Student (looking for a tutor)</option>
                <option value="tutor">Tutor (offering tuition)</option>
                <option value="both">Both Student and Tutor</option>
            </select>

            <label>Password</label>
            <input type="password" name="password" required minlength="6">

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required minlength="6">

            <button type="submit" class="btn btn-primary btn-block mt-3">Create Account</button>
        </form>
        <p class="text-muted mt-2">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; clearOld(); ?>
