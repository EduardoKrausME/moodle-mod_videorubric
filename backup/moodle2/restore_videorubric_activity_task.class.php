<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/videorubric/backup/moodle2/restore_videorubric_stepslib.php');

class restore_videorubric_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_videorubric_activity_structure_step('videorubric_structure', 'videorubric.xml'));
    }

    public static function define_decode_contents(): array {
        return [new restore_decode_content('videorubric', ['intro'], 'videorubric')];
    }

    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule('VIDEORUBRICVIEWBYID', '/mod/videorubric/view.php?id=$1', 'course_module'),
            new restore_decode_rule('VIDEORUBRICINDEX', '/mod/videorubric/index.php?id=$1', 'course'),
        ];
    }
}
