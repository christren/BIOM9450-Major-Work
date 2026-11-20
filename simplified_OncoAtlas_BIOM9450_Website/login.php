<?php
// 1. Read the submitted email/password.
// 2. Look up the user with a MySQLi prepared query.
// 3. Check the bcrypt password with password_verify().
// 4. Save the user's ID and role in $_SESSION.
// 5. Redirect to the dashboard.
require_once __DIR__ . '/app/bootstrap.php';

// Already logged in? Go directly to the dashboard.
if (signed_in()) {
    redirect('dashboard.php');
}

$error = '';

// Only process submitted form data when the request uses POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // A question mark is a placeholder: the email is never joined
        // directly into the SQL string.
        $sql = 'SELECT UserID, Name, Email, Password, Role
                FROM AppUser
                WHERE Email = ?
                LIMIT 1';
        $stmt = sql_run($sql, [$email]);
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['Password'])) {
            // Use the same message for an unknown email and wrong password.
            $error = 'Incorrect email or password.';
            audit('LOGIN_FAILED', 'AppUser', null);
        } else {
            // The password is correct. Start a fresh authenticated session.
            session_regenerate_id(true);

            $_SESSION['account'] = [
                'id' => (int)$user['UserID'],
                'name' => $user['Name'],
                'email' => $user['Email'],
                'role' => $user['Role']
            ];

            // Give the new login session a fresh form-protection token.
            unset($_SESSION['csrf_token']);
            csrf_token();

            audit('LOGIN', 'AppUser', (string)$user['UserID']);
            redirect('dashboard.php');
        }
    } catch (Throwable $errorObject) {
        // Do not reveal database passwords or internal SQL errors in HTML.
        error_log('Login error: ' . $errorObject->getMessage());
        $error = 'Unable to sign in. Check MySQL setup and login credentials.';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · OncoAtlas</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<div class="login-grid">
<section class="login-art">
<a href="index.php" class="brand login-brand">
<span class="brand-symbol">✳</span>
<span>
<strong>onco<span>atlas.</span>
</strong>
<small>CANCER GENOMICS</small>
</span>
</a>
<div class="orb orb-one">
</div>
<div class="orb orb-two">
</div>
<div class="login-story">
<div class="story-kicker">
<span class="live-dot">
</span> TCGA PANCANCER ATLAS</div>
<h1>Explore the patterns<br>within <em>cancer</em>
<br>genomics.</h1>
<p>A connected research workspace for clinical records, mutation profiles, genes and cancer cohorts.</p>
<div class="story-metrics">
<div>
<b>06</b>
<span>Cancer cohorts</span>
</div>
<div>
<b>2018</b>
<span>Atlas edition</span>
</div>
<div>
<b>01</b>
<span>Unified portal</span>
</div>
</div>
</div>
<small class="story-bottom">BIOM9450 · Biomedical &amp; Health Informatics</small>
</section>
<section class="login-form-side">
<div class="signin-box">
<span class="signin-mark">✳</span>
<span class="overline">RESEARCH WORKSPACE</span>
<h2>Welcome back</h2>
<p>Enter your account details to sign in.</p>

<?php if($error):?>
<div class="notice error" role="alert">
<?=h($error)?>
</div>

<?php endif;?>
<form method="post" class="form-stack">
<?=csrf_field()?>
<label for="email">Email address</label>
<input id="email" name="email" type="email" maxlength="255" autocomplete="username" placeholder="researcher@example.test" required value="<?=h($_POST['email']??'')?>">
<label for="password">Password</label>
<input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter password" required>
<button class="button primary button-wide" type="submit">Sign in <span>→</span>
</button>
</form>
<div class="sign-divider">DEMONSTRATION ACCESS</div>
<p class="login-help">Local demo accounts: <code>oncologist@example.test</code>, <code>researcher@example.test</code> and <code>patient@example.test</code>. After applying <code>database/demo_password1234.sql</code>, they all use <strong>password1234</strong>.</p>
<p class="login-disclaimer">Educational and exploratory use only. Not intended for clinical decisions.</p>
</div>
</section>
</div>
</body>
</html>
