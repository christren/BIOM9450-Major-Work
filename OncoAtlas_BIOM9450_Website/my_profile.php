<?php
require_once __DIR__.'/app/bootstrap.php';require_login();
if(account_role()!=='Patient')redirect('patients.php');
$id=linked_patient_id();
if($id) redirect('patient.php?id='.$id);
require_once __DIR__.'/app/layout.php';start_page('My profile','my_profile.php','Your patient record is not linked to this account.');
empty_state('No linked profile','Ask a project administrator to link a demonstration patient record to your user account.');end_page();
