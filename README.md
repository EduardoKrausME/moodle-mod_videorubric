# Video Rubric

Video Rubric is a Moodle activity for video submission and assessment where the grading experience is built around the video itself. The teacher watches the student's video on the left while a persistent rubric remains available on the right, so selecting rubric levels, writing feedback and adding timestamped comments never requires leaving the player or reloading the page.

The repository/plugin name is `moodle-mod_videorubric` and the Moodle component is `mod_videorubric`.

## Student workflow

A student opens the activity and can either upload a supported video file or record a new video directly in the browser with `MediaRecorder`, depending on the activity settings. Browser recordings use the student's camera and microphone, can be previewed before they are saved and can be replaced while the submission is still a draft.

The submission stays in `draft` state until the student explicitly sends the final version. Once submitted, the video is locked for the student and becomes available in the teacher grading queue. This distinction is intentional: recording something does not silently turn it into a final submission.

Video files are stored with Moodle File API in the module context. File delivery is handled by `videorubric_pluginfile()` and is not based on knowing or hiding a URL. Every request is authorised against the submission owner, module context, grader capability and group restrictions.

## Grading workflow

The grading page is the main feature of the plugin and uses a two-column workspace.

The left side contains the video player, playback speed, current timestamp and duration. Below the player the teacher can add comments to the exact current position, for example `01:24 — incorrect hand position`. Clicking a saved timestamp seeks the player back to that moment.

The right side remains visible while the teacher works and contains the activity rubric, current score, text feedback and audio feedback. Selecting a rubric level immediately persists that choice through Moodle AJAX services and recalculates the score without reloading the page or stopping video playback. Text feedback uses delayed autosave so normal typing does not generate a request for every keystroke.

Audio feedback is recorded with `MediaRecorder`. The teacher can record, stop, preview and record again before saving. Saving replaces the previous audio feedback for that evaluation rather than creating an uncontrolled collection of recordings.

When the teacher finalises the evaluation, the grade is pushed to the Moodle gradebook and completion is recalculated. The queue includes previous/next student navigation and the submission list can be filtered by group and status.

## Rubric model

Video Rubric intentionally owns its rubric definition instead of depending on the standard `mod_assign` grading screen. Each activity can define criteria, descriptions, levels, points and optional criterion weights.

Moodle's Advanced Grading API was considered because its rubric subsystem is useful when an activity wants the standard grading form lifecycle. In this plugin, however, the primary requirement is a persistent side-by-side video workspace with granular AJAX persistence and custom timestamp/audio feedback. Coupling the workflow to the standard advanced-grading form would make the core user experience harder rather than simpler.

The plugin therefore reuses Moodle where the core APIs are a clean fit — gradebook, File API, Groups, completion, Privacy and Backup/Restore — while keeping rubric persistence and the grading workspace inside `mod_videorubric`.

The final grade is scaled to the activity maximum grade. Without weights, the selected points are divided by the sum of each criterion's maximum level. With weights enabled, each criterion is first normalised against its own maximum and then contributes according to its configured weight.

## Submission and evaluation status

The teacher queue exposes four operational states:

- **Not submitted**: no final submission exists. A private draft may exist, but it is not exposed to graders.
- **Submitted**: the student sent a final video and grading has not started.
- **In grading**: a grader has started saving rubric selections or feedback but has not finalised the evaluation.
- **Graded**: the evaluation was finalised and the grade was pushed to gradebook.

A teacher opening a final submission creates the grading working record, so subsequent saved work remains available even before finalisation.

## Security boundaries

Submission IDs are never treated as authorisation. Access is validated centrally in `classes/local/access.php` and reused by grading services and protected file delivery.

A student can only access the video attached to their own submission. Changing `submissionid`, `itemid`, user IDs or a pluginfile URL does not grant access to another student's file.

A grader must have `mod/videorubric:grade` in the module context. Under separate groups, the target student must also belong to one of the grader's allowed activity groups unless the grader has the explicit all-groups capability. Draft videos are not exposed to graders.

AJAX grading calls validate the Moodle context and repeat the same submission/group checks server-side. Binary recording endpoints require login, `sesskey`, the appropriate capability and a submission belonging to the current activity. Uploaded video extensions, MIME types, activity size limit and optional duration limit are validated before File API persistence.

## Completion

The activity can use three independent custom completion requirements:

- final video submitted;
- evaluation received;
- optional minimum final grade.

The rules are evaluated from the submission/evaluation records and are refreshed when the student submits or the teacher finalises grading.

## Moodle integrations

The plugin includes:

- gradebook integration;
- Moodle File API for submission videos and audio feedback;
- Groups and separate-group enforcement;
- custom activity completion rules;
- Privacy API export/deletion support;
- Moodle 2 Backup/Restore structure;
- course reset support;
- events for submission creation, final submission, timestamped feedback and grading;
- capabilities for submission, grading, rubric management and all-groups access;
- Mustache grading/submission interfaces;
- AMD modules for grading, video recording and rubric editing;
- AJAX external functions for rubric choices, feedback, comments and finalisation.

## Main code paths

`submission.php` handles the student's draft/final workflow, while browser recordings are uploaded through `ajax/upload_video.php` and persisted by `classes/local/submission_manager.php`.

`submissions.php` is the teacher queue with status/group filtering. `grade.php` builds the correction workspace and delegates asynchronous updates to the classes under `classes/external/`. `classes/local/grading_manager.php` owns score calculation, grade state and gradebook finalisation.

`rubric.php` manages the activity-specific rubric, backed by `videorubric_criteria` and `videorubric_levels`.

`lib.php` contains Moodle callbacks such as activity lifecycle, File API delivery, gradebook integration, completion cache data and course reset; business rules stay in autoloaded classes instead of accumulating in the callback file.

## Supported video submission formats

The initial implementation accepts MP4, WebM, MOV and M4V uploads. Browser recording normally produces WebM/Opus or MP4 depending on browser support. The effective upload limit is also constrained by the PHP/web server and Moodle site limits.

## Current development status

The initial release is marked alpha because the plugin introduces a complete activity data model and grading workflow and should be exercised against the exact Moodle/PHP/browser matrix used in production before being treated as stable. The data model and API boundaries are designed so the grading UI can evolve without turning the activity into a wrapper around `mod_assign`.
