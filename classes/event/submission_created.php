<?php
namespace mod_videorubric\event;

final class submission_created extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videorubric_submission';
    }
    public static function get_name(): string { return get_string('event:submissioncreated', 'mod_videorubric'); }
    public function get_description(): string {
        return "The user with id '{$this->userid}' created video submission '{$this->objectid}'.";
    }
    public function get_url(): \moodle_url { return new \moodle_url('/mod/videorubric/submission.php', ['id' => $this->contextinstanceid]); }
    public static function get_objectid_mapping(): array { return ['db' => 'videorubric_submission', 'restore' => 'videorubric_submission']; }
    public static function get_other_mapping(): array { return ['videorubricid' => ['db' => 'videorubric', 'restore' => 'videorubric']]; }
}
