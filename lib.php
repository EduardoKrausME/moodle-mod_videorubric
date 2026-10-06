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
 * lib.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns supported module features.
 *
 * @param string $feature
 * @return bool|string|null
 */
function videorubric_supports(string $feature): bool|string|null {
    return match ($feature) {
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_GRADE_HAS_GRADE => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_ASSESSMENT,
        default => null,
    };
}

/**
 * Add instance.
 *
 * @param stdClass $data
 * @param mod_videorubric_mod_form|null $mform
 * @return int
 */
function videorubric_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->id = $DB->insert_record('videorubric', $data);

    videorubric_grade_item_update($data);
    return $data->id;
}

/**
 * Update instance.
 *
 * @param stdClass $data
 * @param mod_videorubric_mod_form|null $mform
 * @return bool
 */
function videorubric_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record('videorubric', $data);
    videorubric_grade_item_update($data);
    return true;
}

/**
 * Delete instance and all related data/files.
 *
 * @param int $id
 * @return bool
 */
function videorubric_delete_instance(int $id): bool {
    global $DB;

    if (!$activity = $DB->get_record('videorubric', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videorubric', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_videorubric');
    }

    $submissionids = $DB->get_fieldset_select('videorubric_submission', 'id', 'videorubricid = ?', [$id]);
    if ($submissionids) {
        [$insql, $params] = $DB->get_in_or_equal($submissionids);
        $gradeids = $DB->get_fieldset_select('videorubric_grade', 'id', "submissionid $insql", $params);
        if ($gradeids) {
            [$gsql, $gparams] = $DB->get_in_or_equal($gradeids);
            $DB->delete_records_select('videorubric_grade_criterion', "gradeid $gsql", $gparams);
        }
        $DB->delete_records_select('videorubric_grade', "submissionid $insql", $params);
        $DB->delete_records_select('videorubric_comment', "submissionid $insql", $params);
    }

    $criteriaids = $DB->get_fieldset_select('videorubric_criteria', 'id', 'videorubricid = ?', [$id]);
    if ($criteriaids) {
        [$csql, $cparams] = $DB->get_in_or_equal($criteriaids);
        $DB->delete_records_select('videorubric_levels', "criterionid $csql", $cparams);
    }

    $DB->delete_records('videorubric_criteria', ['videorubricid' => $id]);
    $DB->delete_records('videorubric_submission', ['videorubricid' => $id]);
    $DB->delete_records('videorubric', ['id' => $id]);

    videorubric_grade_item_delete($activity);
    return true;
}

/**
 * Serve protected submission and feedback files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function videorubric_pluginfile($course, $cm, $context, string $filearea, array $args,
        bool $forcedownload, array $options = []): bool {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_MODULE || $context->instanceid != $cm->id) {
        return false;
    }
    require_login($course, true, $cm);

    if (!in_array($filearea, ['intro', 'submission_video', 'feedback_audio'], true)) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($filearea === 'intro') {
        require_capability('mod/videorubric:view', $context);
        if ($itemid !== 0) {
            return false;
        }
    } else if ($filearea === 'submission_video') {
        $submission = $DB->get_record('videorubric_submission', ['id' => $itemid], '*', MUST_EXIST);
        \mod_videorubric\local\access::require_submission_access($submission, $cm, $context, $USER->id);
    } else {
        $grade = $DB->get_record('videorubric_grade', ['id' => $itemid], '*', MUST_EXIST);
        $submission = $DB->get_record('videorubric_submission', ['id' => $grade->submissionid], '*', MUST_EXIST);
        \mod_videorubric\local\access::require_feedback_access($submission, $cm, $context, $USER->id);
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_videorubric', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, [
        'cacheability' => 'private',
        'dontdie' => false,
    ]);
    return true;
}

/**
 * Create/update grade item.
 *
 * @param stdClass $activity
 * @param mixed $grades
 * @return int
 */
function videorubric_grade_item_update(stdClass $activity, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $activity->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademin' => 0,
        'grademax' => (float)($activity->grade ?? 100),
    ];
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/videorubric', $activity->course, 'mod', 'videorubric', $activity->id, 0, $grades, $params);
}

/**
 * Delete grade item.
 *
 * @param stdClass $activity
 * @return int
 */
function videorubric_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/videorubric', $activity->course, 'mod', 'videorubric', $activity->id, 0, null, ['deleted' => 1]);
}

/**
 * Push all or one user's grades to gradebook.
 *
 * @param stdClass $activity
 * @param int $userid
 * @param bool $nullifnone
 * @return void
 */
function videorubric_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;

    $params = [$activity->id];
    $usersql = '';
    if ($userid) {
        $usersql = ' AND s.userid = ?';
        $params[] = $userid;
    }

    $sql = "SELECT s.userid, g.finalscore AS rawgrade, g.timemodified AS dategraded
              FROM {videorubric_submission} s
              JOIN {videorubric_grade} g ON g.submissionid = s.id
             WHERE s.videorubricid = ? AND g.status = 'graded' $usersql";
    $rows = $DB->get_records_sql($sql, $params);
    $grades = [];
    foreach ($rows as $row) {
        $grades[$row->userid] = $row;
    }

    if ($userid && !$grades && $nullifnone) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => null];
    }
    videorubric_grade_item_update($activity, $grades);
}

/**
 * Gradebook callback.
 *
 * @param stdClass $activity
 * @param int $userid
 * @return stdClass|false
 */
function videorubric_get_user_grades(stdClass $activity, int $userid = 0) {
    global $DB;

    $params = [$activity->id];
    $usersql = '';
    if ($userid) {
        $usersql = ' AND s.userid = ?';
        $params[] = $userid;
    }
    $sql = "SELECT s.userid, g.finalscore AS rawgrade, g.timemodified AS dategraded
              FROM {videorubric_submission} s
              JOIN {videorubric_grade} g ON g.submissionid = s.id
             WHERE s.videorubricid = ? AND g.status = 'graded' $usersql";
    return $DB->get_records_sql($sql, $params);
}

/**
 * Return grading areas. We intentionally use our own rubric persistence/UI.
 *
 * @return array
 */
function videorubric_grading_areas_list(): array {
    return [];
}

/**
 * Reset form.
 *
 * @param moodleform $mform
 * @return void
 */
function videorubric_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'videorubricheader', get_string('modulenameplural', 'mod_videorubric'));
    $mform->addElement('advcheckbox', 'reset_videorubric_submissions', get_string('reset:submissions', 'mod_videorubric'));
    $mform->addElement('advcheckbox', 'reset_videorubric_grades', get_string('reset:grades', 'mod_videorubric'));
}

/**
 * Reset defaults.
 *
 * @param stdClass $course
 * @return array
 */
function videorubric_reset_course_form_defaults($course): array {
    return ['reset_videorubric_submissions' => 1, 'reset_videorubric_grades' => 1];
}

/**
 * Reset course data.
 *
 * @param stdClass $data
 * @return array
 */
function videorubric_reset_userdata($data): array {
    global $DB;

    $status = [];
    $activityids = $DB->get_fieldset_select('videorubric', 'id', 'course = ?', [$data->courseid]);
    if (!$activityids) {
        return $status;
    }

    [$insql, $params] = $DB->get_in_or_equal($activityids);
    $submissionids = $DB->get_fieldset_select('videorubric_submission', 'id', "videorubricid $insql", $params);
    if ($submissionids) {
        [$ssql, $sparams] = $DB->get_in_or_equal($submissionids);
        if (!empty($data->reset_videorubric_grades)) {
            $gradeids = $DB->get_fieldset_select('videorubric_grade', 'id', "submissionid $ssql", $sparams);
            if ($gradeids) {
                [$gsql, $gparams] = $DB->get_in_or_equal($gradeids);
                $DB->delete_records_select('videorubric_grade_criterion', "gradeid $gsql", $gparams);
            }
            $DB->delete_records_select('videorubric_grade', "submissionid $ssql", $sparams);
            $DB->delete_records_select('videorubric_comment', "submissionid $ssql", $sparams);
        }
        if (!empty($data->reset_videorubric_submissions)) {
            foreach ($submissionids as $submissionid) {
                $cm = null;
                // Files are removed below per module context.
            }
            $DB->delete_records_select('videorubric_submission', "id $ssql", $sparams);
        }
    }

    foreach ($activityids as $activityid) {
        if ($cm = get_coursemodule_from_instance('videorubric', $activityid, $data->courseid, false, IGNORE_MISSING)) {
            $context = context_module::instance($cm->id);
            $fs = get_file_storage();
            if (!empty($data->reset_videorubric_submissions)) {
                $fs->delete_area_files($context->id, 'mod_videorubric', 'submission_video');
            }
            if (!empty($data->reset_videorubric_grades)) {
                $fs->delete_area_files($context->id, 'mod_videorubric', 'feedback_audio');
            }
        }
        $activity = $DB->get_record('videorubric', ['id' => $activityid]);
        if ($activity) {
            if (!empty($data->reset_videorubric_grades) || !empty($data->reset_videorubric_submissions)) {
                videorubric_grade_item_update($activity, 'reset');
            }
        }
    }

    $status[] = [
        'component' => get_string('modulenameplural', 'mod_videorubric'),
        'item' => get_string('reset:done', 'mod_videorubric'),
        'error' => false,
    ];
    return $status;
}

/**
 * Add cached information, including custom completion rule availability.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function videorubric_get_coursemodule_info($coursemodule) {
    global $DB;

    $activity = $DB->get_record('videorubric', ['id' => $coursemodule->instance],
        'id,name,intro,introformat,duedate,completionsubmit,completiongraded,completionminenabled');
    if (!$activity) {
        return false;
    }
    $result = new cached_cm_info();
    $result->name = $activity->name;
    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('videorubric', $activity, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $activity->completionsubmit;
        $result->customdata['customcompletionrules']['completiongraded'] = $activity->completiongraded;
        $result->customdata['customcompletionrules']['completionmin'] = $activity->completionminenabled;
    }
    if ($activity->duedate) {
        $result->customdata['duedate'] = $activity->duedate;
    }
    return $result;
}

/**
 * Describe active custom completion rules.
 *
 * @param cm_info|stdClass $cm
 * @return array
 */
function mod_videorubric_get_completion_active_rule_descriptions($cm): array {
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }
    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $rule => $enabled) {
        if ($enabled) {
            $descriptions[] = get_string('completionrule:' . $rule, 'mod_videorubric');
        }
    }
    return $descriptions;
}
