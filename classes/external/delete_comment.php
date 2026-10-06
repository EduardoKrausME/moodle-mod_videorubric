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
 * delete_comment.php
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
 * Class delete_comment.
 */
final class delete_comment extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Comment ID'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $commentid Parameter commentid.
     * @return array Return value.
     */
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

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['deleted' => new external_value(PARAM_BOOL, 'Deleted')]);
    }
}
