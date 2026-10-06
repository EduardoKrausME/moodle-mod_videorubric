<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/videorubric/backup/moodle2/backup_videorubric_stepslib.php');

class backup_videorubric_activity_task extends backup_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_videorubric_activity_structure_step('videorubric_structure', 'videorubric.xml'));
    }

    public static function encode_content_links($content): string {
        $base = preg_quote($GLOBALS['CFG']->wwwroot, '/');
        $content = preg_replace("!($base/mod/videorubric/index.php\?id=)([0-9]+)!", '$@VIDEORUBRICINDEX*$2@$', $content);
        return preg_replace("!($base/mod/videorubric/view.php\?id=)([0-9]+)!", '$@VIDEORUBRICVIEWBYID*$2@$', $content);
    }
}
