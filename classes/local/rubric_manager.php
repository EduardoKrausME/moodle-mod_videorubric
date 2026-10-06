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
 * rubric_manager.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\local;

/**
 * Rubric definition management.
 */
final class rubric_manager {
    /**
     * Method get.
     *
     * @param int $activityid Parameter activityid.
     * @return array Return value.
     */
    public static function get(int $activityid): array {
        global $DB;

        $criteria = $DB->get_records('videorubric_criteria', ['videorubricid' => $activityid], 'sortorder, id');
        foreach ($criteria as $criterion) {
            $criterion->levels = array_values($DB->get_records('videorubric_levels',
                ['criterionid' => $criterion->id], 'sortorder, points, id'));
        }
        return array_values($criteria);
    }

    /**
     * Method save.
     *
     * @param int $activityid Parameter activityid.
     * @param array $criteria Parameter criteria.
     * @return void Return value.
     */
    public static function save(int $activityid, array $criteria): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $existing = $DB->get_records('videorubric_criteria', ['videorubricid' => $activityid]);
        $keepcriteria = [];

        foreach ($criteria as $index => $data) {
            $criterionid = !empty($data['id']) ? (int)$data['id'] : 0;
            if ($criterionid && isset($existing[$criterionid])) {
                $criterion = $existing[$criterionid];
                $criterion->name = clean_param($data['name'] ?? '', PARAM_TEXT);
                $criterion->description = clean_param($data['description'] ?? '', PARAM_TEXT);
                $criterion->weight = max(0, (float)($data['weight'] ?? 1));
                $criterion->sortorder = $index;
                $criterion->timemodified = time();
                $DB->update_record('videorubric_criteria', $criterion);
            } else {
                $criterion = (object)[
                    'videorubricid' => $activityid,
                    'name' => clean_param($data['name'] ?? '', PARAM_TEXT),
                    'description' => clean_param($data['description'] ?? '', PARAM_TEXT),
                    'weight' => max(0, (float)($data['weight'] ?? 1)),
                    'sortorder' => $index,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $criterion->id = $DB->insert_record('videorubric_criteria', $criterion);
            }
            $keepcriteria[] = $criterion->id;
            self::save_levels($criterion->id, $data['levels'] ?? []);
        }

        foreach ($existing as $criterion) {
            if (!in_array($criterion->id, $keepcriteria, true)) {
                $DB->delete_records('videorubric_levels', ['criterionid' => $criterion->id]);
                $DB->delete_records('videorubric_grade_criterion', ['criterionid' => $criterion->id]);
                $DB->delete_records('videorubric_criteria', ['id' => $criterion->id]);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Method save_levels.
     *
     * @param int $criterionid Parameter criterionid.
     * @param array $levels Parameter levels.
     * @return void Return value.
     */
    private static function save_levels(int $criterionid, array $levels): void {
        global $DB;

        $existing = $DB->get_records('videorubric_levels', ['criterionid' => $criterionid]);
        $keep = [];
        foreach ($levels as $index => $data) {
            $id = !empty($data['id']) ? (int)$data['id'] : 0;
            if ($id && isset($existing[$id])) {
                $level = $existing[$id];
                $level->label = clean_param($data['label'] ?? '', PARAM_TEXT);
                $level->description = clean_param($data['description'] ?? '', PARAM_TEXT);
                $level->points = (float)($data['points'] ?? 0);
                $level->sortorder = $index;
                $DB->update_record('videorubric_levels', $level);
            } else {
                $level = (object)[
                    'criterionid' => $criterionid,
                    'label' => clean_param($data['label'] ?? '', PARAM_TEXT),
                    'description' => clean_param($data['description'] ?? '', PARAM_TEXT),
                    'points' => (float)($data['points'] ?? 0),
                    'sortorder' => $index,
                ];
                $level->id = $DB->insert_record('videorubric_levels', $level);
            }
            $keep[] = $level->id;
        }
        foreach ($existing as $level) {
            if (!in_array($level->id, $keep, true)) {
                $DB->delete_records('videorubric_grade_criterion', ['levelid' => $level->id]);
                $DB->delete_records('videorubric_levels', ['id' => $level->id]);
            }
        }
    }
}
