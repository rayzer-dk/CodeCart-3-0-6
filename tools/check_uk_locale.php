<?php
$root = realpath(__DIR__ . '/../upload');
if (!$root) { fwrite(STDERR, "upload directory not found\n"); exit(2); }
$allowedParts = array('language','vendor');
$allowedFiles = array(
    'system/library/codecart/migration.php',
    'admin/controller/common/filemanager.php',
    'admin/view/javascript/codecart-admin-ui.js',
    'system/helper/general.php',
    'system/library/codecart/src/RelationLayer.php'
);
$thirdPartyParts = array('summernote/lang','jquery/datetimepicker/moment','font-awesome','bootstrap','codemirror','select2');
$needles = array(
    'Зберегти','Видалити','Додати','Копіювати','Пошук','Переглянути','Оновити','Завантажити','Закрити','Далі','Обрати','Відкрити','Поділитися',
    'Показати','Прибрати','Редагувати','Згорнути','Розгорнути','У кошику','Змінити тему','Відділення','Повідомити','Замовити','Поставити запитання',
    'Умови доставки','Інструкція','Важлива інформація','Таблиця розмірів','Жіночі розміри','Чоловічі розміри'
);
$violations = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, array('php','twig','js'), true)) continue;
    $path = str_replace('\\','/', substr($file->getPathname(), strlen($root) + 1));
    if (in_array($path, $allowedFiles, true)) continue;
    $skip = false;
    foreach ($allowedParts as $part) { if (strpos('/' . $path . '/', '/' . $part . '/') !== false) { $skip = true; break; } }
    if ($skip) continue;
    foreach ($thirdPartyParts as $part) { if (strpos($path, $part) !== false) { $skip = true; break; } }
    if ($skip || substr($path, -7) === '.min.js') continue;
    $lines = @file($file->getPathname());
    if (!is_array($lines)) continue;
    foreach ($lines as $lineNo => $line) {
        $hit = preg_match('/[ІЇЄҐіїєґ]/u', $line);
        if (!$hit) {
            foreach ($needles as $needle) { if (strpos($line, $needle) !== false) { $hit = true; break; } }
        }
        if ($hit) $violations[] = $path . ':' . ($lineNo + 1) . ':' . trim($line);
    }
}
if ($violations) {
    fwrite(STDERR, "Hardcoded Ukrainian UI text found outside uk-ua language files:\n" . implode("\n", $violations) . "\n");
    exit(1);
}
echo "PASS: no hardcoded Ukrainian UI text found in first-party PHP/Twig/JS.\n";
