<?php
namespace CodeCart\Core;

/**
 * Strict five-field Cron with standard DOM/DOW OR semantics.
 * Executes only existing registered handlers; no shell execution.
 */
final class CronExpression {
    private static function number(string $s, int $min, int $max, array $names): int {
        if (isset($names[$s])) { return $names[$s]; }
        if (!preg_match('/^[0-9]{1,2}$/D', $s)) { throw new \InvalidArgumentException('Invalid Cron number.'); }
        $n = (int)$s;
        if ($n < $min || $n > $max) { throw new \InvalidArgumentException('Cron number out of bounds.'); }
        return $n;
    }
    private static function field(string $s, int $min, int $max, array $names = array()): array {
        $set = array();
        foreach (explode(',', strtoupper($s)) as $item) {
            if ($item === '' || !preg_match('/^[A-Z0-9*\-\/]+$/D', $item)) {
                throw new \InvalidArgumentException('Invalid Cron field.');
            }
            $bits = explode('/', $item);
            if (count($bits) > 2 || (count($bits) === 2 && !preg_match('/^[0-9]+$/D', $bits[1]))) {
                throw new \InvalidArgumentException('Invalid Cron step.');
            }
            $step = count($bits) === 2 ? (int)$bits[1] : 1;
            if ($step < 1 || $step > $max - $min + 1) { throw new \InvalidArgumentException('Invalid Cron step.'); }
            $base = $bits[0];
            if ($base === '*') { $a = $min; $b = $max; }
            elseif (strpos($base, '-') !== false) {
                $endpoints = explode('-', $base);
                if (count($endpoints) !== 2) { throw new \InvalidArgumentException('Invalid Cron range.'); }
                $a = self::number($endpoints[0], $min, $max, $names);
                $b = self::number($endpoints[1], $min, $max, $names);
            } else {
                $a = self::number($base, $min, $max, $names);
                $b = count($bits) === 2 ? $max : $a;
            }
            if ($a > $b) { throw new \InvalidArgumentException('Invalid Cron range.'); }
            for ($v = $a; $v <= $b; $v += $step) { $set[$v] = true; }
        }
        if (!$set) { throw new \InvalidArgumentException('Empty Cron field.'); }
        ksort($set, SORT_NUMERIC);
        return array_keys($set);
    }
    public static function parse(string $expression): array {
        $expression = trim($expression);
        if (strlen($expression) > 100 || !preg_match('/^[A-Za-z0-9*,\/\-\s]+$/D', $expression)) {
            throw new \InvalidArgumentException('Invalid Cron expression.');
        }
        $parts = preg_split('/\s+/', $expression);
        if (count($parts) !== 5) { throw new \InvalidArgumentException('Expected five Cron fields.'); }
        $months = array('JAN'=>1,'FEB'=>2,'MAR'=>3,'APR'=>4,'MAY'=>5,'JUN'=>6,'JUL'=>7,'AUG'=>8,'SEP'=>9,'OCT'=>10,'NOV'=>11,'DEC'=>12);
        $days = array('SUN'=>0,'MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6);
        $dow = self::field($parts[4], 0, 7, $days);
        return array(
            'minutes'=>self::field($parts[0], 0, 59),
            'hours'=>self::field($parts[1], 0, 23),
            'dom'=>self::field($parts[2], 1, 31),
            'months'=>self::field($parts[3], 1, 12, $months),
            'dow'=>array_values(array_unique(array_map(function($v){return $v === 7 ? 0 : $v;}, $dow))),
            'dom_any'=>$parts[2] === '*', 'dow_any'=>$parts[4] === '*'
        );
    }
    public static function nextDelay(string $expression, string $timezone, \DateTimeImmutable $now = null): int {
        $f = self::parse($expression);
        $utc = new \DateTimeZone('UTC');
        $now = $now ? $now->setTimezone($utc) : new \DateTimeImmutable('now', $utc);
        $local = new \DateTimeZone(SchedulerSchedule::timezone($timezone));
        $day0 = $now->setTimezone($local)->setTime(0, 0);
        // 5 years includes leap days; test a local wall-clock minute once only.
        for ($offset = 0; $offset < 1830; $offset++) {
            $day = $day0->modify('+' . $offset . ' days');
            if (!in_array((int)$day->format('n'), $f['months'], true)) { continue; }
            $dom = in_array((int)$day->format('j'), $f['dom'], true);
            $dow = in_array((int)$day->format('w'), $f['dow'], true);
            if ($f['dom_any'] ? !$dow : ($f['dow_any'] ? !$dom : (!$dom && !$dow))) { continue; }
            foreach ($f['hours'] as $h) {
                foreach ($f['minutes'] as $m) {
                    $candidate = $day->setTime($h, $m, 0);
                    // During spring DST nonexistent wall times normalize to a different hour.
                    if ((int)$candidate->format('H') !== $h || (int)$candidate->format('i') !== $m) { continue; }
                    $diff = $candidate->getTimestamp() - $now->getTimestamp();
                    if ($diff > 0) { return $diff; }
                }
            }
        }
        throw new \InvalidArgumentException('No Cron event within five years.');
    }
}
