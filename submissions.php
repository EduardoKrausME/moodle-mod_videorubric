<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * submissions.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$statusfilter = optional_param('status', '', PARAM_ALPHA);
$groupid = optional_param('group', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videorubric', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videorubric:grade', $context);

$canallgroups = has_capability('mod/videorubric:viewallgroups', $context) || has_capability('moodle/site:accessallgroups', $context);
$allowedgroups = $canallgroups ? groups_get_all_groups($cm->course, 0, $cm->groupingid) : groups_get_activity_allowed_groups($cm);
if ($groupid && !$canallgroups && !isset($allowedgroups[$groupid])) {
    throw new required_capability_exception($context, 'mod/videorubric:viewallgroups', 'nopermissions', '');
}

$users = get_enrolled_users($context, 'mod/videorubric:submit', $groupid, 'u.id,u.firstname,u.lastname,u.email', 'u.lastname,u.firstname');
$rows = [];
foreach ($users as $user) {
    if (!\mod_videorubric\local\access::can_access_user($cm, $context, $USER->id, $user->id)) {
        continue;
    }
    $submission = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $user->id, false);
    $status = 'notsubmitted';
    $grade = null;
    if ($submission && $submission->status === 'draft') {
        $status = 'notsubmitted';
    } else if ($submission && $submission->status === 'submitted') {
        $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id]);
        $status = !$grade ? 'submitted' : ($grade->status === 'graded' ? 'graded' : 'grading');
    }
    if ($statusfilter && $statusfilter !== $status) {
        continue;
    }
    $review = '-';
    if ($submission && $submission->status === 'submitted') {
        $review = html_writer::link(new moodle_url('/mod/videorubric/grade.php', [
            'id' => $cm->id,
            'userid' => $user->id,
            'group' => $groupid,
            'status' => $statusfilter,
        ]), get_string('review', 'mod_videorubric'));
    }
    $score = ($grade && $grade->status === 'graded') ? format_float($grade->finalscore, 2) . ' / ' . format_float($activity->grade, 2) : '-';
    $rows[] = [fullname($user), get_string('status:' . $status, 'mod_videorubric'), $score, $review];
}

$PAGE->set_url('/mod/videorubric/submissions.php', ['id' => $cm->id, 'status' => $statusfilter, 'group' => $groupid]);
$PAGE->set_title(get_string('reviewsubmissions', 'mod_videorubric'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reviewsubmissions', 'mod_videorubric'));

$groups = [0 => get_string('allparticipants')];
foreach ($allowedgroups as $group) {
    $groups[$group->id] = format_string($group->name);
}
$statusoptions = [
    '' => get_string('all'),
    'notsubmitted' => get_string('status:notsubmitted', 'mod_videorubric'),
    'submitted' => get_string('status:submitted', 'mod_videorubric'),
    'grading' => get_string('status:grading', 'mod_videorubric'),
    'graded' => get_string('status:graded', 'mod_videorubric'),
];
?>
<form method="get" class="d-flex gap-2 align-items-end mb-3">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    <div><label class="form-label"><?php echo get_string('group'); ?></label><?php echo html_writer::select($groups, 'group', $groupid, false, ['class' => 'form-select']); ?></div>
    <div><label class="form-label"><?php echo get_string('status'); ?></label><?php echo html_writer::select($statusoptions, 'status', $statusfilter, false, ['class' => 'form-select']); ?></div>
    <button class="btn btn-primary" type="submit"><?php echo get_string('filter'); ?></button>
</form>
<?php
$table = new html_table();
$table->head = [get_string('student', 'mod_videorubric'), get_string('status'), get_string('grade'), get_string('actions')];
$table->data = $rows;
echo html_writer::table($table);
echo $OUTPUT->footer();
