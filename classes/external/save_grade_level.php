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
 * save_grade_level.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Class save_grade_level.
 */
final class save_grade_level extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'criterionid' => new external_value(PARAM_INT, 'Criterion ID'),
            'levelid' => new external_value(PARAM_INT, 'Level ID'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $submissionid Parameter submissionid.
     * @param int $criterionid Parameter criterionid.
     * @param int $levelid Parameter levelid.
     * @return array Return value.
     */
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

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'gradeid' => new external_value(PARAM_INT, 'Grade ID'),
            'rawscore' => new external_value(PARAM_FLOAT, 'Raw rubric score'),
            'finalscore' => new external_value(PARAM_FLOAT, 'Scaled final score'),
            'status' => new external_value(PARAM_ALPHA, 'Grading status'),
        ]);
    }
}
