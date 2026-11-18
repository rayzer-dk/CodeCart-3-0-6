<?php
/** Calendar scheduler contract: php tests/scheduler_schedule_smoke.php */
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SchedulerSchedule.php';
use CodeCart\Core\SchedulerSchedule as Schedule;
function expectCalendar($yes, $message) {
    if (!$yes) { throw new RuntimeException($message); }
}
$zone = 'Europe/Copenhagen';
$days = Schedule::normalize(array('schedule_mode'=>'weekly','schedule_days'=>1,'schedule_time'=>'03:00'));
$utc = new DateTimeZone('UTC');
$at = new DateTimeImmutable('2026-11-18 20:00:00', $utc);
$delay = Schedule::nextDelay($days, $zone, $at);
$next = $at->modify('+' . $delay . ' seconds')->setTimezone(new DateTimeZone($zone));
expectCalendar($next->format('Y-m-d H:i') === '2026-11-23 03:00', 'Monday after Wednesday in store timezone');
$daily = Schedule::normalize(array('schedule_mode'=>'weekly','schedule_days'=>127,'schedule_time'=>'02:30'));
$beforeSpring = new DateTimeImmutable('2026-03-28 23:55:00', $utc);
$afterSpring = $beforeSpring->modify('+' . Schedule::nextDelay($daily, $zone, $beforeSpring) . ' seconds')->setTimezone(new DateTimeZone($zone));
expectCalendar($afterSpring->format('Y-m-d H:i') === '2026-03-30 02:30', 'Nonexistent spring clock time must be skipped');
$beforeAutumn = new DateTimeImmutable('2026-10-24 23:55:00', $utc);
$afterAutumn = $beforeAutumn->modify('+' . Schedule::nextDelay($daily, $zone, $beforeAutumn) . ' seconds')->setTimezone(new DateTimeZone($zone));
expectCalendar($afterAutumn->format('Y-m-d') === '2026-10-25' && $afterAutumn->format('H:i') === '02:30', 'Fall overlap local clock');
$afterFirst = $afterAutumn->setTimezone($utc)->modify('+1 second');
$following = $afterFirst->modify('+' . Schedule::nextDelay($daily, $zone, $afterFirst) . ' seconds')->setTimezone(new DateTimeZone($zone));
expectCalendar($following->format('Y-m-d H:i') === '2026-10-26 02:30', 'Fall overlap must not repeat the same local day');
expectCalendar(Schedule::timezone('Not/Valid') === 'UTC', 'Invalid store timezone fallback');
expectCalendar(Schedule::normalize(array())['schedule_mode'] === 'interval', 'Historical tasks default to interval');
foreach (array(
    array('schedule_mode'=>'weekly','schedule_days'=>0,'schedule_time'=>'03:00'),
    array('schedule_mode'=>'weekly','schedule_days'=>128,'schedule_time'=>'03:00'),
    array('schedule_mode'=>'weekly','schedule_days'=>1,'schedule_time'=>'25:02'),
    array('schedule_mode'=>'weekly','schedule_days'=>1,'schedule_time'=>'03:2'),
    array('schedule_mode'=>'invalid')
) as $invalid) {
    try { Schedule::normalize($invalid); throw new RuntimeException('Invalid calendar input accepted'); }
    catch (InvalidArgumentException $expected) { }
}
$root = dirname(__DIR__) . '/upload/';
$sql = file_get_contents($root . 'install/opencart.sql');
$migrate = file_get_contents($root . 'system/library/codecart/migration.php');
foreach (array('schedule_mode','schedule_days','schedule_time') as $column) {
    expectCalendar(strpos($sql, '`' . $column . '`') !== false, 'Missing clean install scheduler column ' . $column);
    expectCalendar(strpos($migrate, "'" . $column . "' =>") !== false, 'Missing scheduler migration: ' . $column);
}
expectCalendar(strpos($migrate, "const PRESENTATION_SCHEMA_VERSION = '43'") !== false, 'Scheduler calendar migration revision');
echo "PASS: weekly/daily, DST, validation, schema compatibility\n";
