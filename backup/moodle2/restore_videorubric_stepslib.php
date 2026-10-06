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
 * restore_videorubric_stepslib.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class restore_videorubric_activity_structure_step.
 */
class restore_videorubric_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
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

    /**
     * Method process_videorubric.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
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

    /**
     * Method process_videorubric_criterion.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videorubric_criterion($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videorubricid = $this->get_new_parentid('videorubric');
        $newid = $DB->insert_record('videorubric_criteria', $data);
        $this->set_mapping('videorubric_criterion', $oldid, $newid);
    }

    /**
     * Method process_videorubric_level.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videorubric_level($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->criterionid = $this->get_new_parentid('videorubric_criterion');
        $newid = $DB->insert_record('videorubric_levels', $data);
        $this->set_mapping('videorubric_level', $oldid, $newid);
    }

    /**
     * Method process_videorubric_submission.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
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

    /**
     * Method process_videorubric_grade.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
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

    /**
     * Method process_videorubric_gradeselection.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videorubric_gradeselection($data): void {
        global $DB;
        $data = (object)$data;
        $data->gradeid = $this->get_new_parentid('videorubric_grade');
        $data->criterionid = $this->get_mappingid('videorubric_criterion', $data->criterionid);
        $data->levelid = $this->get_mappingid('videorubric_level', $data->levelid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $DB->insert_record('videorubric_grade_criterion', $data);
    }

    /**
     * Method process_videorubric_comment.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videorubric_comment($data): void {
        global $DB;
        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('videorubric_submission');
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $DB->insert_record('videorubric_comment', $data);
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videorubric', 'intro', null);
        $this->add_related_files('mod_videorubric', 'submission_video', 'videorubric_submission');
        $this->add_related_files('mod_videorubric', 'feedback_audio', 'videorubric_grade');
    }
}
