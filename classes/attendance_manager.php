<?php
/**
 * Attendance manager class for local_async_attendance.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_async_attendance;

defined('MOODLE_INTERNAL') || die();

/**
 * Class attendance_manager
 * Handles logic for scraping logs and compressing weekends/holidays.
 */
class attendance_manager {

    /**
     * Get holiday exceptions from admin settings.
     *
     * @return array Array of holiday date strings (YYYY-MM-DD).
     */
    protected static function get_holiday_exceptions(): array {
        $holidays_raw = get_config('local_async_attendance', 'holiday_exceptions');
        if (empty($holidays_raw)) {
            return [];
        }
        $holidays = array_map('trim', explode(',', $holidays_raw));
        $holidays = array_filter($holidays, function($date) {
            return (bool) strtotime($date);
        });
        return $holidays;
    }

    /**
     * Determine the effective attendance date, accounting for weekends and holidays.
     * Compresses to the nearest valid school day if needed.
     *
     * @param int $timestamp The timestamp to check.
     * @return int Timestamp of midnight for the effective attendance date.
     */
    public static function get_effective_attendance_date(int $timestamp): int {
        $compress_weekends = get_config('local_async_attendance', 'compress_weekends');
        $holidays = self::get_holiday_exceptions();

        // Start with midnight of the given timestamp.
        $target_midnight = strtotime('today', $timestamp);
        
        $adjusted = true;
        while ($adjusted) {
            $adjusted = false;
            
            $day_of_week = date('N', $target_midnight); // 1 (Mon) - 7 (Sun)
            $date_str = date('Y-m-d', $target_midnight);
            
            // 1. Weekend compression.
            if ($compress_weekends) {
                if ($day_of_week == 6) {
                    // Saturday -> Friday (-1 day)
                    $target_midnight = strtotime('-1 day', $target_midnight);
                    $adjusted = true;
                    continue;
                } else if ($day_of_week == 7) {
                    // Sunday -> Monday (+1 day)
                    $target_midnight = strtotime('+1 day', $target_midnight);
                    $adjusted = true;
                    continue;
                }
            }

            // 2. Holiday exceptions.
            if (in_array($date_str, $holidays)) {
                // If it's a holiday, roll forward one day.
                // It will be re-evaluated in the next loop iteration in case the next day is a weekend/holiday.
                $target_midnight = strtotime('+1 day', $target_midnight);
                $adjusted = true;
                continue;
            }
        }

        return $target_midnight;
    }

    /**
     * Calculate and store attendance for a specific user and specific day.
     *
     * @param int $userid The user ID.
     * @param int $target_midnight The midnight timestamp of the day to scrape.
     */
    public static function scrape_user_day(int $userid, int $target_midnight) {
        global $DB;

        $end_of_day = $target_midnight + DAYSECS - 1;

        $excluded_actions_raw = get_config('local_async_attendance', 'excluded_actions');
        $excluded_actions = array_map('trim', explode(',', $excluded_actions_raw));
        $excluded_actions = array_filter($excluded_actions);

        $sql = "SELECT COUNT(id) AS event_count, MIN(timecreated) AS first_event, MAX(timecreated) AS last_event
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND timecreated >= :start_time
                   AND timecreated <= :end_time";

        $params = [
            'userid' => $userid,
            'start_time' => $target_midnight,
            'end_time' => $end_of_day,
        ];

        if (!empty($excluded_actions)) {
            list($in_sql, $in_params) = $DB->get_in_or_equal($excluded_actions, SQL_PARAMS_NAMED, 'excl', false);
            $sql .= " AND action $in_sql";
            $params = array_merge($params, $in_params);
        }

        $result = $DB->get_record_sql($sql, $params);

        $event_count = $result ? (int) $result->event_count : 0;
        
        if ($event_count === 0) {
            // No activity, don't create a record unless we are updating an existing to ABSENT?
            // Actually, we should probably record ABSENT to know we checked it.
            $status = 'ABSENT';
            $first_event = null;
            $last_event = null;
        } else {
            $presence_threshold = (int) get_config('local_async_attendance', 'presence_threshold');
            $late_threshold = (int) get_config('local_async_attendance', 'late_threshold');

            if ($event_count >= $presence_threshold) {
                $status = 'PRESENT';
            } else if ($event_count >= $late_threshold) {
                $status = 'LATE';
            } else {
                $status = 'ABSENT';
            }
            $first_event = $result->first_event;
            $last_event = $result->last_event;
        }

        // Determine effective date for compression.
        $effective_date = self::get_effective_attendance_date($target_midnight);

        // Upsert logic.
        $existing = $DB->get_record('local_async_attendance', [
            'userid' => $userid, 
            'attendance_date' => $effective_date
        ]);

        if ($existing) {
            // Update existing (maybe it was Saturday, now it's Sunday compressing into same Friday record).
            // We should add to the total events and expand the time window.
            $update = new \stdClass();
            $update->id = $existing->id;
            
            // Re-evaluate status based on combined events if compressing multiple days?
            // Yes, if compressing Saturday into Friday, we combine their counts.
            $new_total = $existing->total_events + $event_count;
            $update->total_events = $new_total;
            
            $presence_threshold = (int) get_config('local_async_attendance', 'presence_threshold');
            $late_threshold = (int) get_config('local_async_attendance', 'late_threshold');
            
            if ($new_total >= $presence_threshold) {
                $update->status = 'PRESENT';
            } else if ($new_total >= $late_threshold) {
                $update->status = 'LATE';
            } else {
                $update->status = 'ABSENT';
            }

            if ($first_event && (!$existing->first_event_time || $first_event < $existing->first_event_time)) {
                $update->first_event_time = $first_event;
            }
            if ($last_event && (!$existing->last_event_time || $last_event > $existing->last_event_time)) {
                $update->last_event_time = $last_event;
            }
            
            $DB->update_record('local_async_attendance', $update);
        } else {
            // Insert new.
            $newrec = new \stdClass();
            $newrec->userid = $userid;
            $newrec->attendance_date = $effective_date;
            $newrec->status = $status;
            $newrec->total_events = $event_count;
            $newrec->first_event_time = $first_event;
            $newrec->last_event_time = $last_event;
            $newrec->source = 'log_scraper';
            $newrec->timecreated = time();

            $DB->insert_record('local_async_attendance', $newrec);
        }
    }
}
