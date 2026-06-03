<?php
if(!function_exists("env")){function env($k=null,$d=null){return $d;}}
function rows(string $file): array {
    $cfg = require $file; $mod = $cfg['code'] ?? '?'; $out=[];
    foreach (($cfg['submodules'] ?? []) as $s) {
        $sCode=$s['code']??'?'; $sPrio=$s['priority']??999;
        foreach (($s['components'] ?? []) as $c) {
            $acts = implode(', ', array_map(fn($a)=>$a['code']??'?', $c['actions']??[]));
            $out[] = [$sPrio,$mod,$sCode,$c['code']??'?',$c['type']??'page',$c['route']??($s['route']??''),$acts];
        }
        if (empty($s['components'])) $out[]=[$sPrio,$mod,$sCode,'(submodule)','-',$s['route']??'',''];
    }
    usort($out, fn($a,$b)=>($a[0]<=>$b[0]) ?: strcmp($a[2],$b[2]) ?: strcmp($a[3],$b[3]));
    return $out;
}
$idp=1;
function table($file,$title,$prefix,&$idp){
    if(!is_file($file)){return;}
    $rows=rows($file);
    echo "\n## $title — ".count($rows)." components (from `".$file."`)\n\n";
    echo "| ID | Pr | Code (module.sub.component) | Route | Type | Actions | Status |\n";
    echo "|----|----|------------------------------|-------|------|---------|--------|\n";
    foreach($rows as $r){[$p,$m,$s,$c,$t,$rt,$a]=$r; printf("| %s%02d | %s | `%s.%s.%s` | `%s` | %s | %s | ⬜ |\n",$prefix,$idp++,$p,$m,$s,$c,$rt,$t,$a);} 
}
echo "# AEOS365 — Core Admin Test Matrix (generated from config/module.php)\n\n";
echo "> 100% of the foundation + platform module hierarchy, sorted by sub-module priority.\n";
echo "> Each row = one component (page/feature) + its actions to verify. Run live via MCP.\n";
echo "> Status: ⬜ not run · ✅ pass · ❌ fail (log B-) · ⏭ skip. Generated ".date('Y-m-d').".\n";
$i=1; table('packages/aero-core/config/module.php','CA-CORE (tenant-side foundation)','CAT-',$i);
$j=1; table('packages/aero-platform/config/module.php','CA-PLATFORM (landlord/platform side)','CAP-',$j);
