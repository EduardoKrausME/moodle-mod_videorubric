<?php
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

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
if (trim($activity->intro ?? '') !== '') {
    echo $OUTPUT->box(format_module_intro('videorubric', $activity, $cm->id), 'generalbox mod_introbox');
}

if (has_capability('mod/videorubric:grade', $context)) {
    $url = new moodle_url('/mod/videorubric/submissions.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($url, get_string('reviewsubmissions', 'mod_videorubric'), 'get');
    $rubricurl = new moodle_url('/mod/videorubric/rubric.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($rubricurl, get_string('managerubric', 'mod_videorubric'), 'get');
} else {
    require_capability('mod/videorubric:submit', $context);
    $submission = \mod_videorubric\local\submission_manager::get_for_user($activity->id, $USER->id, false);
    $status = $submission ? $submission->status : 'notsubmitted';
    echo $OUTPUT->box(get_string('status:' . $status, 'mod_videorubric'), 'alert alert-info');
    $url = new moodle_url('/mod/videorubric/submission.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($url, get_string('opensubmission', 'mod_videorubric'), 'get');

    if ($submission && $submission->status === 'submitted') {
        $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id, 'status' => 'graded']);
        if ($grade) {
            echo $OUTPUT->heading(get_string('feedback', 'mod_videorubric'), 3);
            echo html_writer::div(get_string('finalscore', 'mod_videorubric') . ': ' . format_float($grade->finalscore, 2), 'h5');
            if (!empty($grade->feedbacktext)) {
                echo format_text($grade->feedbacktext, FORMAT_PLAIN);
            }
            $audio = \mod_videorubric\local\grading_manager::get_feedback_audio_url($context, $grade->id);
            if ($audio) {
                echo html_writer::tag('audio', '', ['controls' => 'controls', 'src' => $audio->out(false), 'class' => 'w-100 mt-3']);
            }
        }
    }
}

echo $OUTPUT->footer();
