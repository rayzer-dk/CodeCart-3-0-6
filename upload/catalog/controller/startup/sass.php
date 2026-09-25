<?php
class ControllerStartupSass extends Controller {
	public function index() {
		$files = glob(DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/*.scss');

		if ($files) {
			foreach ($files as $file) {
				// Get the filename
				$filename = basename($file, '.scss');

				$stylesheet = DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/' . $filename . '.css';

				if (!is_file($stylesheet) || $this->config->get('developer_sass')) {
					$scss = new \ScssPhp\ScssPhp\Compiler();
					$scss->setLogger(new \ScssPhp\ScssPhp\Logger\QuietLogger());
					$scss->setImportPaths(DIR_APPLICATION . 'view/theme/' . $this->config->get('config_theme') . '/stylesheet/');

					$output = $scss->compileString('@import "' . $filename . '.scss"')->getCss();
					$temp = $stylesheet . '.tmp.' . bin2hex(random_bytes(6));
					$written = file_put_contents($temp, $output, LOCK_EX);

					if ($written === false || $written !== strlen($output)) {
						if (is_file($temp)) {
							unlink($temp);
						}

						throw new \RuntimeException('Unable to write compiled SASS stylesheet: ' . $stylesheet);
					}

					if (!rename($temp, $stylesheet)) {
						// Windows cannot atomically replace an existing file with rename().
						if (is_file($stylesheet) && unlink($stylesheet) && rename($temp, $stylesheet)) {
							continue;
						}

						if (is_file($temp)) {
							unlink($temp);
						}

						throw new \RuntimeException('Unable to replace compiled SASS stylesheet: ' . $stylesheet);
					}
				}
			}
		}
	}
}
