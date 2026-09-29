<?php $u = currentUser(); ?>
<div class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= BASE_URL ?>/index.php">Tutor<span>Hub</span></a>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/index.php">Home</a>
            <a href="<?= BASE_URL ?>/student/browse_tuition.php">Find Tuition</a>
            <a href="<?= BASE_URL ?>/tutor/browse_tutors.php">Find Tutors</a>
            <a href="<?= BASE_URL ?>/docs/team.php">Team</a>
            <?php if ($u): ?>
                <?php if ($u['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Panel</a>
                <?php else: ?>
                    <?php if (in_array($u['role'], ['student','both'], true)): ?>
                        <a href="<?= BASE_URL ?>/student/dashboard.php">Student</a>
                    <?php endif; ?>
                    <?php if (in_array($u['role'], ['tutor','both'], true)): ?>
                        <a href="<?= BASE_URL ?>/tutor/dashboard.php">Tutor</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/notifications/index.php">Notifications
                        <?php $c = unreadNotificationCount($u['user_id']); if ($c > 0): ?>
                            <span class="nav-badge"><?= $c ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-nav">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
