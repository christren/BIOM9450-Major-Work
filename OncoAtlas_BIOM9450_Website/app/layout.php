<?php
require_once __DIR__.'/bootstrap.php';
function icon(string $name): string {
    $paths = [
      'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1.4"/><rect x="14" y="3" width="7" height="7" rx="1.4"/><rect x="3" y="14" width="7" height="7" rx="1.4"/><rect x="14" y="14" width="7" height="7" rx="1.4"/>',
      'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.8M16 3.2a4 4 0 0 1 0 7.6"/>',
      'dna'=>'<path d="M3 6c6 0 12 12 18 12M3 18C9 18 15 6 21 6M6 8l4 8m4-8 4 8"/>',
      'chart'=>'<path d="M3 3v18h18M7 17v-5m5 5V8m5 9V5"/>',
      'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
      'upload'=>'<path d="M12 16V3m0 0-5 5m5-5 5 5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
      'shield'=>'<path d="m12 22 8-4V6l-8-4-8 4v12zM9 12l2 2 4-4"/>',
      'log'=>'<path d="M3 12a9 9 0 1 0 3-7M3 4v6h6M12 7v5l3 2"/>',
      'layers'=>'<path d="m12 2 9 5-9 5-9-5 9-5zm-9 9 9 5 9-5M3 16l9 5 9-5"/>',
      'spark'=>'<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5z"/>',
      'logout'=>'<path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>',
      'plus'=>'<path d="M12 5v14M5 12h14"/>',
      'arrow'=>'<path d="M4 12h16m-7-7 7 7-7 7"/>',
      'check'=>'<path d="m5 12 4 4L19 6"/>',
      'chevron'=>'<path d="m9 18 6-6-6-6"/>',
      'filter'=>'<path d="M4 7h16M7 12h10m-7 5h4"/>',
    ];
    return '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['grid']).'</svg>';
}
function start_page(string $title, string $active, string $description='', string $overline='RESEARCH WORKSPACE'): void {
    require_login();
    $user=app_user();
    $nav=[
        ['dashboard.php','Overview','grid'],
        ['patients.php','Patients','users'],
        ['mutations.php','Mutation explorer','dna'],
        ['genes.php','Gene explorer','search'],
        ['cohorts.php','Cancer cohorts','layers'],
        ['professionals.php','Research team','users'],
        ['analytics.php','Analytics','chart'],
        ['upload.php','Import mutations','upload'],
        ['predictions.php','Predictions','spark'],
        ['activity.php','Activity log','log'],
        ['account.php','My account','shield'],
    ];
    if (account_role()==='Patient') {
        $nav=[['dashboard.php','Overview','grid'],['my_profile.php','My profile','users'],['predictions.php','Predictions','spark'],['account.php','My account','shield']];
    } elseif (!is_researcher()) {
        $nav=array_values(array_filter($nav,fn($x)=>$x[0]!=='analytics.php'));
    }
    $flash=flash_take();
    ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d1630"><title><?=h($title)?> · OncoAtlas</title><link rel="stylesheet" href="assets/css/style.css"><script src="assets/js/app.js" defer></script></head>
<body><div class="shell">
<aside class="sidebar" id="sidebar">
  <a class="brand" href="dashboard.php"><span class="brand-symbol"><?=icon('dna')?></span><span><strong>onco<span>atlas.</span></strong><small>CANCER GENOMICS</small></span></a>
  <div class="nav-caption">WORKSPACE</div>
  <nav aria-label="Main navigation" class="nav-list">
  <?php foreach ($nav as $item): ?><a class="nav-link <?= $active===$item[0]?'selected':'' ?>" href="<?=h($item[0])?>"><?=icon($item[2])?><span><?=h($item[1])?></span><?php if($active===$item[0]):?><span class="nav-dot"></span><?php endif;?></a><?php endforeach;?></nav>
  <div class="sidebar-end"><div class="sidebar-note"><span class="live-dot"></span><div><strong>Research demo</strong><small>TCGA · PanCancer Atlas</small></div></div><a href="logout.php" class="nav-link"><?=icon('logout')?>Sign out</a></div>
</aside>
<div class="main-column"><header class="topbar"><button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button><span class="breadcrumb">Research workspace <span>/</span> <b><?=h($title)?></b></span><div class="user-identity"><span class="research-chip"><span class="live-dot"></span> Academic prototype</span><span class="avatar"><?=h(strtoupper(substr((string)$user['name'],0,1)))?></span><div class="user-meta"><b><?=h($user['name'])?></b><small><?=h($user['role'])?></small></div></div></header>
<main class="content"><header class="page-heading"><div><div class="overline"><?=h($overline)?></div><h1><?=h($title)?></h1><?php if($description!==''):?><p><?=h($description)?></p><?php endif;?></div></header>
<?php if($flash):?><div class="notice <?=h($flash['type'])?>" role="status"><?=h($flash['text'])?></div><?php endif; ?>
<?php }
function end_page(): void { ?>
</main><footer class="site-footer"><span>OncoAtlas · BIOM9450 academic project</span><span>Research data only · Not for clinical diagnosis</span></footer></div></div></body></html>
<?php }
function empty_state(string $headline, string $message): void { ?><div class="empty-state"><span class="empty-icon"><?=icon('layers')?></span><strong><?=h($headline)?></strong><p><?=h($message)?></p></div><?php }
function cohort_options(?string $selected=''): void {
    foreach (query_rows('SELECT CancerTypeID,StudyCode,DiagnosisLabel FROM CancerType ORDER BY CancerTypeID') as $c) {
        echo '<option value="'.h($c['CancerTypeID']).'"'.((string)$c['CancerTypeID']===(string)$selected?' selected':'').'>'.h(strtoupper($c['StudyCode']).' · '.$c['DiagnosisLabel']).'</option>';
    }
}
