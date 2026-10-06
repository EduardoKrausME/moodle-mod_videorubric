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
 * Activity view page.
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videorubric', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/videorubric/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$event = \mod_videorubric\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('videorubric', $activity);
$event->trigger();

if ($completion = new completion_info($course)) {
    $completion->set_module_viewed($cm);
}

$data = [
    'activityname' => format_string($activity->name),
    'hasintro' => trim($activity->intro ?? '') !== '',
    'intro' => trim($activity->intro ?? '') !== ''
        ? format_module_intro('videorubric', $activity, $cm->id)
        : '',
    'isgrader' => has_capability('mod/videorubric:grade', $context),
];

if ($data['isgrader']) {
    $data['reviewurl'] = (new moodle_url('/mod/videorubric/submissions.php', ['id' => $cm->id]))->out(false);
    $data['rubricurl'] = (new moodle_url('/mod/videorubric/rubric.php', ['id' => $cm->id]))->out(false);
} else {
    require_capability('mod/videorubric:submit', $context);

    $submission = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $USER->id, false);
    $status = $submission ? $submission->status : 'notsubmitted';
    $data['status'] = get_string('status:' . $status, 'mod_videorubric');
    $data['submissionurl'] = (new moodle_url('/mod/videorubric/submission.php', ['id' => $cm->id]))->out(false);
    $data['hasfeedback'] = false;

    if ($submission && $submission->status === 'submitted') {
        $grade = $DB->get_record('videorubric_grade', [
            'submissionid' => $submission->id,
            'status' => 'graded',
        ]);
        if ($grade) {
            $audio = \mod_videorubric\local\grading_manager::get_feedback_audio_url($context, $grade->id);
            $data['hasfeedback'] = true;
            $data['finalscore'] = format_float($grade->finalscore, 2);
            $data['hasfeedbacktext'] = !empty($grade->feedbacktext);
            $data['feedbacktext'] = !empty($grade->feedbacktext)
                ? format_text($grade->feedbacktext, FORMAT_PLAIN)
                : '';
            $data['hasaudio'] = (bool)$audio;
            $data['audiourl'] = $audio ? $audio->out(false) : '';
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videorubric/view', $data);
echo $OUTPUT->footer();
