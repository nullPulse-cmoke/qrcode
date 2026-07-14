<?php
require __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard'); exit; }

$err = '';
if ($_POST) {
    if (login($_POST['password'] ?? '')) {
        header('Location: dashboard');
        exit;
    }
    $err = 'Wrong password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - <?=STORE?></title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
<div class="login-box">
  <div class="login-icon">👶</div>
  <h1><?=STORE?></h1>
  <p style="color:#888;margin-bottom:20px;">Product Management</p>
  <?php if ($err): ?><div class="alert alert-error">❌ <?=h($err)?></div><?php endif; ?>
  <form method="post">
    <input type="password" name="password" placeholder="Admin Password" required autofocus class="input">
    <button type="submit" class="btn btn-primary btn-block" style="margin-top:12px;">🔓 Login</button>
  </form>
</div>
</body>
</html>
