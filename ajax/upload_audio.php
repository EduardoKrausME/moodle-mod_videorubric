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
 * upload_audio.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

header('Content-Type: application/json; charset=utf-8');
try {
    $cmid = required_param('cmid', PARAM_INT);
    $submissionid = required_param('submissionid', PARAM_INT);
    require_sesskey();

    $cm = get_coursemodule_from_id('videorubric', $cmid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_login($course, true, $cm);
    require_capability('mod/videorubric:grade', $context);

    $submission = $DB->get_record('videorubric_submission', ['id' => $submissionid], '*', MUST_EXIST);
    \mod_videorubric\local\access::require_grading_access($submission, $cm, $context, $USER->id);
    if (empty($_FILES['audio']['tmp_name']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('error:upload', 'mod_videorubric');
    }
    if ((int)$_FILES['audio']['size'] > 20 * 1024 * 1024) {
        throw new moodle_exception('error:audiofilesize', 'mod_videorubric');
    }
    $mime = strtolower(mime_content_type($_FILES['audio']['tmp_name']) ?: ($_FILES['audio']['type'] ?? ''));
    $allowed = ['audio/webm', 'video/webm', 'audio/ogg', 'audio/mp4', 'video/mp4', 'application/octet-stream'];
    if (!in_array($mime, $allowed, true)) {
        throw new moodle_exception('error:audiotype', 'mod_videorubric');
    }

    $grade = \mod_videorubric\local\grading_manager::get_or_create_grade($submission, $USER->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_videorubric', 'feedback_audio', $grade->id);
    $ext = str_contains($mime, 'ogg') ? 'ogg' : (str_contains($mime, 'mp4') ? 'm4a' : 'webm');
    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'mod_videorubric',
        'filearea' => 'feedback_audio',
        'itemid' => $grade->id,
        'filepath' => '/',
        'filename' => 'feedback.' . $ext,
        'userid' => $USER->id,
    ], $_FILES['audio']['tmp_name']);

    $url = \mod_videorubric\local\grading_manager::get_feedback_audio_url($context, $grade->id);
    echo json_encode(['success' => true, 'url' => $url ? $url->out(false) : '', 'gradeid' => $grade->id]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => get_exception_info($e)->message]);
}
