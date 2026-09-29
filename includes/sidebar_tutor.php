<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="sidebar">
    <a href="<?= BASE_URL ?>/tutor/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= BASE_URL ?>/tutor/profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">My Profile</a>
    <a href="<?= BASE_URL ?>/tutor/my_posts.php" class="<?= $current === 'my_posts.php' ? 'active' : '' ?>">My Tutor Posts</a>
    <a href="<?= BASE_URL ?>/tutor/create_post.php" class="<?= $current === 'create_post.php' ? 'active' : '' ?>">Advertise Availability</a>
    <a href="<?= BASE_URL ?>/student/browse_tuition.php" class="<?= $current === 'browse_tuition.php' ? 'active' : '' ?>">Browse Tuition Posts</a>
    <a href="<?= BASE_URL ?>/tutor/my_applications.php" class="<?= $current === 'my_applications.php' ? 'active' : '' ?>">My Applications</a>
    <a href="<?= BASE_URL ?>/tutor/availability.php" class="<?= $current === 'availability.php' ? 'active' : '' ?>">Set Availability</a>
    <a href="<?= BASE_URL ?>/schedule/index.php?view=tutor" class="<?= $current === 'index.php' && ($_GET['view'] ?? '') === 'tutor' ? 'active' : '' ?>">Schedule</a>
    <a href="<?= BASE_URL ?>/attendance/tutor_view.php" class="<?= $current === 'tutor_view.php' ? 'active' : '' ?>">Attendance</a>
    <a href="<?= BASE_URL ?>/payments/tutor_view.php" class="<?= $current === 'tutor_view.php' ? 'active' : '' ?>">Payments</a>
    <a href="<?= BASE_URL ?>/messages/inbox.php" class="<?= $current === 'inbox.php' ? 'active' : '' ?>">Messages</a>
    <a href="<?= BASE_URL ?>/tutor/reviews.php" class="<?= $current === 'reviews.php' ? 'active' : '' ?>">My Reviews</a>
    <a href="<?= BASE_URL ?>/reports/submit_report.php" class="<?= $current === 'submit_report.php' ? 'active' : '' ?>">Report a Problem</a>
</div>
