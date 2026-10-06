<?php

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
