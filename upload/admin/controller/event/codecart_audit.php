<?php
/**
 * Records administrator changes (create, edit, delete and similar) in the security audit log.
 * Only the route, the record identifier and the administrator are stored, never submitted form data.
 */
class ControllerEventCodecartAudit extends Controller {
	private static $verbs = array('add', 'edit', 'delete', 'copy', 'install', 'uninstall', 'enable', 'disable', 'approve', 'deny', 'clear', 'restore');

	// Not changes made by the administrator on behalf of the shop, or high-frequency internals.
	private static $skip = array(
		'user/user/editcode',
		'user/user/editpassword',
		'user/api/addip',
		'user/api/addsession',
		'user/api/deletesession',
		'user/user/addlogin',
		'tool/upload/addupload',
		'setting/event',
		'extension/modification/',
		'report/',
		'common/',
		'codecart/'
	);

	// admin/model/*/after
	public function record(&$route, &$args, &$output) {
		$user = $this->registry->get('user');

		if (!$user || !$user->isLogged()) {
			return;
		}

		$parts = explode('/', (string)$route);
		$method = (string)array_pop($parts);
		$model = strtolower(implode('/', $parts));

		if (!$this->isAuditedMethod($method)) {
			return;
		}

		foreach (self::$skip as $prefix) {
			if (strpos($model . '/' . strtolower($method), $prefix) === 0 || strpos($model . '/', $prefix) === 0) {
				return;
			}
		}

		$context = array('model' => $model, 'method' => $method);

		if (isset($args[0]) && is_scalar($args[0]) && !is_bool($args[0])) {
			$context['id'] = (string)$args[0];
		} elseif (is_int($output) || (is_string($output) && ctype_digit($output))) {
			$context['id'] = (string)$output;
		}

		(new \CodeCart\Core\SecurityAudit($this->registry))->add(
			'admin.change.' . str_replace('/', '.', $model) . '.' . strtolower($method),
			'success',
			'info',
			'admin',
			(int)$user->getId(),
			(string)$user->getUserName(),
			$context
		);
	}

	private function isAuditedMethod($method) {
		$method = strtolower((string)$method);

		foreach (self::$verbs as $verb) {
			if (strpos($method, $verb) === 0) {
				return true;
			}
		}

		return false;
	}
}
