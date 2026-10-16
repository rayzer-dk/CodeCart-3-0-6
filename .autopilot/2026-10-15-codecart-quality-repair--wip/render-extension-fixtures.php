<?php
$root = dirname(__DIR__, 2);
require $root . '/upload/system/storage/vendor/autoload.php';
$twig = new Twig\Environment(new Twig\Loader\FilesystemLoader($root . '/upload/admin/view/template'), ['cache' => false, 'autoescape' => false]);
$out = __DIR__ . '/evidence/browser';
if (!is_dir($out)) { mkdir($out, 0755, true); }
$types = ['advertise','analytics','captcha','currency','dashboard','feed','fraud','menu','module','payment','report','shipping','theme','total'];
$header = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/upload/admin/"><link rel="stylesheet" href="view/stylesheet/bootstrap.css"><link rel="stylesheet" href="view/javascript/font-awesome/css/font-awesome.min.css"><link rel="stylesheet" href="view/stylesheet/stylesheet.css"><script src="view/javascript/jquery/jquery-3.7.1.min.js"></script></head><body><div id="container">';
$context = ['header' => $header, 'column_left' => '<aside id="column-left"></aside>', 'footer' => '</div></body></html>', 'heading_title'=>'Extensions', 'categories'=>[], 'breadcrumbs'=>[], 'text_type'=>'Type'];
$parent = $twig->render('marketplace/extension.twig', $context);
foreach ($types as $type) {
    $_ = [];
    include $root . '/upload/admin/language/en-gb/en-gb.php';
    include $root . '/upload/admin/language/en-gb/marketplace/extension.php';
    include $root . '/upload/admin/language/en-gb/extension/extension/' . $type . '.php';
    $data = $_ + ['extensions'=>[], 'promotion'=>'', 'hiden'=>[], 'error_warning'=>'', 'success'=>''];
    foreach ([['zeta','Zeta',1,'2026-01-01','2026-02-01'], ['alpha','Alpha',0,'2026-03-01','2026-04-01'], ['beta','Beta',1,'','']] as [$code,$name,$installed,$uploaded,$date]) {
        $data['extensions'][] = ['code'=>$code,'name'=>$name,'status'=>$installed?'Enabled':'Disabled','status_sort'=>$installed,'installed'=>$installed,'date_uploaded'=>$uploaded,'date_installed'=>$date,'date_uploaded_label'=>$uploaded,'date_installed_label'=>$date,'install'=>'#','uninstall'=>'#','edit'=>'#','module'=>[['name'=>$name.' child','status'=>'Enabled','edit'=>'#','delete'=>'#']]];
    }
    $table = $twig->render('extension/extension/' . $type . '.twig', $data);
    file_put_contents($out . '/' . $type . '.html', str_replace('<div id="extension"></div>', '<div id="extension">'.$table.'</div>', $parent));
}
echo 'Rendered '.count($types).' native extension fixtures with native CSS/JS, no store/database.', PHP_EOL;
