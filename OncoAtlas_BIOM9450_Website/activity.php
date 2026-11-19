<?php
require_once __DIR__.'/app/layout.php';require_professional();
$me=(int)app_user()['id'];
$rows=query_rows('SELECT AuditID,Action,EntityType,EntityID,CreatedAt FROM AuditLog WHERE UserID=? ORDER BY AuditID DESC LIMIT 80',[$me]);
start_page('Activity log','activity.php','A chronological record of actions performed by your signed-in account.','AUDIT & SECURITY');
?><section class="panel"><div class="panel-title"><div><h2>My recent activity</h2><p>Latest 80 audit records from AuditLog</p></div><span class="chip informative">Account-specific</span></div><div class="table-scroll"><table><thead><tr><th>Audit ID</th><th>Action</th><th>Entity</th><th>Record ID</th><th>Time</th></tr></thead><tbody><?php foreach($rows as $a):?><tr><td class="mono"><?=h($a['AuditID'])?></td><td><span class="code-pill"><?=h($a['Action'])?></span></td><td><?=display($a['EntityType'])?></td><td class="mono"><?=display($a['EntityID'])?></td><td><?=display($a['CreatedAt'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="5">No audit entries available yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php end_page();
