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
 * grading_manager.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\local;

/**
 * Grading workflow.
 */
final class grading_manager {
    /**
     * Method get_or_create_grade.
     *
     * @param \stdClass $submission Parameter submission.
     * @param int $graderid Parameter graderid.
     * @return \stdClass Return value.
     */
    public static function get_or_create_grade(\stdClass $submission, int $graderid): \stdClass {
        global $DB;

        $grade = $DB->get_record('videorubric_grade', ['submissionid' => $submission->id]);
        if (!$grade) {
            $now = time();
            $grade = (object)[
                'submissionid' => $submission->id,
                'graderid' => $graderid,
                'status' => 'grading',
                'rawscore' => 0,
                'finalscore' => 0,
                'feedbacktext' => '',
                'timecreated' => $now,
                'timemodified' => $now,
                'timegraded' => 0,
            ];
            $grade->id = $DB->insert_record('videorubric_grade', $grade);
        }
        return $grade;
    }

    /**
     * Method select_level.
     *
     * @param \stdClass $submission Parameter submission.
     * @param int $criterionid Parameter criterionid.
     * @param int $levelid Parameter levelid.
     * @param int $graderid Parameter graderid.
     * @return \stdClass Return value.
     */
    public static function select_level(\stdClass $submission, int $criterionid, int $levelid, int $graderid): \stdClass {
        global $DB;

        $criterion = $DB->get_record('videorubric_criteria', [
            'id' => $criterionid,
            'videorubricid' => $submission->videorubricid,
        ], '*', MUST_EXIST);
        $level = $DB->get_record('videorubric_levels', ['id' => $levelid, 'criterionid' => $criterionid], '*', MUST_EXIST);
        $grade = self::get_or_create_grade($submission, $graderid);

        $existing = $DB->get_record('videorubric_grade_criterion', [
            'gradeid' => $grade->id,
            'criterionid' => $criterionid,
        ]);
        $record = (object)[
            'gradeid' => $grade->id,
            'criterionid' => $criterionid,
            'levelid' => $levelid,
            'points' => $level->points,
            'timemodified' => time(),
        ];
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('videorubric_grade_criterion', $record);
        } else {
            $DB->insert_record('videorubric_grade_criterion', $record);
        }

        self::recalculate($submission, $grade);
        return $DB->get_record('videorubric_grade', ['id' => $grade->id], '*', MUST_EXIST);
    }

    /**
     * Method save_feedback.
     *
     * @param \stdClass $submission Parameter submission.
     * @param string $feedback Parameter feedback.
     * @param int $graderid Parameter graderid.
     * @return \stdClass Return value.
     */
    public static function save_feedback(\stdClass $submission, string $feedback, int $graderid): \stdClass {
        global $DB;
        $grade = self::get_or_create_grade($submission, $graderid);
        $grade->feedbacktext = clean_param($feedback, PARAM_TEXT);
        $grade->graderid = $graderid;
        $grade->timemodified = time();
        $DB->update_record('videorubric_grade', $grade);
        return $grade;
    }

    /**
     * Method add_comment.
     *
     * @param \stdClass $submission Parameter submission.
     * @param int $seconds Parameter seconds.
     * @param string $comment Parameter comment.
     * @param int $graderid Parameter graderid.
     * @param \context_module $context Parameter context.
     * @return \stdClass Return value.
     */
    public static function add_comment(\stdClass $submission, int $seconds, string $comment, int $graderid,
            \context_module $context): \stdClass {
        global $DB;
        $record = (object)[
            'submissionid' => $submission->id,
            'graderid' => $graderid,
            'timeposition' => max(0, $seconds),
            'commenttext' => clean_param($comment, PARAM_TEXT),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $record->id = $DB->insert_record('videorubric_comment', $record);
        $event = \mod_videorubric\event\temporal_comment_created::create([
            'objectid' => $record->id,
            'context' => $context,
            'relateduserid' => $submission->userid,
            'other' => ['submissionid' => $submission->id, 'timeposition' => $record->timeposition],
        ]);
        $event->trigger();
        return $record;
    }

    /**
     * Method finalize.
     *
     * @param \stdClass $submission Parameter submission.
     * @param int $graderid Parameter graderid.
     * @param \stdClass $cm Parameter cm.
     * @param \context_module $context Parameter context.
     * @return \stdClass Return value.
     */
    public static function finalize(\stdClass $submission, int $graderid, \stdClass $cm,
            \context_module $context): \stdClass {
        global $DB;

        $grade = self::get_or_create_grade($submission, $graderid);
        self::recalculate($submission, $grade);
        $grade = $DB->get_record('videorubric_grade', ['id' => $grade->id], '*', MUST_EXIST);
        $criteria = $DB->count_records('videorubric_criteria', ['videorubricid' => $submission->videorubricid]);
        $selected = $DB->count_records('videorubric_grade_criterion', ['gradeid' => $grade->id]);
        if ($criteria > 0 && $selected < $criteria) {
            throw new \moodle_exception('error:incompleterubric', 'mod_videorubric');
        }

        $now = time();
        $grade->status = 'graded';
        $grade->graderid = $graderid;
        $grade->timegraded = $now;
        $grade->timemodified = $now;
        $DB->update_record('videorubric_grade', $grade);

        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        videorubric_update_grades($activity, $submission->userid, true);

        $event = \mod_videorubric\event\submission_graded::create([
            'objectid' => $grade->id,
            'context' => $context,
            'relateduserid' => $submission->userid,
            'other' => ['submissionid' => $submission->id, 'finalscore' => $grade->finalscore],
        ]);
        $event->trigger();

        $completion = new \completion_info(get_course($cm->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $submission->userid);
        }
        return $grade;
    }

    /**
     * Method get_feedback_audio_url.
     *
     * @param \context_module $context Parameter context.
     * @param int $gradeid Parameter gradeid.
     * @return ?\moodle_url Return value.
     */
    public static function get_feedback_audio_url(\context_module $context, int $gradeid): ?\moodle_url {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_videorubric', 'feedback_audio', $gradeid, 'id DESC', false);
        if (!$files) {
            return null;
        }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url($context->id, 'mod_videorubric', 'feedback_audio',
            $gradeid, $file->get_filepath(), $file->get_filename(), false);
    }

    /**
     * Method recalculate.
     *
     * @param \stdClass $submission Parameter submission.
     * @param \stdClass $grade Parameter grade.
     * @return void Return value.
     */
    public static function recalculate(\stdClass $submission, \stdClass $grade): void {
        global $DB;

        $activity = $DB->get_record('videorubric', ['id' => $submission->videorubricid], '*', MUST_EXIST);
        $criteria = rubric_manager::get($activity->id);
        $selected = $DB->get_records('videorubric_grade_criterion', ['gradeid' => $grade->id]);
        $selectedbycriterion = [];
        foreach ($selected as $row) {
            $selectedbycriterion[$row->criterionid] = $row;
        }

        $raw = 0.0;
        $max = 0.0;
        if (!empty($activity->useweights)) {
            $weighttotal = 0.0;
            $weighted = 0.0;
            foreach ($criteria as $criterion) {
                $criterionmax = 0.0;
                foreach ($criterion->levels as $level) {
                    $criterionmax = max($criterionmax, (float)$level->points);
                }
                $weight = max(0.0, (float)$criterion->weight);
                if ($criterionmax > 0 && $weight > 0) {
                    $points = isset($selectedbycriterion[$criterion->id])
                        ? (float)$selectedbycriterion[$criterion->id]->points
                        : 0.0;
                    $weighted += ($points / $criterionmax) * $weight;
                    $weighttotal += $weight;
                }
            }
            $ratio = $weighttotal > 0 ? $weighted / $weighttotal : 0.0;
            $raw = $weighted;
            $max = $weighttotal;
        } else {
            foreach ($criteria as $criterion) {
                $criterionmax = 0.0;
                foreach ($criterion->levels as $level) {
                    $criterionmax = max($criterionmax, (float)$level->points);
                }
                $max += $criterionmax;
                if (isset($selectedbycriterion[$criterion->id])) {
                    $raw += (float)$selectedbycriterion[$criterion->id]->points;
                }
            }
            $ratio = $max > 0 ? $raw / $max : 0.0;
        }

        $final = max(0.0, min((float)$activity->grade, $ratio * (float)$activity->grade));
        $DB->update_record('videorubric_grade', (object)[
            'id' => $grade->id,
            'rawscore' => $raw,
            'finalscore' => $final,
            'graderid' => $grade->graderid,
            'timemodified' => time(),
        ]);
    }
}
