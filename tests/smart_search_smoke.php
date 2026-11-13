<?php
/** Storefront search helpers: php tests/smart_search_smoke.php */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SmartSearch.php';

use CodeCart\Core\SmartSearch;

$failures = array();
$check = function ($ok, $name) use (&$failures) { if (!$ok) { $failures[] = $name; } };

// Wrong keyboard layout and Latin spelling.
$check(in_array('привет', SmartSearch::alternatives('ghbdtn'), true), 'Latin keys read as Cyrillic');
$check(in_array('hello', SmartSearch::alternatives('руддщ'), true), 'Cyrillic keys read as Latin');
$check(in_array('телефон', SmartSearch::alternatives('telefon'), true), 'Latin spelling of a Cyrillic word');
$check(in_array('samsung', SmartSearch::alternatives('самсунг'), true) && in_array('nikon', SmartSearch::alternatives('никон'), true), 'Cyrillic spelling of a Latin word');
$check(SmartSearch::alternatives('телефон') !== array() && !in_array('телефон', SmartSearch::alternatives('телефон'), true), 'alternatives never repeat the query');
$check(SmartSearch::alternatives('FTH-062 12 V') === array(), 'a technical query is not re-read');
$check(SmartSearch::alternatives('') === array(), 'empty query');

// Codes written with or without separators.
$check(SmartSearch::compactCode('FTH 062') === 'fth062' && SmartSearch::compactCode('fth-062') === 'fth062' && SmartSearch::compactCode('FTH062') === 'fth062', 'model code with separators');
$check(SmartSearch::compactCode('холодильник') === '' && SmartSearch::compactCode('12345') === '', 'plain words and numbers are not codes');

// Distance: one swapped pair counts once.
$check(SmartSearch::distance('телфеон', 'телефон', 2) === 1, 'swapped letters');
$check(SmartSearch::distance('телефон', 'телефон', 2) === 0, 'same word');
$check(SmartSearch::distance('книга', 'машина', 1) > 1, 'different words stay far apart');

// Correction against the shop vocabulary.
$vocabulary = array('холодильник' => 5, 'телефон' => 9, 'самсунг' => 4, 'галакси' => 3, 'стіл' => 2);
$check(SmartSearch::correct('холодильнк', $vocabulary) === 'холодильник', 'missing letter');
$check(SmartSearch::correct('  телфон  ', $vocabulary) === 'телефон', 'missing letter and surrounding spaces');
$check(SmartSearch::correct('телфеон', $vocabulary) === 'телефон', 'swapped letters');
$check(SmartSearch::correct('самсунг гелакси', $vocabulary) === 'самсунг галакси', 'two words');
$check(SmartSearch::correct('холдильн', $vocabulary) === 'холодильник', 'misspelled and unfinished');
$check(SmartSearch::correct('холодил', $vocabulary) === '', 'an unfinished word is left alone');
$check(SmartSearch::correct('телефон', $vocabulary) === '', 'a known word is left alone');
$check(SmartSearch::correct('FTH062', $vocabulary) === '', 'codes with digits are left alone');
$check(SmartSearch::correct('абвгд', $vocabulary) === '', 'nothing close enough');
$check(SmartSearch::correct('телефон', array()) === '', 'empty vocabulary');

// Vocabulary and statistics hygiene.
$words = SmartSearch::vocabulary(array('Телефон Samsung 128GB', 'телефон чорний'));
$check(isset($words['телефон']) && $words['телефон'] === 2 && isset($words['samsung']) && !isset($words['128gb']), 'vocabulary counts words without digits');
$check(SmartSearch::anonymize('write to a.b@example.com or +380 44 123 45 67') === 'write to [email] or [phone]', 'e-mail and phone are not stored');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . implode('; ', $failures) . "\n");
    exit(1);
}
echo "PASS storefront search helpers\n";
