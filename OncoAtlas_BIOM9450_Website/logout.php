<?php
require_once __DIR__.'/app/bootstrap.php';
if(signed_in()) audit('LOGOUT','AppUser',(string)app_user()['id']);
$_SESSION=[];
if(ini_get('session.use_cookies')) { $p=session_get_cookie_params();setcookie(session_name(),'',time()-3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']); }
if(session_status()===PHP_SESSION_ACTIVE) session_destroy();
redirect('login.php');
