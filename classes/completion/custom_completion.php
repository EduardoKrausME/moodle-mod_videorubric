<?php
namespace mod_videorubric\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for Video Rubric.
 */
final class custom_completion extends activity_custom_completion {
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit', 'completiongraded', 'completionmin'];
    }

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

    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videorubric', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionsubmit' => get_string('completionrule:completionsubmit', 'mod_videorubric'),
            'completiongraded' => get_string('completionrule:completiongraded', 'mod_videorubric'),
            'completionmin' => get_string('completionrule:completionminvalue', 'mod_videorubric', format_float($activity->completionmingrade, 2)),
        ];
    }

    public function get_sort_order(): array {
        return ['completionsubmit', 'completiongraded', 'completionmin'];
    }
}
