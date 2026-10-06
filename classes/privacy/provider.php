<?php
namespace mod_videorubric\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Video Rubric.
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videorubric_submission', [
            'userid' => 'privacy:metadata:submission:userid',
            'status' => 'privacy:metadata:submission:status',
            'duration' => 'privacy:metadata:submission:duration',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'timesubmitted' => 'privacy:metadata:submission:timesubmitted',
        ], 'privacy:metadata:submission');
        $collection->add_database_table('videorubric_grade', [
            'graderid' => 'privacy:metadata:grade:graderid',
            'finalscore' => 'privacy:metadata:grade:finalscore',
            'feedbacktext' => 'privacy:metadata:grade:feedbacktext',
            'timegraded' => 'privacy:metadata:grade:timegraded',
        ], 'privacy:metadata:grade');
        $collection->add_database_table('videorubric_comment', [
            'graderid' => 'privacy:metadata:comment:graderid',
            'timeposition' => 'privacy:metadata:comment:timeposition',
            'commenttext' => 'privacy:metadata:comment:commenttext',
        ], 'privacy:metadata:comment');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:files');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videorubric} v ON v.id = cm.instance
             LEFT JOIN {videorubric_submission} s ON s.videorubricid = v.id
             LEFT JOIN {videorubric_grade} g ON g.submissionid = s.id
             LEFT JOIN {videorubric_comment} c ON c.submissionid = s.id
                 WHERE s.userid = :submissionuser OR g.graderid = :gradeuser OR c.graderid = :commentuser";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videorubric',
            'submissionuser' => $userid,
            'gradeuser' => $userid,
            'commentuser' => $userid,
        ]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videorubric', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $submission = $DB->get_record('videorubric_submission', [
                'videorubricid' => $cm->instance,
                'userid' => $userid,
            ]);
            if ($submission) {
                $data = (object)[
                    'status' => $submission->status,
                    'duration' => $submission->duration,
                    'created' => transform::datetime($submission->timecreated),
                    'modified' => transform::datetime($submission->timemodified),
                    'submitted' => $submission->timesubmitted ? transform::datetime($submission->timesubmitted) : null,
                ];
                $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id]);
                if ($grade) {
                    $data->grade = (object)[
                        'status' => $grade->status,
                        'finalscore' => $grade->finalscore,
                        'feedbacktext' => $grade->feedbacktext,
                        'timegraded' => $grade->timegraded ? transform::datetime($grade->timegraded) : null,
                    ];
                }
                $data->comments = array_values($DB->get_records('videorubric_comment',
                    ['submissionid' => $submission->id], 'timeposition, id', 'id,timeposition,commenttext,timecreated'));
                writer::with_context($context)->export_data([get_string('submission', 'mod_videorubric')], $data);
                writer::with_context($context)->export_area_files([get_string('submission', 'mod_videorubric')],
                    'mod_videorubric', 'submission_video', $submission->id);
            }

            $authoredcomments = $DB->get_records_sql(
                "SELECT c.* FROM {videorubric_comment} c
                  JOIN {videorubric_submission} s ON s.id = c.submissionid
                 WHERE s.videorubricid = ? AND c.graderid = ?", [$cm->instance, $userid]);
            if ($authoredcomments) {
                writer::with_context($context)->export_data([get_string('authoredcomments', 'mod_videorubric')],
                    array_values($authoredcomments));
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videorubric', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $submissionids = $DB->get_fieldset_select('videorubric_submission', 'id', 'videorubricid = ?', [$cm->instance]);
        if ($submissionids) {
            self::delete_submission_ids($context, $submissionids);
        }
        get_file_storage()->delete_area_files($context->id, 'mod_videorubric');
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_user_in_context($context, $userid);
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videorubric', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userlist->add_from_sql('userid',
            'SELECT userid FROM {videorubric_submission} WHERE videorubricid = :vid', ['vid' => $cm->instance]);
        $userlist->add_from_sql('userid',
            "SELECT g.graderid AS userid
               FROM {videorubric_grade} g
               JOIN {videorubric_submission} s ON s.id = g.submissionid
              WHERE s.videorubricid = :vid", ['vid' => $cm->instance]);
        $userlist->add_from_sql('userid',
            "SELECT c.graderid AS userid
               FROM {videorubric_comment} c
               JOIN {videorubric_submission} s ON s.id = c.submissionid
              WHERE s.videorubricid = :vid", ['vid' => $cm->instance]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_in_context($userlist->get_context(), $userid);
        }
    }

    private static function delete_user_in_context(\context $context, int $userid): void {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videorubric', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $ownids = $DB->get_fieldset_select('videorubric_submission', 'id', 'videorubricid = ? AND userid = ?',
            [$cm->instance, $userid]);
        if ($ownids) {
            self::delete_submission_ids($context, $ownids);
        }

        $gradeids = $DB->get_fieldset_sql(
            "SELECT g.id
               FROM {videorubric_grade} g
               JOIN {videorubric_submission} s ON s.id = g.submissionid
              WHERE s.videorubricid = ? AND g.graderid = ?", [$cm->instance, $userid]);
        foreach ($gradeids as $gradeid) {
            $DB->delete_records('videorubric_grade_criterion', ['gradeid' => $gradeid]);
            $DB->delete_records('videorubric_grade', ['id' => $gradeid]);
            get_file_storage()->delete_area_files($context->id, 'mod_videorubric', 'feedback_audio', $gradeid);
        }
        $DB->delete_records_select('videorubric_comment',
            'graderid = ? AND submissionid IN (SELECT id FROM {videorubric_submission} WHERE videorubricid = ?)',
            [$userid, $cm->instance]);
    }

    private static function delete_submission_ids(\context_module $context, array $submissionids): void {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($submissionids);
        $gradeids = $DB->get_fieldset_select('videorubric_grade', 'id', "submissionid $insql", $params);
        if ($gradeids) {
            [$gsql, $gparams] = $DB->get_in_or_equal($gradeids);
            $DB->delete_records_select('videorubric_grade_criterion', "gradeid $gsql", $gparams);
            foreach ($gradeids as $gradeid) {
                get_file_storage()->delete_area_files($context->id, 'mod_videorubric', 'feedback_audio', $gradeid);
            }
        }
        $DB->delete_records_select('videorubric_grade', "submissionid $insql", $params);
        $DB->delete_records_select('videorubric_comment', "submissionid $insql", $params);
        foreach ($submissionids as $submissionid) {
            get_file_storage()->delete_area_files($context->id, 'mod_videorubric', 'submission_video', $submissionid);
        }
        $DB->delete_records_select('videorubric_submission', "id $insql", $params);
    }
}
