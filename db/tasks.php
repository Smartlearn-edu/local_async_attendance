<?php
/**
 * Tasks definition for the local_async_attendance plugin.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_async_attendance\task\scrape_daily_attendance',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
);
