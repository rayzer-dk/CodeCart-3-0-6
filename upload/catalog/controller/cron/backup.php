<?php
/**
 * Scheduled database backup: writes a verified SQL dump to the private storage backup
 * directory and keeps the newest copies. Task argument: {"keep":7}.
 */
class ControllerCronBackup extends Controller {
    public function index($args = array()) {
        $keep = isset($args['keep']) ? max(1, min(60, (int)$args['keep'])) : 7;
        $dir = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return array('success' => false, 'message' => 'Backup directory is not writable.');
        }

        $backup = new \CodeCart\Core\SqlBackup($this->db);
        $file = $dir . 'codecart_scheduled_' . gmdate('Ymd_His') . '.sql';

        try {
            $tables = array_values(array_diff($backup->tables(), array(DB_PREFIX . 'session', DB_PREFIX . 'api_session')));
            $count = $backup->dump($tables, $file);
            @chmod($file, 0600);

            if (!$backup->verify($file)) {
                throw new \RuntimeException('Backup verification failed.');
            }

            file_put_contents($file . '.sha256', hash_file('sha256', $file) . '  ' . basename($file) . "\n");
            @chmod($file . '.sha256', 0600);
        } catch (\Throwable $e) {
            foreach (array($file, $file . '.sha256') as $partial) {
                if (is_file($partial)) {
                    @unlink($partial);
                }
            }
            $this->log->write('Scheduled backup failed: ' . $e->getMessage());

            return array('success' => false, 'message' => 'Backup failed: ' . $e->getMessage());
        }

        $removed = 0;
        $files = glob($dir . 'codecart_scheduled_*.sql') ?: array();
        rsort($files, SORT_STRING);

        foreach (array_slice($files, $keep) as $old) {
            @unlink($old . '.sha256');
            if (@unlink($old)) {
                $removed++;
            }
        }

        return array(
            'success' => true,
            'message' => 'Backup ' . basename($file) . ': ' . $count . ' tables, ' . round(filesize($file) / 1048576, 2) . ' MB, verified; removed ' . $removed . ' old copies.'
        );
    }
}
