<?php
namespace CodeCart\Core;

/**
 * Query helpers for the storefront search: wrong keyboard layout, Latin spelling of
 * Cyrillic words, spelling mistakes and unfinished words. Pure functions, no database.
 */
final class SmartSearch {
    private const EN = array('q','w','e','r','t','y','u','i','o','p','[',']','a','s','d','f','g','h','j','k','l',';','\'','z','x','c','v','b','n','m',',','.');
    private const RU = array('й','ц','у','к','е','н','г','ш','щ','з','х','ъ','ф','ы','в','а','п','р','о','л','д','ж','э','я','ч','с','м','и','т','ь','б','ю');
    private const UK = array('й','ц','у','к','е','н','г','ш','щ','з','х',"\u{0457}",'ф',"\u{0456}",'в','а','п','р','о','л','д','ж',"\u{0454}",'я','ч','с','м','и','т','ь','б','ю');

    /** Longer sequences first so "shch" is not read as "s"+"h"+"ch". */
    private const TRANSLIT = array(
        'shch' => 'щ', 'sch' => 'щ', 'zh' => 'ж', 'kh' => 'х', 'ch' => 'ч', 'sh' => 'ш', 'ts' => 'ц', 'yu' => 'ю', 'ya' => 'я',
        'yo' => 'е', 'ye' => "\u{0454}", 'a' => 'а', 'b' => 'б', 'c' => 'к', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г', 'h' => 'х',
        'i' => 'и', 'j' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'q' => 'к', 'r' => 'р',
        's' => 'с', 't' => 'т', 'u' => 'у', 'v' => 'в', 'w' => 'в', 'x' => 'кс', 'y' => 'и', 'z' => 'з',
    );

    private const CYR_TO_LAT = array(
        'щ' => 'shch', 'ж' => 'zh', 'х' => 'kh', 'ч' => 'ch', 'ш' => 'sh', 'ц' => 'ts', 'ю' => 'yu', 'я' => 'ya', "\u{0454}" => 'ye', "\u{0457}" => 'yi',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', "\u{0491}" => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'з' => 'z', 'и' => 'i', "\u{0456}" => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'ы' => 'y', 'э' => 'e', 'ъ' => '', 'ь' => '',
    );

    public static function lower($value) {
        return mb_strtolower((string)$value, 'UTF-8');
    }

    /** Lower case, one space between words, nothing but letters, digits and . - / + inside words. */
    public static function normalize($value) {
        $value = self::lower(html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8'));
        $value = preg_replace('/[^\p{L}\p{N}.\/+-]+/u', ' ', $value);
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * The query typed on the other layout (Latin keys read as Russian or Ukrainian and back)
     * and the same words spelled in the other alphabet (telefon - телефон, самсунг - samsung). Each entry differs from the input.
     */
    public static function alternatives($query) {
        $query = self::normalize($query);
        // Model codes (FTH-062, A12B) are typed exactly; never re-read them as another layout.
        if ($query === '' || preg_match('/\p{L}[\p{L}\d]*\d|\d[\p{L}\d]*\p{L}|\p{L}-\p{N}|\p{N}-\p{L}/u', $query)) {
            return array();
        }
        $latin = (bool)preg_match('/[a-z]/', $query);
        $cyrillic = (bool)preg_match('/[а-яё\x{0456}\x{0457}\x{0454}\x{0491}]/u', $query);
        if ($latin === $cyrillic) {
            return array();
        }
        $out = array();
        $maps = $latin ? array(array_combine(self::EN, self::RU), array_combine(self::EN, self::UK)) : array(array_combine(self::RU, self::EN), array_combine(self::UK, self::EN));
        foreach ($maps as $map) {
            $alt = self::normalize(strtr($query, $map));
            if ($alt !== '' && $alt !== $query) {
                $out[$alt] = $alt;
            }
        }
        $alt = self::normalize(strtr($query, $latin ? self::TRANSLIT : self::CYR_TO_LAT));
        if ($alt !== '' && $alt !== $query) {
            $out[$alt] = $alt;
        }
        return array_values($out);
    }

    /**
     * Words of the query as typed and of each alternative reading (see alternatives()), one list per reading.
     * Words shorter than two characters are dropped; a reading without any word is left out.
     */
    public static function wordGroups($query) {
        $groups = array();
        foreach (array_merge(array((string)$query), self::alternatives($query)) as $reading) {
            $words = array();
            foreach (preg_split('/\s+/u', $reading, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                if (mb_strlen($word, 'UTF-8') >= 2) {
                    $words[] = $word;
                }
            }
            if ($words) {
                $groups[] = $words;
            }
        }
        return $groups;
    }

    /** Only code-like single tokens qualify for an infix identifier lookup. */
    public static function identifierFragment($value) {
        $value = trim((string)$value);
        if (strlen($value) < 3 || strlen($value) > 48
            || !preg_match('/^[a-z0-9][a-z0-9._\\/+\\-]*$/i', $value)
            || !preg_match('/[0-9]/', $value)) {
            return '';
        }
        return strtolower($value);
    }

    /**
     * Unicode search terms shared by the indexer and the query path.
     * Split letter/digit boundaries so 220В also matches a separate 220 attribute.
     * Keep decimal values such as 1.5 and 1,5 equivalent.
     */
    public static function searchTerms($text) {
        $text = self::lower(html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8'));
        $text = preg_replace('/(?<=\p{L})(?=\p{N})|(?<=\p{N})(?=\p{L})/u', ' ', $text);
        preg_match_all('/[\p{L}\p{N}]+(?:[.,]\d+)?/u', $text, $matches);
        $terms = array();
        foreach ($matches[0] as $part) {
            $part = str_replace(',', '.', $part);
            $length = mb_strlen($part, 'UTF-8');
            if ($length >= 2 && $length <= 32) {
                $terms[$part] = $part;
            }
        }
        return array_values($terms);
    }

    /**
     * One equivalence group per line: "насос, помпа, pump".
     * Only single words are accepted to avoid unsafe phrase rewriting.
     */
    public static function parseSynonyms($text) {
        $synonyms = array();
        foreach (array_slice(preg_split('/\r\n|\r|\n/', (string)$text), 0, 150) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $terms = array();
            foreach (array_slice(preg_split('/[,;|=]+/u', $line), 0, 8) as $term) {
                $term = self::normalize($term);
                if (preg_match('/^[\p{L}][\p{L}\p{N}-]{1,31}$/u', $term)) {
                    $terms[$term] = $term;
                }
            }
            if (count($terms) < 2) {
                continue;
            }
            foreach ($terms as $term) {
                if (!isset($synonyms[$term])) {
                    $synonyms[$term] = array();
                }
                foreach ($terms as $equivalent) {
                    $synonyms[$term][$equivalent] = $equivalent;
                }
                $synonyms[$term] = array_slice(array_values($synonyms[$term]), 0, 12);
            }
        }
        return $synonyms;
    }

    /** At most eight single-word alternatives per reading; original always comes first. */
    public static function synonymWordGroups($query, array $synonyms) {
        $words = preg_split('/\s+/u', self::normalize($query), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words || count($words) > 6) {
            return array();
        }
        $groups = array();
        foreach ($words as $i => $word) {
            if (!isset($synonyms[$word])) {
                continue;
            }
            foreach ($synonyms[$word] as $equivalent) {
                if ($equivalent === $word) {
                    continue;
                }
                $variant = $words;
                $variant[$i] = $equivalent;
                $groups[] = $variant;
                if (count($groups) >= 8) {
                    return $groups;
                }
            }
        }
        return $groups;
    }

    /** "FTH 062", "fth-062" and "FTH062" give the same value; empty if the text is not a code. */
    public static function compactCode($value) {
        $value = self::normalize($value);
        if (!preg_match('/\p{L}/u', $value) || !preg_match('/\d/u', $value)) {
            return '';
        }
        if (substr_count($value, ' ') > 1) {
            return '';
        }
        return preg_replace('/[\s.\/+-]+/u', '', $value);
    }

    /** Optimal string alignment distance (a swapped pair of letters counts once); stops above $max. */
    public static function distance($a, $b, $max = 2) {
        $x = preg_split('//u', (string)$a, -1, PREG_SPLIT_NO_EMPTY);
        $y = preg_split('//u', (string)$b, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($x);
        $m = count($y);
        if (abs($n - $m) > $max) {
            return $max + 1;
        }
        $prev2 = array();
        $prev = range(0, $m);
        for ($i = 1; $i <= $n; $i++) {
            $cur = array($i);
            $min = $i;
            for ($j = 1; $j <= $m; $j++) {
                $cost = $x[$i - 1] === $y[$j - 1] ? 0 : 1;
                $v = min($cur[$j - 1] + 1, $prev[$j] + 1, $prev[$j - 1] + $cost);
                if ($i > 1 && $j > 1 && $x[$i - 1] === $y[$j - 2] && $x[$i - 2] === $y[$j - 1]) {
                    $v = min($v, $prev2[$j - 2] + 1);
                }
                $cur[$j] = $v;
                $min = min($min, $v);
            }
            if ($min > $max) {
                return $max + 1;
            }
            $prev2 = $prev;
            $prev = $cur;
        }
        return $prev[$m];
    }

    /**
     * Replaces words that are not in the vocabulary with the closest vocabulary word: one wrong,
     * missing, extra or swapped letter (two for long words), also when the word is cut short as
     * well. $vocabulary maps a lower case word to how often it occurs. Returns '' if nothing changed.
     */
    public static function correct($query, array $vocabulary) {
        $words = preg_split('/\s+/u', self::normalize($query), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words || !$vocabulary) {
            return '';
        }
        $lengths = array();
        foreach ($vocabulary as $candidate => $count) {
            $lengths[(string)$candidate] = mb_strlen((string)$candidate, 'UTF-8');
        }
        $changed = false;
        foreach ($words as &$word) {
            $length = mb_strlen($word, 'UTF-8');
            if ($length < 4 || $length > 32 || preg_match('/\d/u', $word) || isset($vocabulary[$word])) {
                continue;
            }
            // A word that already starts a known word is only unfinished, not misspelled.
            $unfinished = false;
            foreach ($lengths as $candidate => $candidateLength) {
                if ($candidateLength > $length && strncmp((string)$candidate, $word, strlen($word)) === 0) {
                    $unfinished = true;
                    break;
                }
            }
            if ($unfinished) {
                continue;
            }
            $max = $length >= 8 ? 2 : 1;
            $best = '';
            $bestDistance = $max + 1;
            $bestCount = 0;
            foreach ($lengths as $candidate => $candidateLength) {
                $candidate = (string)$candidate;
                $distance = $max + 1;
                if (abs($candidateLength - $length) <= $max) {
                    $distance = self::distance($word, $candidate, $max);
                }
                if ($length >= 5 && $candidateLength > $length + $max) {
                    $distance = min($distance, self::distance($word, mb_substr($candidate, 0, $length, 'UTF-8'), $max));
                }
                $count = (int)$vocabulary[$candidate];
                if ($distance < $bestDistance || ($distance === $bestDistance && $distance <= $max && $count > $bestCount)) {
                    $best = $candidate;
                    $bestDistance = $distance;
                    $bestCount = $count;
                }
            }
            if ($best !== '' && $bestDistance <= $max) {
                $word = $best;
                $changed = true;
            }
        }
        unset($word);
        $result = implode(' ', $words);
        return $changed && $result !== self::normalize($query) ? $result : '';
    }

    /** Words of the given texts with how often each occurs; 3 to 32 letters, no digits. */
    public static function vocabulary(array $texts) {
        $vocabulary = array();
        foreach ($texts as $text) {
            foreach (preg_split('/[^\p{L}]+/u', self::lower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $length = mb_strlen($word, 'UTF-8');
                if ($length >= 3 && $length <= 32) {
                    $vocabulary[$word] = (isset($vocabulary[$word]) ? $vocabulary[$word] : 0) + 1;
                }
            }
        }
        return $vocabulary;
    }

    /** Statistics must not keep e-mail addresses or phone numbers typed into the search box. */
    public static function anonymize($query) {
        $query = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', (string)$query);
        $query = preg_replace('/\+?\d[\d\s\-\(\)]{8,}\d/', '[phone]', $query);
        return mb_substr($query, 0, 190, 'UTF-8');
    }
}
