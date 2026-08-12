<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (admin_user()) {
    redirect('admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $error = 'Invalid session. Please try again.';
    } else {
        $email = post('email');
        $password = post('password');
        if ($email === '' || $password === '') {
            $error = 'Email and password are required.';
        } elseif (attempt_login($email, $password)) {
            redirect('admin/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login | YathraNest</title>
  <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>" />
</head>
<body class="login-page">
  <div class="login-card">
    <h1>YathraNest Admin</h1>
    <p>Sign in to manage packages, inquiries and site content.</p>
    <?php if ($error): ?>
      <div class="admin-alert admin-alert--err"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" required autocomplete="username" value="<?= e(post('email')) ?>" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password" />
      </div>
      <button class="btn btn--primary" type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
