<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) redirect('/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Invalid email or password.';
    } elseif ($user['status'] === 'suspended') {
        $errors[] = 'Your account has been suspended. Please contact support.';
    }

    if (!$errors) {
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'profile_photo' => $user['profile_photo'],
        ];
        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
        if ($user['role'] === 'admin') redirect('/admin/dashboard.php');
        if ($user['role'] === 'tutor') redirect('/tutor/dashboard.php');
        redirect('/student/dashboard.php');
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="card form-narrow">
        <h2>Log in to TutorHub</h2>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <?= csrfField() ?>
            <label>Email Address</label>
            <input type="email" name="email" value="<?= old('email') ?>" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary btn-block mt-3">Log In</button>
        </form>

        <p class="text-muted mt-2">No account yet? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a></p>

        <div class="card mt-3" style="background:var(--bg-navy-light)">
            <strong class="text-gold">Demo Accounts</strong> <span class="text-muted">(password: Passw0rd!)</span>
            <p class="text-muted" style="margin:8px 0 0">
                Student: student@example.com<br>
                Tutor: tutor@example.com<br>
                Admin: admin@example.com
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
