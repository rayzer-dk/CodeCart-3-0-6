<?php
namespace CodeCart\Core;

/**
 * Fixed wall-clock schedules in the configured shop timezone.
 * Monday is bit 0; Sunday is bit 6. DB date_next remains in database-local
 * time by adding a UTC-derived delay to SQL NOW(), not writing a PHP local date.
 */
require_once __DIR__ . '/CronExpression.php';
final class SchedulerSchedule {
    public static function normalize(array $input): array {
        $mode = isset($input['schedule_mode']) ? (string)$input['schedule_mode'] : 'interval';
        if (!in_array($mode, array('interval', 'weekly', 'cron'), true)) {
            throw new \InvalidArgumentException('Invalid scheduler mode.');
        }
        if ($mode === 'interval') {
            return array('schedule_mode' => 'interval', 'schedule_days' => 127, 'schedule_time' => '03:00', 'schedule_expression' => '');
        }
        if ($mode === 'cron') {
            $expression = trim((string)($input['schedule_expression'] ?? ''));
            CronExpression::parse($expression);
            return array('schedule_mode' => 'cron', 'schedule_days' => 127, 'schedule_time' => '03:00', 'schedule_expression' => $expression);
        }
        $days = isset($input['schedule_days']) ? $input['schedule_days'] : 0;
        if (!is_int($days) && (!is_string($days) || !preg_match('/^[0-9]{1,3}$/D', $days))) {
            throw new \InvalidArgumentException('Invalid scheduler weekdays.');
        }
        $days = (int)$days;
        if ($days < 1 || $days > 127) {
            throw new \InvalidArgumentException('Choose at least one weekday.');
        }
        $time = isset($input['schedule_time']) ? (string)$input['schedule_time'] : '';
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time)) {
            throw new \InvalidArgumentException('Invalid scheduler time.');
        }
        return array('schedule_mode' => 'weekly', 'schedule_days' => $days, 'schedule_time' => $time, 'schedule_expression' => '');
    }

    public static function timezone($value): string {
        $name = is_string($value) ? trim($value) : '';
        if ($name !== '') {
            try { new \DateTimeZone($name); return $name; }
            catch (\Throwable $e) { /* An invalid store timezone cannot stop Cron. */ }
        }
        return 'UTC';
    }

    /** Seconds until the next selected *local* clock time, strictly after now. */
    public static function nextDelay(array $schedule, string $timezone, \DateTimeImmutable $now = null): int {
        $schedule = self::normalize($schedule);
        if ($schedule['schedule_mode'] === 'cron') {
            return CronExpression::nextDelay($schedule['schedule_expression'], $timezone, $now);
        }
        if ($schedule['schedule_mode'] !== 'weekly') {
            throw new \InvalidArgumentException('Calendar delay requested for interval task.');
        }
        $utc = new \DateTimeZone('UTC');
        $now = $now ? $now->setTimezone($utc) : new \DateTimeImmutable('now', $utc);
        $local = $now->setTimezone(new \DateTimeZone(self::timezone($timezone)));
        list($hour, $minute) = array_map('intval', explode(':', $schedule['schedule_time']));
        $midnight = $local->setTime(0, 0, 0);
        for ($i = 0; $i <= 7; $i++) {
            $day = $midnight->modify('+' . $i . ' days');
            $bit = 1 << ((int)$day->format('N') - 1);
            if (($schedule['schedule_days'] & $bit) === 0) { continue; }
            $candidate = $day->setTime($hour, $minute, 0);
            // Skip wall-clock times that do not exist on a spring DST transition.
            if ($candidate->format('H:i') !== $schedule['schedule_time']) { continue; }
            $seconds = $candidate->getTimestamp() - $now->getTimestamp();
            if ($seconds > 0) { return $seconds; }
        }
        throw new \RuntimeException('No next scheduler date within eight days.');
    }
}
