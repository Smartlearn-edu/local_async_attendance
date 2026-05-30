<?php
/**
 * English strings for local_async_attendance.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Async Attendance Engine';
$string['async_attendance:viewreports'] = 'View async attendance reports';
$string['async_attendance:configure'] = 'Configure async attendance settings';

// Admin settings.
$string['presencethreshold'] = 'Presence Threshold';
$string['presencethreshold_desc'] = 'Minimum number of log events required on a single day for a student to be marked as Present.';
$string['latethreshold'] = 'Late Threshold';
$string['latethreshold_desc'] = 'Minimum number of log events required on a single day for a student to be marked as Late. If events are below this, they are marked Absent.';
$string['scrapewindowdays'] = 'Scrape Window (Days)';
$string['scrapewindowdays_desc'] = 'How many days back in time should the nightly scraper look to catch up on missed logs? Default is 1 (yesterday).';
$string['holidayexceptions'] = 'Holiday Exceptions';
$string['holidayexceptions_desc'] = 'Comma-separated list of dates (YYYY-MM-DD) that are official school holidays. Activity on these days will be compressed into the nearest valid school day.';
$string['compressweekends'] = 'Compress Weekends';
$string['compressweekends_desc'] = 'If enabled, Saturday and Sunday activity is compressed into the adjacent Friday or Monday record to comply with the 194-day weekday Ministry calendar.';
$string['excludedactions'] = 'Excluded Log Actions';
$string['excludedactions_desc'] = 'Comma-separated list of log actions to ignore when counting meaningful engagement (e.g., loggedin, loggedout).';

// Task.
$string['taskscrapedailyattendance'] = 'Scrape daily asynchronous attendance';
