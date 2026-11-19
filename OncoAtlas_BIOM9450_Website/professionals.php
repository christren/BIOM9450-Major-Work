<?php
require_once __DIR__.'/app/layout.php';require_professional();
$people=query_rows('SELECT p.ProfessionalID,u.Name,u.Role,p.Specialty,p.Institution FROM Professional p INNER JOIN AppUser u ON u.UserID=p.UserID ORDER BY u.Name');
audit('VIEW_PROFESSIONALS','Professional',null);
start_page('Research team','professionals.php','The oncologists and researchers configured for this demonstration.','PEOPLE & ACCESS');
?><div class="cohort-grid"><?php foreach($people as $person):?><article class="cohort-card"><div class="cohort-heading"><span class="cohort-icon"><?=icon('users')?></span><span class="code-pill"><?=h($person['Role'])?></span></div><h2><?=h($person['Name'])?></h2><p><?=display($person['Specialty'])?></p><div class="cohort-metrics"><div><small>Institution</small><b class="person-institution"><?=display($person['Institution'])?></b></div></div><div class="footer-note">Demonstration professional account</div></article><?php endforeach;?></div>
<?php end_page();
