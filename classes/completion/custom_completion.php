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
 * custom_completion.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for Video Rubric.
 */
final class custom_completion extends activity_custom_completion {
    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit', 'completiongraded', 'completionmin'];
    }

    /**
     * Method get_state.
     *
     * @param string $rule Parameter rule.
     * @return int Return value.
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('videorubric', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $submission = $DB->get_record('videorubric_submission', [
            'videorubricid' => $activity->id,
            'userid' => $this->userid,
        ]);

        if ($rule === 'completionsubmit') {
            return ($submission && $submission->status === 'submitted') ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if (!$submission) {
            return COMPLETION_INCOMPLETE;
        }
        $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id]);
        if ($rule === 'completiongraded') {
            return ($grade && $grade->status === 'graded') ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionmin') {
            return ($grade && $grade->status === 'graded' && (float)$grade->finalscore >= (float)$activity->completionmingrade)
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videorubric', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionsubmit' => get_string('completionrule:completionsubmit', 'mod_videorubric'),
            'completiongraded' => get_string('completionrule:completiongraded', 'mod_videorubric'),
            'completionmin' => get_string('completionrule:completionminvalue', 'mod_videorubric', format_float($activity->completionmingrade, 2)),
        ];
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return ['completionsubmit', 'completiongraded', 'completionmin'];
    }
}
