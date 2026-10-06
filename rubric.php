<?php
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
    redirect(new moodle_url('/mod/videorubric/rubric.php', ['id' => $cm->id]), get_string('rubricsaved', 'mod_videorubric'));
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
                (object)['id' => 0, 'label' => get_string('levelneedswork', 'mod_videorubric'), 'description' => '', 'points' => 0],
                (object)['id' => 0, 'label' => get_string('levelgood', 'mod_videorubric'), 'description' => '', 'points' => 1],
            ],
        ],
    ];
}

$PAGE->set_url('/mod/videorubric/rubric.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managerubric', 'mod_videorubric'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/videorubric/styles.css');
$PAGE->requires->js_call_amd('mod_videorubric/rubric_editor', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managerubric', 'mod_videorubric'));
echo html_writer::tag('p', get_string('rubrichelp', 'mod_videorubric'), ['class' => 'text-muted']);
?>
<form method="post" id="videorubric-rubric-form">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <div id="videorubric-criteria">
        <?php foreach ($criteria as $ci => $criterion): ?>
        <section class="card mb-3 videorubric-criterion" data-index="<?php echo $ci; ?>">
            <div class="card-body">
                <input type="hidden" name="criteria[<?php echo $ci; ?>][id]" value="<?php echo (int)$criterion->id; ?>">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1">
                        <label class="form-label"><?php echo get_string('criterion', 'mod_videorubric'); ?></label>
                        <input class="form-control mb-2" name="criteria[<?php echo $ci; ?>][name]" value="<?php echo s($criterion->name); ?>" required>
                        <textarea class="form-control mb-2" name="criteria[<?php echo $ci; ?>][description]" rows="2" placeholder="<?php echo s(get_string('description')); ?>"><?php echo s($criterion->description); ?></textarea>
                        <label class="form-label"><?php echo get_string('weight', 'mod_videorubric'); ?></label>
                        <input class="form-control videorubric-weight" type="number" min="0" step="0.01" name="criteria[<?php echo $ci; ?>][weight]" value="<?php echo s((string)$criterion->weight); ?>">
                    </div>
                    <button type="button" class="btn btn-outline-danger videorubric-remove-criterion"><?php echo get_string('remove'); ?></button>
                </div>
                <div class="videorubric-levels mt-3">
                    <?php foreach ($criterion->levels as $li => $level): ?>
                    <div class="videorubric-level border rounded p-3 mb-2 d-grid gap-2" data-level-index="<?php echo $li; ?>">
                        <input type="hidden" name="criteria[<?php echo $ci; ?>][levels][<?php echo $li; ?>][id]" value="<?php echo (int)$level->id; ?>">
                        <input class="form-control" name="criteria[<?php echo $ci; ?>][levels][<?php echo $li; ?>][label]" value="<?php echo s($level->label); ?>" placeholder="<?php echo s(get_string('level', 'mod_videorubric')); ?>" required>
                        <textarea class="form-control" name="criteria[<?php echo $ci; ?>][levels][<?php echo $li; ?>][description]" rows="2" placeholder="<?php echo s(get_string('description')); ?>"><?php echo s($level->description); ?></textarea>
                        <div class="d-flex gap-2">
                            <input class="form-control" type="number" step="0.01" name="criteria[<?php echo $ci; ?>][levels][<?php echo $li; ?>][points]" value="<?php echo s((string)$level->points); ?>" placeholder="<?php echo s(get_string('points', 'mod_videorubric')); ?>" required>
                            <button type="button" class="btn btn-outline-secondary videorubric-remove-level"><?php echo get_string('remove'); ?></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-primary videorubric-add-level"><?php echo get_string('addlevel', 'mod_videorubric'); ?></button>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-secondary" id="videorubric-add-criterion"><?php echo get_string('addcriterion', 'mod_videorubric'); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo get_string('savechanges'); ?></button>
    </div>
</form>
<?php
echo $OUTPUT->footer();
