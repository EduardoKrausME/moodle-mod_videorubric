<?php
namespace mod_videorubric\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

final class add_comment extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'timeposition' => new external_value(PARAM_INT, 'Position in seconds'),
            'comment' => new external_value(PARAM_RAW, 'Comment'),
        ]);
    }

    public static function execute(int $submissionid, int $timeposition, string $comment): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('submissionid', 'timeposition', 'comment'));
        if (trim($params['comment']) === '') {
            throw new \invalid_parameter_exception('Comment cannot be empty');
        }
        $submission = $DB->get_record('videorubric_submission', ['id' => $params['submissionid']], '*', MUST_EXIST);
        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('videorubric', $activity->id, $activity->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        \mod_videorubric\local\access::require_grading_access($submission, $cm, $context, $USER->id);
        $record = \mod_videorubric\local\grading_manager::add_comment($submission, $params['timeposition'], $params['comment'], $USER->id, $context);
        return [
            'id' => (int)$record->id,
            'timeposition' => (int)$record->timeposition,
            'formattedtime' => gmdate('i:s', (int)$record->timeposition),
            'comment' => $record->commenttext,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Comment ID'),
            'timeposition' => new external_value(PARAM_INT, 'Position in seconds'),
            'formattedtime' => new external_value(PARAM_TEXT, 'Formatted position'),
            'comment' => new external_value(PARAM_TEXT, 'Comment'),
        ]);
    }
}
