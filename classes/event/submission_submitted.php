<?php
namespace mod_videorubric\event;

final class submission_submitted extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videorubric_submission';
    }
    public static function get_name(): string { return get_string('event:submissionsubmitted', 'mod_videorubric'); }
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted video submission '{$this->objectid}'.";
    }
    public static function get_objectid_mapping(): array { return ['db' => 'videorubric_submission', 'restore' => 'videorubric_submission']; }
    public static function get_other_mapping(): array { return ['videorubricid' => ['db' => 'videorubric', 'restore' => 'videorubric']]; }
}
