<?php
namespace mod_videorubric\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Rubric definition management.
 */
final class rubric_manager {
    public static function get(int $activityid): array {
        global $DB;

        $criteria = $DB->get_records('videorubric_criteria', ['videorubricid' => $activityid], 'sortorder, id');
        foreach ($criteria as $criterion) {
            $criterion->levels = array_values($DB->get_records('videorubric_levels',
                ['criterionid' => $criterion->id], 'sortorder, points, id'));
        }
        return array_values($criteria);
    }

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
