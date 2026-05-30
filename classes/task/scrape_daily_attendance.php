<?php
/**
 * Scheduled task to scrape daily async attendance.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_async_attendance\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_async_attendance\attendance_manager;

/**
 * Class scrape_daily_attendance
 * Scrapes standard logs for active students and populates attendance table.
 */
class scrape_daily_attendance extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskscrapedailyattendance', 'local_async_attendance');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace("Starting local_async_attendance nightly scrape...");

        $window_days = (int) get_config('local_async_attendance', 'scrape_window_days');
        if ($window_days < 1) {
            $window_days = 1;
        }

        // Get all active users with the student archetype role.
        // We find all role assignments for roles that have archetype 'student'.
        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND r.archetype = 'student'";
                   
        $students = $DB->get_records_sql($sql);
        
        if (empty($students)) {
            mtrace("No active students found. Aborting.");
            return;
        }

        mtrace("Found " . count($students) . " active students. Scrape window: {$window_days} days.");

        $now = time();

        foreach ($students as $student) {
            for ($i = $window_days; $i >= 0; $i--) {
                // E.g., if window = 1, it scrapes exactly 1 day ago (yesterday).
                $target_timestamp = strtotime("-{$i} days", $now);
                $target_midnight = strtotime('today', $target_timestamp);

                attendance_manager::scrape_user_day($student->id, $target_midnight);
            }
        }

        mtrace("Finished local_async_attendance nightly scrape.");
    }
}
