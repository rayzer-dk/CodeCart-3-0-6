<?php
/**
 * Standalone multilingual notification smoke test.
 * Run: php tools/qa_notification_diagnostics.php
 * No OpenCart bootstrap, database, network, or files are modified.
 */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SystemNotification.php';

$locales = array(
    'en-gb' => 'Cannot run controller /cron. Check the scheduled task route.',
    'ru-ru' => 'Не удалось вызвать контроллер /cron. Проверьте маршрут задачи в планировщике.',
    'uk-ua' => 'Не вдалося викликати контролер /cron. Перевірте маршрут завдання в планувальнику.'
);
$referenceKeys = null;
foreach ($locales as $locale => $expected) {
    $diagnosticsFile = dirname(__DIR__) . '/upload/admin/language/' . $locale . '/tool/diagnostics.php';
    $_ = array();
    include $diagnosticsFile;
    $translations = $_;
    if (!is_array($translations) || count($translations) < 15) {
        throw new RuntimeException('Missing diagnostic translations: ' . $locale);
    }
    $keys = array_keys($translations);
    sort($keys);
    if ($referenceKeys !== null && $keys !== $referenceKeys) {
        throw new RuntimeException('Translation keys differ between languages: ' . $locale);
    }
    $referenceKeys = $keys;

    $language = new class($translations) {
        private $values;
        public function __construct(array $values) { $this->values = $values; }
        public function get($key) { return isset($this->values[$key]) ? $this->values[$key] : $key; }
    };

    $actual = \CodeCart\Core\SystemNotification::localizeDiagnostic(
        'Exception: Error: Could not call /cron!', $language
    );
    if ($actual !== $expected) {
        throw new RuntimeException('Cron controller translation failed (' . $locale . '): ' . $actual);
    }
    foreach (array(
        'Handler returned false',
        'Task failed without details',
        'Scheduler is already running.',
        'OK',
        'recovered_stale_processing',
        'stale_processing_attempt_limit'
    ) as $raw) {
        $translated = \CodeCart\Core\SystemNotification::localizeDiagnostic($raw, $language);
        if ($translated === $raw || $translated === '') {
            throw new RuntimeException('Untranslated diagnostic (' . $locale . '): ' . $raw);
        }
    }
    $unknown = 'Vendor\Package\Failure: error trace-id=ABC-123';
    $translated = \CodeCart\Core\SystemNotification::localizeDiagnostic($unknown, $language);
    if (strpos($translated, $unknown) === false || $translated === $unknown) {
        throw new RuntimeException('Unknown technical details were lost: ' . $locale);
    }
    foreach (array('notification', 'scheduler') as $part) {
        $_ = array();
        include dirname(__DIR__) . '/upload/admin/language/' . $locale . '/tool/' . $part . '.php';
        if (!is_array($_) || !$_) {
            throw new RuntimeException('Missing admin translations: ' . $part . ', ' . $locale);
        }
    }
    echo 'PASS ' . $locale . PHP_EOL;
}
echo 'PASS: Stored Cron diagnostics localize without changing raw details.' . PHP_EOL;
