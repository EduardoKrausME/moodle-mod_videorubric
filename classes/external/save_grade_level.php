<?php
namespace mod_videorubric\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

final class save_grade_level extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'criterionid' => new external_value(PARAM_INT, 'Criterion ID'),
            'levelid' => new external_value(PARAM_INT, 'Level ID'),
        ]);
    }

    public static function execute(int $submissionid, int $criterionid, int $levelid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('submissionid', 'criterionid', 'levelid'));
        $submission = $DB->get_record('videorubric_submission', ['id' => $params['submissionid']], '*', MUST_EXIST);
        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('videorubric', $activity->id, $activity->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        \mod_videorubric\local\access::require_grading_access($submission, $cm, $context, $USER->id);

        $grade = \mod_videorubric\local\grading_manager::select_level($submission, $params['criterionid'], $params['levelid'], $USER->id);
        return [
            'gradeid' => (int)$grade->id,
            'rawscore' => (float)$grade->rawscore,
            'finalscore' => (float)$grade->finalscore,
            'status' => $grade->status,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'gradeid' => new external_value(PARAM_INT, 'Grade ID'),
            'rawscore' => new external_value(PARAM_FLOAT, 'Raw rubric score'),
            'finalscore' => new external_value(PARAM_FLOAT, 'Scaled final score'),
            'status' => new external_value(PARAM_ALPHA, 'Grading status'),
        ]);
    }
}
