<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — HK Builders CRM</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/favicon.svg">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="login-logo-mark">HK</div>
            <div class="login-logo-text">
                <span class="name">HK Builders</span>
                <span class="sub">& Developers</span>
            </div>
        </div>

        <p class="login-tagline">CRM Portal</p>

        <div class="login-divider"><span>Sign in to continue</span></div>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <?= Security::e($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= Security::e($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login" novalidate>
            <?= Security::csrfField() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    value="<?= Security::e($_POST['email'] ?? '') ?>"
                    placeholder="you@hkbuilders.com"
                    required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    placeholder="••••••••"
                    required autocomplete="current-password">
            </div>

            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember_me" value="1">
                    <span>Remember me for 30 days</span>
                </label>
            </div>

            <button type="submit" class="btn-login">
                <span>Sign In</span>
            </button>
        </form>

    </div>
</div>

</body>
</html>
