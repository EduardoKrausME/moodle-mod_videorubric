<?php
namespace mod_videorubric\event;

final class course_module_viewed extends \core\event\course_module_viewed {
    protected function init(): void {
        $this->data['objecttable'] = 'videorubric';
        parent::init();
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'videorubric', 'restore' => 'videorubric'];
    }
}
