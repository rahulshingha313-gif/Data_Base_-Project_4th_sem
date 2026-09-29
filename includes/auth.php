<?php
/**
 * Authentication & role-based authorization helpers.
 */

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function hasRole(string $role): bool
{
    $user = currentUser();
    if (!$user) return false;
    if ($user['role'] === 'both') {
        return in_array($role, ['student', 'tutor'], true);
    }
    return $user['role'] === $role;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect('/auth/login.php');
    }
    if (currentUser()['status'] === 'suspended') {
        session_destroy();
        setFlash('error', 'Your account has been suspended. Contact support.');
        redirect('/auth/login.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (!hasRole($role)) {
        setFlash('error', 'You do not have access to that page.');
        redirect('/index.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (currentUser()['role'] !== 'admin') {
        setFlash('error', 'Admin access only.');
        redirect('/index.php');
    }
}

/** Get the student_profile_id for the logged-in user, or null. */
function currentStudentProfileId(): ?int
{
    $user = currentUser();
    if (!$user) return null;
    $stmt = getDB()->prepare('SELECT student_profile_id FROM student_profiles WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $row = $stmt->fetch();
    return $row ? (int) $row['student_profile_id'] : null;
}

/** Get the tutor_profile_id for the logged-in user, or null. */
function currentTutorProfileId(): ?int
{
    $user = currentUser();
    if (!$user) return null;
    $stmt = getDB()->prepare('SELECT tutor_profile_id FROM tutor_profiles WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $row = $stmt->fetch();
    return $row ? (int) $row['tutor_profile_id'] : null;
}

function refreshSessionUser(): void
{
    if (!isLoggedIn()) return;
    $stmt = getDB()->prepare('SELECT user_id, full_name, email, role, status, profile_photo FROM users WHERE user_id = ?');
    $stmt->execute([currentUser()['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['user'] = $row;
    }
}
