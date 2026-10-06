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
 * access.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\local;

/**
 * Centralised access checks for submissions and feedback.
 */
final class access {
    /**
     * Whether a viewer may access a target user in this activity.
     */
    public static function can_access_user(\stdClass $cm, \context_module $context, int $viewerid, int $targetuserid): bool {
        if ($viewerid === $targetuserid) {
            return true;
        }
        if (!has_capability('mod/videorubric:grade', $context, $viewerid)) {
            return false;
        }

        if (groups_get_activity_groupmode($cm) !== SEPARATEGROUPS ||
                (has_capability('mod/videorubric:viewallgroups', $context, $viewerid) ||
                has_capability('moodle/site:accessallgroups', $context, $viewerid))) {
            return true;
        }

        $allowed = groups_get_activity_allowed_groups($cm);
        if (!$allowed) {
            return false;
        }
        $targetgroups = groups_get_all_groups($cm->course, $targetuserid, $cm->groupingid, 'g.id');
        if (!$targetgroups) {
            return false;
        }
        return (bool)array_intersect(array_keys($allowed), array_keys($targetgroups));
    }

    /**
     * Require permission to view a submission video.
     */
    public static function require_submission_access(\stdClass $submission, \stdClass $cm,
            \context_module $context, int $viewerid): void {
        global $DB;

        if ((int)$submission->videorubricid !== (int)$cm->instance) {
            throw new \moodle_exception('invalidsubmission', 'mod_videorubric');
        }
        if (!self::can_access_user($cm, $context, $viewerid, (int)$submission->userid)) {
            throw new \required_capability_exception($context, 'mod/videorubric:view', 'nopermissions', '');
        }
        if ($viewerid !== (int)$submission->userid && $submission->status !== 'submitted') {
            throw new \moodle_exception('submissionnotavailable', 'mod_videorubric');
        }
    }

    /**
     * Require permission to view feedback.
     */
    public static function require_feedback_access(\stdClass $submission, \stdClass $cm,
            \context_module $context, int $viewerid): void {
        global $DB;

        if ((int)$submission->videorubricid !== (int)$cm->instance) {
            throw new \moodle_exception('invalidsubmission', 'mod_videorubric');
        }
        if ($viewerid === (int)$submission->userid) {
            $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id]);
            if (!$grade || $grade->status !== 'graded') {
                throw new \moodle_exception('feedbacknotavailable', 'mod_videorubric');
            }
            return;
        }
        if (!self::can_access_user($cm, $context, $viewerid, (int)$submission->userid)) {
            throw new \required_capability_exception($context, 'mod/videorubric:grade', 'nopermissions', '');
        }
    }

    /**
     * Require grader access to a submission.
     */
    public static function require_grading_access(\stdClass $submission, \stdClass $cm,
            \context_module $context, int $graderid): void {
        require_capability('mod/videorubric:grade', $context, $graderid);
        if ($submission->status !== 'submitted') {
            throw new \moodle_exception('submissionnotavailable', 'mod_videorubric');
        }
        if (!self::can_access_user($cm, $context, $graderid, (int)$submission->userid)) {
            throw new \required_capability_exception($context, 'mod/videorubric:grade', 'nopermissions', '');
        }
    }
}
