# OnScholars Async Attendance (`local_async_attendance`)

This plugin automatically determines daily student attendance for a fully asynchronous school by scraping Moodle's standard log store. It removes the need for manual roll-call or dependencies on `mod_attendance`.

## Features

- **Daily Log Scraper:** Runs nightly to capture asynchronous student engagement based on minimum activity thresholds.
- **Smart Calendar:** Compresses weekend activity into adjacent weekdays to ensure alignment with standard reporting schemas.
- **Holiday Awareness:** Supports configuring holiday exceptions, where activity on those dates is mapped to the nearest valid school day.
- **Configurable Thresholds:** Define the minimum number of log events required to mark a student as "Present" or "Late".

## Installation

1. Ensure this folder is named `async_attendance` and is placed inside your Moodle installation's `local/` directory (i.e., `moodle/local/async_attendance/`).
2. Log in to your Moodle site as an Administrator.
3. Navigate to **Site administration > Notifications** to trigger the installation process.
4. Complete the installation by following the on-screen prompts.

## Configuration

After installation, configure the plugin settings under **Site administration > Plugins > Local plugins > Async Attendance**:

- **Presence Threshold:** Minimum log events required to count as "Present" (default: 3).
- **Late Threshold:** Minimum log events required to count as "Late" (default: 1).
- **Holiday Exceptions:** Comma-separated list of dates (YYYY-MM-DD) to exclude from standard logging.
- **Compress Weekends:** Option to compress Saturday/Sunday activity into adjacent valid school days.

## Documentation

For full details on the database schema, scheduled tasks, and integration with the OnScholars SIS suite, please refer to the main `implementation_plan.md` located in the suite's root directory.
