<?php
namespace CodeCart\Core;

final class StyleCompiler {
    public function rebuild(): array {
        $result=array('compiled'=>array(),'skipped'=>array(),'errors'=>array());
        if (!class_exists('ScssPhp\\ScssPhp\\Compiler')) {
            $result['errors'][]='scssphp is not available.';
            return $result;
        }
        if (defined('DIR_ADMIN')) {
            $this->compile(DIR_ADMIN . 'view/stylesheet/bootstrap.scss', DIR_ADMIN . 'view/stylesheet/bootstrap.css', DIR_ADMIN . 'view/stylesheet/', $result);
        } elseif (defined('DIR_APPLICATION') && basename(rtrim(DIR_APPLICATION,'/\\')) === 'admin') {
            $this->compile(DIR_APPLICATION . 'view/stylesheet/bootstrap.scss', DIR_APPLICATION . 'view/stylesheet/bootstrap.css', DIR_APPLICATION . 'view/stylesheet/', $result);
        } elseif (defined('DIR_SYSTEM')) {
            $admin = dirname(rtrim(DIR_SYSTEM,'/\\')) . '/admin/';
            if (is_dir($admin)) $this->compile($admin . 'view/stylesheet/bootstrap.scss', $admin . 'view/stylesheet/bootstrap.css', $admin . 'view/stylesheet/', $result);
        }
        return $result;
    }

    private function compile(string $source,string $target,string $importPath,array &$result): void {
        if (!is_file($source)) { $result['skipped'][]=$source; return; }
        try {
            $scss=new \ScssPhp\ScssPhp\Compiler();
            $scss->setLogger(new \ScssPhp\ScssPhp\Logger\QuietLogger());
            $scss->setImportPaths($importPath);
            $css=$scss->compileString('@import "' . basename($source) . '"')->getCss();
            $tmp=$target.'.tmp.'.bin2hex(random_bytes(6));
            if (file_put_contents($tmp,$css,LOCK_EX)===false) throw new \RuntimeException('Unable to write temporary CSS.');
            if (!@rename($tmp,$target)) {
                if (is_file($target) && @unlink($target) && @rename($tmp,$target)) { $result['compiled'][]=$target; return; }
                @unlink($tmp); throw new \RuntimeException('Unable to replace CSS.');
            }
            $result['compiled'][]=$target;
        } catch (\Throwable $e) { $result['errors'][]=$source.': '.$e->getMessage(); }
    }
}
