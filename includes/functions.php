<?php
/**
 * Shared helper functions used across the application.
 */

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function setOld(array $data): void
{
    $_SESSION['old'] = $data;
}

function clearOld(): void
{
    unset($_SESSION['old']);
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function money(float $amount): string
{
    return 'BDT ' . number_format($amount, 2);
}

/**
 * Insert a notification for a user. Every important state change in the
 * system calls this so the notifications table always reflects real events.
 */
function notify(int $userId, string $type, string $title, string $message, string $url = ''): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO notifications (user_id, type, title, message, related_url) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message, $url]);
}

function logAdminAction(int $adminUserId, string $actionType, string $targetType, ?int $targetId, string $details = ''): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO admin_actions (admin_user_id, action_type, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$adminUserId, $actionType, $targetType, $targetId, $details]);
}

function unreadNotificationCount(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function paginate(int $totalRows, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [$totalPages, $currentPage, $offset];
}
