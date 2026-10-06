<?php
namespace mod_videorubric\event;

final class submission_graded extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'videorubric_grade';
    }
    public static function get_name(): string { return get_string('event:submissiongraded', 'mod_videorubric'); }
    public function get_description(): string {
        return "The user with id '{$this->userid}' graded submission '{$this->other['submissionid']}' for user '{$this->relateduserid}'.";
    }
    public static function get_objectid_mapping(): array { return ['db' => 'videorubric_grade', 'restore' => 'videorubric_grade']; }
    public static function get_other_mapping(): array { return ['submissionid' => ['db' => 'videorubric_submission', 'restore' => 'videorubric_submission']]; }
}
