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
 * submission_manager.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\local;

/**
 * Submission lifecycle and File API storage.
 */
final class submission_manager {
    /**
     * Method get_for_user.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @param bool $create Parameter create.
     * @return ?\stdClass Return value.
     */
    public static function get_for_user(int $activityid, int $userid, bool $create = false): ?\stdClass {
        global $DB;

        $submission = $DB->get_record('videorubric_submission', [
            'videorubricid' => $activityid,
            'userid' => $userid,
        ]);
        if (!$submission && $create) {
            $now = time();
            $record = (object)[
                'videorubricid' => $activityid,
                'userid' => $userid,
                'status' => 'draft',
                'duration' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
                'timesubmitted' => 0,
            ];
            $record->id = $DB->insert_record('videorubric_submission', $record);
            $submission = $record;
            $event = \mod_videorubric\event\submission_created::create([
                'objectid' => $record->id,
                'context' => \context_module::instance(self::get_cm($activityid)->id),
                'relateduserid' => $userid,
                'other' => ['videorubricid' => $activityid],
            ]);
            $event->trigger();
        }
        return $submission ?: null;
    }

    /**
     * Method get_cm.
     *
     * @param int $activityid Parameter activityid.
     * @return \stdClass Return value.
     */
    public static function get_cm(int $activityid): \stdClass {
        global $DB;
        $activity = $DB->get_record('videorubric', ['id' => $activityid], '*', MUST_EXIST);
        return get_coursemodule_from_instance('videorubric', $activityid, $activity->course, false, MUST_EXIST);
    }

    /**
     * Method save_uploaded_video.
     *
     * @param \stdClass $submission Parameter submission.
     * @param \context_module $context Parameter context.
     * @param string $tmpname Parameter tmpname.
     * @param string $originalname Parameter originalname.
     * @param string $mimetype Parameter mimetype.
     * @param int $filesize Parameter filesize.
     * @param int $duration Parameter duration.
     * @return void Return value.
     */
    public static function save_uploaded_video(\stdClass $submission, \context_module $context, string $tmpname,
            string $originalname, string $mimetype, int $filesize, int $duration = 0): void {
        global $DB;

        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        self::validate_video($activity, $originalname, $mimetype, $filesize, $duration);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_videorubric', 'submission_video', $submission->id);
        $cleanname = clean_param($originalname, PARAM_FILE);
        if ($cleanname === '' || $cleanname === '.') {
            $cleanname = self::default_filename($mimetype);
        }
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_videorubric',
            'filearea' => 'submission_video',
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => $cleanname,
            'userid' => $submission->userid,
        ];
        $fs->create_file_from_pathname($fileinfo, $tmpname);

        $DB->set_field('videorubric_submission', 'duration', max(0, $duration), ['id' => $submission->id]);
        $DB->set_field('videorubric_submission', 'timemodified', time(), ['id' => $submission->id]);
    }

    /**
     * Method get_video_file.
     *
     * @param \context_module $context Parameter context.
     * @param int $submissionid Parameter submissionid.
     * @return ?\stored_file Return value.
     */
    public static function get_video_file(\context_module $context, int $submissionid): ?\stored_file {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_videorubric', 'submission_video', $submissionid,
            'id DESC', false);
        return $files ? reset($files) : null;
    }

    /**
     * Method get_video_url.
     *
     * @param \context_module $context Parameter context.
     * @param int $submissionid Parameter submissionid.
     * @return ?\moodle_url Return value.
     */
    public static function get_video_url(\context_module $context, int $submissionid): ?\moodle_url {
        $file = self::get_video_file($context, $submissionid);
        if (!$file) {
            return null;
        }
        return \moodle_url::make_pluginfile_url($context->id, 'mod_videorubric', 'submission_video',
            $submissionid, $file->get_filepath(), $file->get_filename(), false);
    }

    /**
     * Method submit.
     *
     * @param \stdClass $submission Parameter submission.
     * @param \stdClass $cm Parameter cm.
     * @param \context_module $context Parameter context.
     * @return void Return value.
     */
    public static function submit(\stdClass $submission, \stdClass $cm, \context_module $context): void {
        global $DB;

        if ((int)$submission->userid !== (int)$GLOBALS['USER']->id) {
            throw new \moodle_exception('invalidsubmission', 'mod_videorubric');
        }
        if (!self::get_video_file($context, $submission->id)) {
            throw new \moodle_exception('error:novideo', 'mod_videorubric');
        }
        $now = time();
        $DB->update_record('videorubric_submission', (object)[
            'id' => $submission->id,
            'status' => 'submitted',
            'timesubmitted' => $now,
            'timemodified' => $now,
        ]);

        $event = \mod_videorubric\event\submission_submitted::create([
            'objectid' => $submission->id,
            'context' => $context,
            'relateduserid' => $submission->userid,
            'other' => ['videorubricid' => $submission->videorubricid],
        ]);
        $event->trigger();

        $completion = new \completion_info(get_course($cm->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $submission->userid);
        }
    }

    /**
     * Method validate_video.
     *
     * @param \stdClass $activity Parameter activity.
     * @param string $filename Parameter filename.
     * @param string $mimetype Parameter mimetype.
     * @param int $filesize Parameter filesize.
     * @param int $duration Parameter duration.
     * @return void Return value.
     */
    private static function validate_video(\stdClass $activity, string $filename, string $mimetype,
            int $filesize, int $duration): void {
        $allowedext = ['mp4', 'webm', 'mov', 'm4v'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedext, true)) {
            throw new \moodle_exception('error:videotype', 'mod_videorubric');
        }
        $allowedmime = ['video/mp4', 'video/webm', 'video/quicktime', 'application/octet-stream'];
        if ($mimetype !== '' && !in_array(strtolower($mimetype), $allowedmime, true)) {
            throw new \moodle_exception('error:videotype', 'mod_videorubric');
        }
        $maxbytes = (int)$activity->maxbytes;
        if ($maxbytes > 0 && $filesize > $maxbytes) {
            throw new \moodle_exception('error:filesize', 'mod_videorubric');
        }
        if ((int)$activity->maxduration > 0 && $duration > (int)$activity->maxduration) {
            throw new \moodle_exception('error:duration', 'mod_videorubric');
        }
    }

    /**
     * Method default_filename.
     *
     * @param string $mimetype Parameter mimetype.
     * @return string Return value.
     */
    private static function default_filename(string $mimetype): string {
        return strtolower($mimetype) === 'video/mp4' ? 'recording.mp4' : 'recording.webm';
    }
}
