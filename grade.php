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
 * grade.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$statusfilter = optional_param('status', '', PARAM_ALPHA);
$cm = get_coursemodule_from_id('videorubric', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videorubric:grade', $context);

$submission = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $userid, false);
if (!$submission || $submission->status !== 'submitted') {
    throw new moodle_exception('submissionnotavailable', 'mod_videorubric');
}
\mod_videorubric\local\access::require_grading_access($submission, $cm, $context, $USER->id);
$student = core_user::get_user($userid, '*', MUST_EXIST);
$videourl = \mod_videorubric\local\submission_manager::get_video_url($context, $submission->id);
if (!$videourl) {
    throw new moodle_exception('error:novideo', 'mod_videorubric');
}

$criteria = \mod_videorubric\local\rubric_manager::get($activity->id);
$grade = \mod_videorubric\local\grading_manager::get_or_create_grade($submission, $USER->id);
$selectedrows = $DB->get_records('videorubric_grade_criterion', ['gradeid' => $grade->id]);
$selected = [];
foreach ($selectedrows as $row) {
    $selected[$row->criterionid] = $row->levelid;
}
$rubric = [];
foreach ($criteria as $criterion) {
    $levels = [];
    foreach ($criterion->levels as $level) {
        $levels[] = [
            'id' => $level->id,
            'label' => format_string($level->label),
            'description' => format_text($level->description, FORMAT_PLAIN),
            'points' => format_float($level->points, 2),
            'selected' => isset($selected[$criterion->id]) && (int)$selected[$criterion->id] === (int)$level->id,
        ];
    }
    $rubric[] = [
        'id' => $criterion->id,
        'name' => format_string($criterion->name),
        'description' => format_text($criterion->description, FORMAT_PLAIN),
        'weight' => format_float($criterion->weight, 2),
        'showweight' => !empty($activity->useweights),
        'levels' => $levels,
    ];
}

$comments = [];
foreach ($DB->get_records('videorubric_comment', ['submissionid' => $submission->id], 'timeposition, id') as $comment) {
    $comments[] = [
        'id' => $comment->id,
        'timeposition' => $comment->timeposition,
        'time' => gmdate('i:s', $comment->timeposition),
        'comment' => format_string($comment->commenttext),
    ];
}

// Build previous/next navigation using only accessible submitted users and preserving group filter.
$participants = get_enrolled_users($context, 'mod/videorubric:submit', $groupid, 'u.id,u.firstname,u.lastname', 'u.lastname,u.firstname');
$queue = [];
foreach ($participants as $participant) {
    if (!\mod_videorubric\local\access::can_access_user($cm, $context, $USER->id, $participant->id)) {
        continue;
    }
    $s = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $participant->id, false);
    if (!$s || $s->status !== 'submitted') {
        continue;
    }
    if ($statusfilter) {
        $g = $DB->get_record('videorubric_grade', ['submissionid' => $s->id]);
        $st = !$g ? 'submitted' : ($g->status === 'graded' ? 'graded' : 'grading');
        if ($st !== $statusfilter) {
            continue;
        }
    }
    $queue[] = $participant->id;
}
$position = array_search($userid, $queue, true);
$baseparams = ['id' => $cm->id, 'group' => $groupid, 'status' => $statusfilter];
$previousurl = null;
$nexturl = null;
if ($position !== false && $position > 0) {
    $previousurl = new moodle_url('/mod/videorubric/grade.php', $baseparams + ['userid' => $queue[$position - 1]]);
}
if ($position !== false && $position < count($queue) - 1) {
    $nexturl = new moodle_url('/mod/videorubric/grade.php', $baseparams + ['userid' => $queue[$position + 1]]);
}

$audio = \mod_videorubric\local\grading_manager::get_feedback_audio_url($context, $grade->id);
$data = [
    'cmid' => $cm->id,
    'submissionid' => $submission->id,
    'studentname' => fullname($student),
    'videourl' => $videourl->out(false),
    'rubric' => $rubric,
    'comments' => $comments,
    'feedbacktext' => $grade->feedbacktext,
    'finalscore' => format_float($grade->finalscore, 2),
    'maxgrade' => format_float($activity->grade, 2),
    'gradeisfinal' => $grade->status === 'graded',
    'gradestatus' => get_string('status:' . ($grade->status === 'graded' ? 'graded' : 'grading'), 'mod_videorubric'),
    'previousurl' => $previousurl ? $previousurl->out(false) : '',
    'hasprevious' => (bool)$previousurl,
    'nexturl' => $nexturl ? $nexturl->out(false) : '',
    'hasnext' => (bool)$nexturl,
    'backurl' => (new moodle_url('/mod/videorubric/submissions.php', $baseparams))->out(false),
    'audiourl' => $audio ? $audio->out(false) : '',
    'hasaudio' => (bool)$audio,
];

$PAGE->set_url('/mod/videorubric/grade.php', ['id' => $cm->id, 'userid' => $userid, 'group' => $groupid, 'status' => $statusfilter]);
$PAGE->set_title(get_string('gradingstudent', 'mod_videorubric', fullname($student)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/videorubric/styles.css');
$PAGE->requires->js_call_amd('mod_videorubric/grading', 'init', [[
    'cmid' => $cm->id,
    'submissionid' => $submission->id,
    'sesskey' => sesskey(),
    'audioUploadUrl' => (new moodle_url('/mod/videorubric/ajax/upload_audio.php'))->out(false),
    'strings' => [
        'saved' => get_string('saved', 'mod_videorubric'),
        'saving' => get_string('saving', 'mod_videorubric'),
        'recording' => get_string('recording', 'mod_videorubric'),
    ],
]]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videorubric/grading_interface', $data);
echo $OUTPUT->footer();
