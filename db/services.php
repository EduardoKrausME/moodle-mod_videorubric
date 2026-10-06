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
 * services.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_videorubric_save_grade_level' => [
        'classname' => 'mod_videorubric\\external\\save_grade_level',
        'description' => 'Save a rubric level selection and recalculate the grade.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videorubric:grade',
    ],
    'mod_videorubric_save_feedback_text' => [
        'classname' => 'mod_videorubric\\external\\save_feedback_text',
        'description' => 'Save textual feedback while grading.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videorubric:grade',
    ],
    'mod_videorubric_add_comment' => [
        'classname' => 'mod_videorubric\\external\\add_comment',
        'description' => 'Add a timestamped comment.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videorubric:grade',
    ],
    'mod_videorubric_delete_comment' => [
        'classname' => 'mod_videorubric\\external\\delete_comment',
        'description' => 'Delete a timestamped comment.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videorubric:grade',
    ],
    'mod_videorubric_finalize_grade' => [
        'classname' => 'mod_videorubric\\external\\finalize_grade',
        'description' => 'Finalize grading and push the grade to gradebook.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videorubric:grade',
    ],
];
