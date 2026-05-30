<?php
/**
 * Basic attendance report page.
 *
 * @package    local_async_attendance
 * @copyright  2026 OnScholars
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Require login and capability.
require_login();
$context = context_system::instance();
require_capability('local/async_attendance:viewreports', $context);

$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$filter_date = optional_param('filter_date', '', PARAM_TEXT);
$filter_status = optional_param('filter_status', '', PARAM_ALPHA);
$filter_name = optional_param('filter_name', '', PARAM_TEXT);

$url = new moodle_url('/local/async_attendance/index.php');
if ($filter_date) {
    $url->param('filter_date', $filter_date);
}
if ($filter_status) {
    $url->param('filter_status', $filter_status);
}
if ($filter_name) {
    $url->param('filter_name', $filter_name);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_async_attendance'));
$PAGE->set_heading(get_string('pluginname', 'local_async_attendance'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();
echo $OUTPUT->heading('Asynchronous Attendance Report');

// Render filter form.
echo html_writer::start_tag('form', ['action' => $url->out(false), 'method' => 'get', 'class' => 'form-inline mb-4']);
echo html_writer::start_tag('div', ['class' => 'd-flex align-items-center flex-wrap']);

// Date Filter
echo html_writer::start_tag('div', ['class' => 'mr-3 mb-2']);
echo html_writer::label('Date: ', 'filter_date', false, ['class' => 'mr-1']);
echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'filter_date', 'id' => 'filter_date', 'value' => $filter_date, 'class' => 'form-control']);
echo html_writer::end_tag('div');

// Status Filter
echo html_writer::start_tag('div', ['class' => 'mr-3 mb-2']);
echo html_writer::label('Status: ', 'filter_status', false, ['class' => 'mr-1']);
$status_options = ['' => 'All', 'PRESENT' => 'Present', 'ABSENT' => 'Absent', 'LATE' => 'Late'];
echo html_writer::select($status_options, 'filter_status', $filter_status, false, ['class' => 'form-control custom-select', 'id' => 'filter_status']);
echo html_writer::end_tag('div');

// Name Filter
echo html_writer::start_tag('div', ['class' => 'mr-3 mb-2']);
echo html_writer::label('Student Name: ', 'filter_name', false, ['class' => 'mr-1']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'filter_name', 'id' => 'filter_name', 'value' => s($filter_name), 'class' => 'form-control', 'placeholder' => 'Search name']);
echo html_writer::end_tag('div');

// Buttons
echo html_writer::start_tag('div', ['class' => 'mb-2']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Filter', 'class' => 'btn btn-primary']);
if ($filter_date || $filter_status || $filter_name) {
    echo html_writer::link(new moodle_url('/local/async_attendance/index.php'), 'Clear', ['class' => 'btn btn-secondary ml-1']);
}
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

// Build SQL filters.
$wheres = [];
$params = [];

if ($filter_date) {
    $target_time = strtotime($filter_date);
    if ($target_time) {
        $midnight = strtotime('today', $target_time);
        $wheres[] = "a.attendance_date = :attdate";
        $params['attdate'] = $midnight;
    }
}
if ($filter_status) {
    $wheres[] = "a.status = :status";
    $params['status'] = $filter_status;
}
if ($filter_name) {
    $wheres[] = $DB->sql_like($DB->sql_fullname('u.firstname', 'u.lastname'), ':name', false, false);
    $params['name'] = '%' . $DB->sql_like_escape($filter_name) . '%';
}

$where_sql = '';
if (!empty($wheres)) {
    $where_sql = 'WHERE ' . implode(' AND ', $wheres);
}

$totalcount = $DB->count_records_sql("SELECT COUNT(1) FROM {local_async_attendance} a JOIN {user} u ON u.id = a.userid $where_sql", $params);
$offset = $page * $perpage;

// Fetch records with user details.
$sql = "SELECT a.*, u.firstname, u.lastname, u.email
          FROM {local_async_attendance} a
          JOIN {user} u ON u.id = a.userid
          $where_sql
      ORDER BY a.attendance_date DESC, u.lastname ASC, u.firstname ASC";

$records = $DB->get_records_sql($sql, $params, $offset, $perpage);

if (empty($records)) {
    echo $OUTPUT->notification('No attendance records found. Ensure the nightly scrape task is running.', 'info');
} else {
    // Render pagination.
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

    // Setup HTML table.
    $table = new html_table();
    $table->head = [
        'Student Name',
        'Date',
        'Status',
        'Total Events',
        'First Event Time',
        'Last Event Time',
    ];
    $table->data = [];

    foreach ($records as $rec) {
        $date_str = userdate($rec->attendance_date, get_string('strftimedate'));
        $first_time = $rec->first_event_time ? userdate($rec->first_event_time, get_string('strftimetime')) : '-';
        $last_time = $rec->last_event_time ? userdate($rec->last_event_time, get_string('strftimetime')) : '-';

        // Add some basic styling based on status.
        $status_display = $rec->status;
        if ($rec->status === 'PRESENT') {
            $status_display = '<span class="badge badge-success bg-success text-white">' . $rec->status . '</span>';
        } else if ($rec->status === 'ABSENT') {
            $status_display = '<span class="badge badge-danger bg-danger text-white">' . $rec->status . '</span>';
        } else if ($rec->status === 'LATE') {
            $status_display = '<span class="badge badge-warning bg-warning text-dark">' . $rec->status . '</span>';
        }

        $table->data[] = [
            fullname($rec),
            $date_str,
            $status_display,
            $rec->total_events,
            $first_time,
            $last_time,
        ];
    }

    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
}

echo $OUTPUT->footer();
