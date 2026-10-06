<?php
namespace mod_videorubric\event;

final class temporal_comment_created extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'videorubric_comment';
    }
    public static function get_name(): string { return get_string('event:temporalcommentcreated', 'mod_videorubric'); }
    public function get_description(): string {
        return "The user with id '{$this->userid}' created a timestamped comment for submission '{$this->other['submissionid']}'.";
    }
    public static function get_objectid_mapping(): array { return ['db' => 'videorubric_comment', 'restore' => 'videorubric_comment']; }
    public static function get_other_mapping(): array { return ['submissionid' => ['db' => 'videorubric_submission', 'restore' => 'videorubric_submission']]; }
}
