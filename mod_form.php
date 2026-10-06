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
 * mod_form.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form.
 */
class mod_videorubric_mod_form extends moodleform_mod {
    /** @var array custom completion element names */
    private array $completionelements = [];

    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videorubricname', 'mod_videorubric'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'submissionhdr', get_string('submissionoptions', 'mod_videorubric'));
        $mform->addElement('advcheckbox', 'allowupload', get_string('allowupload', 'mod_videorubric'));
        $mform->setDefault('allowupload', 1);
        $mform->addElement('advcheckbox', 'allowrecording', get_string('allowrecording', 'mod_videorubric'));
        $mform->setDefault('allowrecording', 1);
        $mform->addElement('select', 'maxbytes', get_string('maximumupload'), get_max_upload_sizes($CFG->maxbytes, 0, 0));
        $mform->addElement('text', 'maxduration', get_string('maxduration', 'mod_videorubric'), ['size' => 8]);
        $mform->setType('maxduration', PARAM_INT);
        $mform->setDefault('maxduration', 0);
        $mform->addHelpButton('maxduration', 'maxduration', 'mod_videorubric');
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'mod_videorubric'), ['optional' => true]);

        $mform->addElement('header', 'gradinghdr', get_string('gradingoptions', 'mod_videorubric'));
        $mform->addElement('text', 'grade', get_string('maximumgrade', 'mod_videorubric'), ['size' => 8]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', 100);
        $mform->addRule('grade', null, 'required', null, 'client');
        $mform->addElement('advcheckbox', 'useweights', get_string('useweights', 'mod_videorubric'));
        $mform->setDefault('useweights', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $this->completionelements = ['completionsubmit', 'completiongraded', 'completionminenabled', 'completionmingrade'];

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completion:submit', 'mod_videorubric'));
        $mform->addElement('advcheckbox', 'completiongraded', '', get_string('completion:graded', 'mod_videorubric'));
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'completionminenabled', '',
            get_string('completion:minenable', 'mod_videorubric'));
        $group[] = $mform->createElement('text', 'completionmingrade', '', ['size' => 6]);
        $mform->addGroup($group, 'completionmingroup', '', ' ', false);
        $mform->setType('completionmingrade', PARAM_FLOAT);
        $mform->setDefault('completionmingrade', 0);
        $mform->hideIf('completionmingrade', 'completionminenabled', 'notchecked');
        return $this->completionelements;
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionsubmit']) || !empty($data['completiongraded']) || !empty($data['completionminenabled']);
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['allowupload']) && empty($data['allowrecording'])) {
            $errors['allowupload'] = get_string('error:submissionmethod', 'mod_videorubric');
        }
        if ((float)$data['grade'] <= 0) {
            $errors['grade'] = get_string('error:positivegrade', 'mod_videorubric');
        }
        if (!empty($data['completionminenabled']) && (float)$data['completionmingrade'] < 0) {
            $errors['completionmingrade'] = get_string('error:nonnegative', 'mod_videorubric');
        }
        return $errors;
    }
}
