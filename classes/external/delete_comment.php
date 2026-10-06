<?php
namespace mod_videorubric\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

final class delete_comment extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Comment ID'),
        ]);
    }

    public static function execute(int $commentid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('commentid'));
        $comment = $DB->get_record('videorubric_comment', ['id' => $params['commentid']], '*', MUST_EXIST);
        $submission = $DB->get_record('videorubric_submission', ['id' => $comment->submissionid], '*', MUST_EXIST);
        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('videorubric', $activity->id, $activity->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        \mod_videorubric\local\access::require_grading_access($submission, $cm, $context, $USER->id);
        $DB->delete_records('videorubric_comment', ['id' => $comment->id]);
        return ['deleted' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['deleted' => new external_value(PARAM_BOOL, 'Deleted')]);
    }
}
