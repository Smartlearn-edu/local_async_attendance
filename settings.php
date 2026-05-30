<?php
/**
 * Admin settings for local_async_attendance.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_async_attendance', get_string('pluginname', 'local_async_attendance'));
    $ADMIN->add('localplugins', $settings);

    // Presence Threshold.
    $settings->add(new admin_setting_configtext(
        'local_async_attendance/presence_threshold',
        get_string('presencethreshold', 'local_async_attendance'),
        get_string('presencethreshold_desc', 'local_async_attendance'),
        '3',
        PARAM_INT
    ));

    // Late Threshold.
    $settings->add(new admin_setting_configtext(
        'local_async_attendance/late_threshold',
        get_string('latethreshold', 'local_async_attendance'),
        get_string('latethreshold_desc', 'local_async_attendance'),
        '1',
        PARAM_INT
    ));

    // Scrape Window (Days).
    $settings->add(new admin_setting_configtext(
        'local_async_attendance/scrape_window_days',
        get_string('scrapewindowdays', 'local_async_attendance'),
        get_string('scrapewindowdays_desc', 'local_async_attendance'),
        '1',
        PARAM_INT
    ));

    // Holiday Exceptions.
    $settings->add(new admin_setting_configtextarea(
        'local_async_attendance/holiday_exceptions',
        get_string('holidayexceptions', 'local_async_attendance'),
        get_string('holidayexceptions_desc', 'local_async_attendance'),
        '',
        PARAM_RAW // Must be RAW to allow comma separated dates, will validate in usage.
    ));

    // Compress Weekends.
    $settings->add(new admin_setting_configcheckbox(
        'local_async_attendance/compress_weekends',
        get_string('compressweekends', 'local_async_attendance'),
        get_string('compressweekends_desc', 'local_async_attendance'),
        1
    ));

    // Excluded Log Actions.
    $settings->add(new admin_setting_configtext(
        'local_async_attendance/excluded_actions',
        get_string('excludedactions', 'local_async_attendance'),
        get_string('excludedactions_desc', 'local_async_attendance'),
        'loggedin,loggedout',
        PARAM_TEXT
    ));
}
