<?php
require_once __DIR__ . '/../config/config.php';

$db = getDB();
$subjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();

$q = trim($_GET['q'] ?? '');
$subjectId = (int) ($_GET['subject_id'] ?? 0);
$location = trim($_GET['location'] ?? '');
$budgetMax = $_GET['budget_max'] ?? '';
$classLevel = trim($_GET['class_level'] ?? '');
$mode = $_GET['mode'] ?? '';
$days = $_GET['days'] ?? '';

$where = ["tp.status = 'open'"];
$params = [];

if ($q !== '') { $where[] = '(tp.title LIKE ? OR tp.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($subjectId) { $where[] = 'tp.subject_id = ?'; $params[] = $subjectId; }
if ($location !== '') { $where[] = 'tp.location LIKE ?'; $params[] = "%$location%"; }
if ($budgetMax !== '') { $where[] = 'tp.budget_min <= ?'; $params[] = (float)$budgetMax; }
if ($classLevel !== '') { $where[] = 'tp.class_level LIKE ?'; $params[] = "%$classLevel%"; }
if ($mode !== '') { $where[] = "(tp.mode = ? OR tp.mode = 'both')"; $params[] = $mode; }
if ($days !== '') { $where[] = 'tp.preferred_days LIKE ?'; $params[] = "%$days%"; }

$whereSql = implode(' AND ', $where);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$total = $db->prepare("SELECT COUNT(*) FROM tuition_posts tp WHERE $whereSql");
$total->execute($params);
$total = (int) $total->fetchColumn();
[$totalPages, $page, $offset] = paginate($total, $perPage, $page);

$sql = "SELECT tp.*, s.subject_name, sp.class_level AS student_class, u.full_name AS student_name
        FROM tuition_posts tp
        JOIN subjects s ON s.subject_id = tp.subject_id
        JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
        JOIN users u ON u.user_id = sp.user_id
        WHERE $whereSql ORDER BY tp.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$pageTitle = 'Browse Tuition Posts';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <h2>Tuition Marketplace</h2>
    <div class="card">
        <form method="GET" action="" class="filter-bar">
            <div><label>Keyword</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Search title/description"></div>
            <div><label>Subject</label>
                <select name="subject_id">
                    <option value="0">Any</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['subject_id'] ?>" <?= $subjectId==$s['subject_id']?'selected':'' ?>><?= e($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Location</label><input type="text" name="location" value="<?= e($location) ?>"></div>
            <div><label>Class</label><input type="text" name="class_level" value="<?= e($classLevel) ?>"></div>
            <div><label>Max Budget</label><input type="number" name="budget_max" value="<?= e((string)$budgetMax) ?>"></div>
            <div><label>Mode</label>
                <select name="mode">
                    <option value="">Any</option>
                    <option value="online" <?= $mode=='online'?'selected':'' ?>>Online</option>
                    <option value="offline" <?= $mode=='offline'?'selected':'' ?>>Offline</option>
                </select>
            </div>
            <div><label>Day</label>
                <select name="days">
                    <option value="">Any</option>
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                        <option value="<?= $d ?>" <?= $days==$d?'selected':'' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" class="btn btn-primary btn-block">Search</button></div>
        </form>
    </div>

    <p class="text-muted mt-2"><?= $total ?> tuition post(s) found.</p>

    <div class="grid grid-3">
        <?php foreach ($posts as $p): ?>
            <div class="card">
                <span class="badge badge-open">open</span>
                <h3 class="mt-1"><?= e($p['title']) ?></h3>
                <p class="text-muted"><?= e($p['subject_name']) ?> &middot; <?= e($p['class_level']) ?></p>
                <p class="text-muted"><?= e($p['location']) ?: 'Location flexible' ?></p>
                <p><?= money($p['budget_min']) ?> - <?= money($p['budget_max']) ?> /month</p>
                <p class="text-muted">Mode: <?= ucfirst(e($p['mode'])) ?> &middot; Days: <?= e($p['preferred_days'] ?: 'Flexible') ?></p>
                <a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$p['tuition_post_id'] ?>" class="btn btn-outline btn-sm mt-1">View &amp; Apply</a>
            </div>
        <?php endforeach; ?>
        <?php if (!$posts): ?><p class="empty-state">No tuition posts match your filters.</p><?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): $qs = $_GET; $qs['page'] = $i; ?>
            <a href="?<?= http_build_query($qs) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
