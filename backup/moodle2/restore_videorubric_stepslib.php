<?php

defined('MOODLE_INTERNAL') || die();

class restore_videorubric_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videorubric', '/activity/videorubric'),
            new restore_path_element('videorubric_criterion', '/activity/videorubric/criteria/criterion'),
            new restore_path_element('videorubric_level', '/activity/videorubric/criteria/criterion/levels/level'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videorubric_submission', '/activity/videorubric/submissions/submission');
            $paths[] = new restore_path_element('videorubric_grade', '/activity/videorubric/submissions/submission/grades/grade');
            $paths[] = new restore_path_element('videorubric_gradeselection', '/activity/videorubric/submissions/submission/grades/grade/gradeselections/gradeselection');
            $paths[] = new restore_path_element('videorubric_comment', '/activity/videorubric/submissions/submission/comments/comment');
        }
        return $this->prepare_activity_structure($paths);
    }

    protected function process_videorubric($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        if (!empty($data->duedate)) {
            $data->duedate = $this->apply_date_offset($data->duedate);
        }
        $newid = $DB->insert_record('videorubric', $data);
        $this->apply_activity_instance($newid);
        $this->set_mapping('videorubric', $oldid, $newid, true);
    }

    protected function process_videorubric_criterion($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videorubricid = $this->get_new_parentid('videorubric');
        $newid = $DB->insert_record('videorubric_criteria', $data);
        $this->set_mapping('videorubric_criterion', $oldid, $newid);
    }

    protected function process_videorubric_level($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->criterionid = $this->get_new_parentid('videorubric_criterion');
        $newid = $DB->insert_record('videorubric_levels', $data);
        $this->set_mapping('videorubric_level', $oldid, $newid);
    }

    protected function process_videorubric_submission($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videorubricid = $this->get_new_parentid('videorubric');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if ($data->timesubmitted) {
            $data->timesubmitted = $this->apply_date_offset($data->timesubmitted);
        }
        $newid = $DB->insert_record('videorubric_submission', $data);
        $this->set_mapping('videorubric_submission', $oldid, $newid, true);
    }

    protected function process_videorubric_grade($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->submissionid = $this->get_new_parentid('videorubric_submission');
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if ($data->timegraded) {
            $data->timegraded = $this->apply_date_offset($data->timegraded);
        }
        $newid = $DB->insert_record('videorubric_grade', $data);
        $this->set_mapping('videorubric_grade', $oldid, $newid, true);
    }

    protected function process_videorubric_gradeselection($data): void {
        global $DB;
        $data = (object)$data;
        $data->gradeid = $this->get_new_parentid('videorubric_grade');
        $data->criterionid = $this->get_mappingid('videorubric_criterion', $data->criterionid);
        $data->levelid = $this->get_mappingid('videorubric_level', $data->levelid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $DB->insert_record('videorubric_grade_criterion', $data);
    }

    protected function process_videorubric_comment($data): void {
        global $DB;
        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('videorubric_submission');
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $DB->insert_record('videorubric_comment', $data);
    }

    protected function after_execute(): void {
        $this->add_related_files('mod_videorubric', 'intro', null);
        $this->add_related_files('mod_videorubric', 'submission_video', 'videorubric_submission');
        $this->add_related_files('mod_videorubric', 'feedback_audio', 'videorubric_grade');
    }
}
