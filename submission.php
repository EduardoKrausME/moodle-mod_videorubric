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
 * submission.php
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
require_capability('mod/videorubric:submit', $context);

$submission = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $USER->id, true);
\mod_videorubric\local\access::require_submission_access($submission, $cm, $context, $USER->id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if ($submission->status === 'submitted') {
        throw new moodle_exception('submissionlocked', 'mod_videorubric');
    }

    if (!empty($_FILES['videofile']['tmp_name'])) {
        if (empty($activity->allowupload)) {
            throw new moodle_exception('uploadnotallowed', 'mod_videorubric');
        }
        if ($_FILES['videofile']['error'] !== UPLOAD_ERR_OK) {
            throw new moodle_exception('error:upload', 'mod_videorubric');
        }
        $mime = mime_content_type($_FILES['videofile']['tmp_name']) ?: ($_FILES['videofile']['type'] ?? '');
        \mod_videorubric\local\submission_manager::save_uploaded_video(
            $submission,
            $context,
            $_FILES['videofile']['tmp_name'],
            $_FILES['videofile']['name'],
            $mime,
            (int)$_FILES['videofile']['size'],
            optional_param('duration', 0, PARAM_INT)
        );
        $submission = $DB->get_record('videorubric_submission', ['id' => $submission->id], '*', MUST_EXIST);
    }

    if (optional_param('action', '', PARAM_ALPHA) === 'submit') {
        \mod_videorubric\local\submission_manager::submit($submission, $cm, $context);
        redirect(new moodle_url('/mod/videorubric/view.php', ['id' => $cm->id]), get_string('submissionsent', 'mod_videorubric'));
    }
    redirect(new moodle_url('/mod/videorubric/submission.php', ['id' => $cm->id]), get_string('draftsaved', 'mod_videorubric'));
}

$PAGE->set_url('/mod/videorubric/submission.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('submission', 'mod_videorubric'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/videorubric/styles.css');

$videourl = \mod_videorubric\local\submission_manager::get_video_url($context, $submission->id);
$data = [
    'cmid' => $cm->id,
    'submissionid' => $submission->id,
    'status' => get_string('status:' . $submission->status, 'mod_videorubric'),
    'issubmitted' => $submission->status === 'submitted',
    'allowupload' => !empty($activity->allowupload),
    'allowrecording' => !empty($activity->allowrecording),
    'videourl' => $videourl ? $videourl->out(false) : '',
    'hasvideo' => (bool)$videourl,
    'sesskey' => sesskey(),
    'maxduration' => (int)$activity->maxduration,
    'duedate' => !empty($activity->duedate) ? userdate($activity->duedate) : '',
];

if ($data['allowrecording'] && !$data['issubmitted']) {
    $PAGE->requires->js_call_amd('mod_videorubric/recorder', 'init', [[
        'cmid' => $cm->id,
        'submissionid' => $submission->id,
        'sesskey' => sesskey(),
        'maxduration' => (int)$activity->maxduration,
        'uploadUrl' => (new moodle_url('/mod/videorubric/ajax/upload_video.php'))->out(false),
    ]]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('submission', 'mod_videorubric'));
echo $OUTPUT->render_from_template('mod_videorubric/submission', $data);
echo $OUTPUT->footer();
