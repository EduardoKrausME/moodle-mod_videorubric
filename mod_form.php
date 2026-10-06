<?php
// This file is part of Moodle - https://moodle.org/

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form.
 */
class mod_videorubric_mod_form extends moodleform_mod {
    /** @var array custom completion element names */
    private array $completionelements = [];

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

    public function add_completion_rules(): array {
        $mform = $this->_form;
        $this->completionelements = ['completionsubmit', 'completiongraded', 'completionminenabled', 'completionmingrade'];

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completion:submit', 'mod_videorubric'));
        $mform->addElement('advcheckbox', 'completiongraded', '', get_string('completion:graded', 'mod_videorubric'));
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'completionminenabled', '', get_string('completion:minenable', 'mod_videorubric'));
        $group[] = $mform->createElement('text', 'completionmingrade', '', ['size' => 6]);
        $mform->addGroup($group, 'completionmingroup', '', ' ', false);
        $mform->setType('completionmingrade', PARAM_FLOAT);
        $mform->setDefault('completionmingrade', 0);
        $mform->hideIf('completionmingrade', 'completionminenabled', 'notchecked');
        return $this->completionelements;
    }

    public function completion_rule_enabled($data): bool {
        return !empty($data['completionsubmit']) || !empty($data['completiongraded']) || !empty($data['completionminenabled']);
    }

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
