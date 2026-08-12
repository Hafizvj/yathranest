<?php

function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void
{
    if (!admin_user()) {
        flash_set('error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    $_SESSION['admin'] = [
        'id' => (int) $row['id'],
        'email' => $row['email'],
        'name' => $row['name'],
    ];
    return true;
}

function logout_admin(): void
{
    unset($_SESSION['admin']);
}
