<?php
namespace Template;
final class Twig {
	private $data = array();

	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render($filename, $code = '') {
		if (!$code) {
			$file = DIR_TEMPLATE . $filename . '.twig';

			if (is_file($file)) {
				$code = file_get_contents($file);
			} else {
				throw new \Exception('Error: Could not load template ' . $file . '!');
				exit();
			}
		}

		// initialize Twig environment
		$config = array(
			'autoescape'  => false,
			'debug'       => false,
			'auto_reload' => true,
			'cache'       => DIR_CACHE . 'template/'
		);

		try {
			// Keep the already-resolved template code as the first source so OCMOD/Event
			// modifications still win, but allow normal Twig includes/extends to resolve
			// from the active template directory. ArrayLoader alone cannot resolve partials.
			$array_loader = new \Twig\Loader\ArrayLoader(array($filename . '.twig' => $code));

			// Catalog routes are resolved by the theme event to names such as
			// default/template/product/product. Includes inside those files use
			// logical names such as common/header.twig and common/codecart_purchase_blocks.twig.
			// Therefore the filesystem loader must also expose the active theme's
			// template/ directory as a root; DIR_TEMPLATE alone points one level too high.
			$template_paths = array();
			if (preg_match('#^([^/]+)/template/#', (string)$filename, $matches)) {
				$theme_root = rtrim(DIR_TEMPLATE, '/\\') . '/' . $matches[1] . '/template';
				if (is_dir($theme_root)) { $template_paths[] = $theme_root; }
				$default_root = rtrim(DIR_TEMPLATE, '/\\') . '/default/template';
				if ($matches[1] !== 'default' && is_dir($default_root)) { $template_paths[] = $default_root; }
			}
			$template_paths[] = DIR_TEMPLATE;
			$file_loader = new \Twig\Loader\FilesystemLoader(array_values(array_unique($template_paths)));
			$loader = new \Twig\Loader\ChainLoader(array($array_loader, $file_loader));

			$twig = new \Twig\Environment($loader, $config);

			return $twig->render($filename . '.twig', $this->data);
		} catch (\Exception $e) {
			trigger_error('Error: Could not load template ' . $filename . '! ' . $e->getMessage());
			exit();
		}	
	}	
}
