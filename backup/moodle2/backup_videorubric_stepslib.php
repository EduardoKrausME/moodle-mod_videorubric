<?php

defined('MOODLE_INTERNAL') || die();

class backup_videorubric_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videorubric', ['id'], [
            'course', 'name', 'intro', 'introformat', 'allowupload', 'allowrecording', 'maxbytes', 'maxduration',
            'duedate', 'grade', 'useweights', 'completionsubmit', 'completiongraded', 'completionminenabled',
            'completionmingrade', 'timecreated', 'timemodified',
        ]);
        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', ['id'], [
            'name', 'description', 'weight', 'sortorder', 'timecreated', 'timemodified',
        ]);
        $levels = new backup_nested_element('levels');
        $level = new backup_nested_element('level', ['id'], ['label', 'description', 'points', 'sortorder']);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'status', 'duration', 'timecreated', 'timemodified', 'timesubmitted',
        ]);
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'graderid', 'status', 'rawscore', 'finalscore', 'feedbacktext', 'timecreated', 'timemodified', 'timegraded',
        ]);
        $gradeselections = new backup_nested_element('gradeselections');
        $gradeselection = new backup_nested_element('gradeselection', ['id'], [
            'criterionid', 'levelid', 'points', 'timemodified',
        ]);
        $comments = new backup_nested_element('comments');
        $comment = new backup_nested_element('comment', ['id'], [
            'graderid', 'timeposition', 'commenttext', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($criteria);
        $criteria->add_child($criterion);
        $criterion->add_child($levels);
        $levels->add_child($level);
        $activity->add_child($submissions);
        $submissions->add_child($submission);
        $submission->add_child($grades);
        $grades->add_child($grade);
        $grade->add_child($gradeselections);
        $gradeselections->add_child($gradeselection);
        $submission->add_child($comments);
        $comments->add_child($comment);

        $activity->set_source_table('videorubric', ['id' => backup::VAR_ACTIVITYID]);
        $criterion->set_source_table('videorubric_criteria', ['videorubricid' => backup::VAR_PARENTID], 'sortorder, id');
        $level->set_source_table('videorubric_levels', ['criterionid' => backup::VAR_PARENTID], 'sortorder, id');

        if ($userinfo) {
            $submission->set_source_table('videorubric_submission', ['videorubricid' => backup::VAR_PARENTID], 'id');
            $grade->set_source_table('videorubric_grade', ['submissionid' => backup::VAR_PARENTID], 'id');
            $gradeselection->set_source_table('videorubric_grade_criterion', ['gradeid' => backup::VAR_PARENTID], 'id');
            $comment->set_source_table('videorubric_comment', ['submissionid' => backup::VAR_PARENTID], 'timeposition, id');

            $submission->annotate_ids('user', 'userid');
            $grade->annotate_ids('user', 'graderid');
            $comment->annotate_ids('user', 'graderid');
            $submission->annotate_files('mod_videorubric', 'submission_video', 'id');
            $grade->annotate_files('mod_videorubric', 'feedback_audio', 'id');
        }

        $activity->annotate_files('mod_videorubric', 'intro', null);
        return $this->prepare_activity_structure($activity);
    }
}
