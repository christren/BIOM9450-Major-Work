<?php
require_once __DIR__.'/app/layout.php';require_login();$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    verify_csrf();
    $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
    $account=query_one('SELECT Password FROM AppUser WHERE UserID=?',[app_user()['id']]);
    if(!$account||!password_verify($current,(string)$account['Password']))throw new RuntimeException('Current password is incorrect.');
    if(strlen($new)<12 || strlen($new)>128)throw new RuntimeException('Choose a password containing 12–128 characters.');
    if($new!==$confirm)throw new RuntimeException('New passwords do not match.');
    if(hash_equals($current,$new))throw new RuntimeException('Choose a different password.');
    $hash=password_hash($new,PASSWORD_BCRYPT);
    db()->prepare('UPDATE AppUser SET Password=? WHERE UserID=?')->execute([$hash,app_user()['id']]);
    session_regenerate_id(true);audit('CHANGE_PASSWORD','AppUser',(string)app_user()['id']);flash('Password updated successfully.');redirect('account.php');
  } catch (Throwable $e){$error=$e instanceof PDOException?'Unable to update password.':$e->getMessage();error_log('Change password: '.$e->getMessage());}
}
start_page('My account','account.php','Your current research portal login and password settings.','ACCOUNT SETTINGS');
?><div class="two-column form-layout"><section class="panel"><div class="panel-title"><div><h2>Account details</h2><p>Signed-in identity</p></div></div><dl class="detail-grid"><div><dt>Name</dt><dd><?=h(app_user()['name'])?></dd></div><div><dt>Role</dt><dd><?=h(app_user()['role'])?></dd></div><div class="span-two"><dt>Email</dt><dd><?=h(app_user()['email'])?></dd></div></dl><hr class="light-divider"><div class="panel-title"><div><h2>Change password</h2><p>Passwords are securely hashed using PHP password_hash.</p></div></div><?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?><form method="post" class="form-stack account-form"><?=csrf_field()?><label for="currentPassword">Current password</label><input type="password" autocomplete="current-password" id="currentPassword" name="current_password" required><label for="newPassword">New password (12+ characters)</label><input type="password" id="newPassword" name="new_password" minlength="12" maxlength="128" autocomplete="new-password" required><label for="confirmPassword">Confirm password</label><input type="password" id="confirmPassword" name="confirm_password" minlength="12" maxlength="128" autocomplete="new-password" required><button class="button primary" type="submit">Update password</button></form></section><aside class="panel aside-help"><span class="metric-symbol violet"><?=icon('shield')?></span><h3>Access controls</h3><p>Your login role determines which pages are available. Researchers have access to additional cohort visualisations, while patient accounts can view only their linked research profile and predictions.</p><p>Every password update is recorded in the audit table.</p></aside></div>
<?php end_page();
