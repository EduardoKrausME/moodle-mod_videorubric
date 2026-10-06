<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$PAGE->set_url('/mod/videorubric/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_videorubric'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('videorubric', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_videorubric'));
if (!$instances) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_videorubric')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('duedate', 'mod_videorubric')];
foreach ($instances as $instance) {
    $table->data[] = [
        html_writer::link(new moodle_url('/mod/videorubric/view.php', ['id' => $instance->coursemodule]), format_string($instance->name)),
        !empty($instance->duedate) ? userdate($instance->duedate) : get_string('none'),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
