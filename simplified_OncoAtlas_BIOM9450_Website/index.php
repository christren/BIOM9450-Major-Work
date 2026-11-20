<?php 
require_once __DIR__.'/app/bootstrap.php';
redirect(signed_in()?'dashboard.php':'login.php');
