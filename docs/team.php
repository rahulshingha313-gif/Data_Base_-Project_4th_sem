<?php
require_once __DIR__ . '/../config/config.php';

$team = [
    ['name' => 'Rahul', 'role' => 'Team Lead / Backend Developer', 'desc' => 'Designed the database schema and implemented core PHP/PDO backend logic.', 'photo' => 'rahul.jpg'],
    ['name' => 'Ador', 'role' => 'Frontend Developer', 'desc' => 'Built the UI theme, responsive layouts, and dashboard components.', 'photo' => 'ador.jpg'],
    ['name' => 'Chaity', 'role' => 'Database Designer', 'desc' => 'Modeled table relationships, constraints, and wrote the SQL migration.', 'photo' => 'chaity.jpg'],
    ['name' => 'Rudra', 'role' => 'QA / Documentation', 'desc' => 'Tested the full workflow and authored setup and testing documentation.', 'photo' => 'rudra.jpg'],
];

$pageTitle = 'Our Team';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <div style="text-align:center; margin-bottom:30px;">
        <h2>Meet the TutorHub Team</h2>
        <p class="text-muted">A university Database Management System (DBMS) project.</p>
    </div>
    <div class="grid grid-4">
        <?php foreach ($team as $m): ?>
        <div class="card" style="text-align:center;">
            <div class="team-photo" style="overflow:hidden;">
                <?php
                $photoPath = __DIR__ . '/../assets/images/' . $m['photo'];
                if (file_exists($photoPath)):
                ?>
                    <img src="<?= BASE_URL ?>/assets/images/<?= e($m['photo']) ?>" alt="<?= e($m['name']) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                <?php else: ?>
                    <?= strtoupper(substr($m['name'],0,1)) ?>
                <?php endif; ?>
            </div>
            <h3><?= e($m['name']) ?></h3>
            <p class="text-gold"><?= e($m['role']) ?></p>
            <p class="text-muted"><?= e($m['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>