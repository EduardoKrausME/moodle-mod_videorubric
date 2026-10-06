<?php
require('../../../config.php');

header('Content-Type: application/json; charset=utf-8');
try {
    $cmid = required_param('cmid', PARAM_INT);
    $submissionid = required_param('submissionid', PARAM_INT);
    $duration = optional_param('duration', 0, PARAM_INT);
    require_sesskey();

    $cm = get_coursemodule_from_id('videorubric', $cmid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_login($course, true, $cm);
    require_capability('mod/videorubric:submit', $context);

    if (empty($activity->allowrecording)) {
        throw new moodle_exception('recordingnotallowed', 'mod_videorubric');
    }
    $submission = $DB->get_record('videorubric_submission', ['id' => $submissionid], '*', MUST_EXIST);
    if ((int)$submission->userid !== (int)$USER->id || (int)$submission->videorubricid !== (int)$activity->id) {
        throw new moodle_exception('invalidsubmission', 'mod_videorubric');
    }
    if ($submission->status === 'submitted') {
        throw new moodle_exception('submissionlocked', 'mod_videorubric');
    }
    if (empty($_FILES['video']['tmp_name']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('error:upload', 'mod_videorubric');
    }

    $mime = mime_content_type($_FILES['video']['tmp_name']) ?: ($_FILES['video']['type'] ?? 'video/webm');
    $extension = str_contains(strtolower($mime), 'mp4') ? 'mp4' : 'webm';
    \mod_videorubric\local\submission_manager::save_uploaded_video(
        $submission,
        $context,
        $_FILES['video']['tmp_name'],
        'recording.' . $extension,
        $mime,
        (int)$_FILES['video']['size'],
        $duration
    );
    $url = \mod_videorubric\local\submission_manager::get_video_url($context, $submission->id);
    echo json_encode(['success' => true, 'url' => $url ? $url->out(false) : '']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => get_exception_info($e)->message]);
}
