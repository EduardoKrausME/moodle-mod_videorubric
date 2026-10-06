<?php
namespace mod_videorubric\local;

defined('MOODLE_INTERNAL') || die();

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
