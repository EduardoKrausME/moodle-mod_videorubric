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
 * Rubric management page.
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videorubric', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videorubric', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videorubric:managerubric', $context);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $criteria = $_POST['criteria'] ?? [];
    if (!is_array($criteria)) {
        throw new invalid_parameter_exception('Invalid rubric data');
    }

    $clean = [];
    foreach ($criteria as $criterion) {
        if (!is_array($criterion) || trim((string)($criterion['name'] ?? '')) === '') {
            continue;
        }
        $levels = [];
        foreach (($criterion['levels'] ?? []) as $level) {
            if (!is_array($level) || trim((string)($level['label'] ?? '')) === '') {
                continue;
            }
            $levels[] = [
                'id' => (int)($level['id'] ?? 0),
                'label' => (string)($level['label'] ?? ''),
                'description' => (string)($level['description'] ?? ''),
                'points' => (float)($level['points'] ?? 0),
            ];
        }
        $clean[] = [
            'id' => (int)($criterion['id'] ?? 0),
            'name' => (string)($criterion['name'] ?? ''),
            'description' => (string)($criterion['description'] ?? ''),
            'weight' => (float)($criterion['weight'] ?? 1),
            'levels' => $levels,
        ];
    }

    \mod_videorubric\local\rubric_manager::save($activity->id, $clean);
    redirect(
        new moodle_url('/mod/videorubric/rubric.php', ['id' => $cm->id]),
        get_string('rubricsaved', 'mod_videorubric')
    );
}

$criteria = \mod_videorubric\local\rubric_manager::get($activity->id);
if (!$criteria) {
    $criteria = [
        (object)[
            'id' => 0,
            'name' => '',
            'description' => '',
            'weight' => 1,
            'levels' => [
                (object)[
                    'id' => 0,
                    'label' => get_string('levelneedswork', 'mod_videorubric'),
                    'description' => '',
                    'points' => 0,
                ],
                (object)[
                    'id' => 0,
                    'label' => get_string('levelgood', 'mod_videorubric'),
                    'description' => '',
                    'points' => 1,
                ],
            ],
        ],
    ];
}

$templatecriteria = [];
foreach ($criteria as $criterionindex => $criterion) {
    $levels = [];
    foreach ($criterion->levels as $levelindex => $level) {
        $levels[] = [
            'criterionindex' => $criterionindex,
            'index' => $levelindex,
            'id' => (int)$level->id,
            'label' => $level->label,
            'description' => $level->description,
            'points' => (string)$level->points,
        ];
    }
    $templatecriteria[] = [
        'index' => $criterionindex,
        'id' => (int)$criterion->id,
        'name' => $criterion->name,
        'description' => $criterion->description,
        'weight' => (string)$criterion->weight,
        'levels' => $levels,
    ];
}

$PAGE->set_url('/mod/videorubric/rubric.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managerubric', 'mod_videorubric'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/videorubric/styles.css');
$PAGE->requires->js_call_amd('mod_videorubric/rubric_editor', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videorubric/rubric', [
    'sesskey' => sesskey(),
    'criteria' => $templatecriteria,
]);
echo $OUTPUT->footer();
