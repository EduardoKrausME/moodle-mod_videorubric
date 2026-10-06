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
 * videorubric.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addcomment'] = 'Add comment';
$string['addcriterion'] = 'Add criterion';
$string['addlevel'] = 'Add level';
$string['allowrecording'] = 'Allow browser recording';
$string['allowupload'] = 'Allow video upload';
$string['audiofeedback'] = 'Audio feedback';
$string['authoredcomments'] = 'Authored grading comments';
$string['backtosubmissions'] = 'Back to submissions';
$string['completion:graded'] = 'Student must receive an evaluation';
$string['completion:minenable'] = 'Student must receive at least this grade:';
$string['completion:submit'] = 'Student must submit a final video';
$string['completionrule:completiongraded'] = 'Receive an evaluation';
$string['completionrule:completionmin'] = 'Reach the configured minimum grade';
$string['completionrule:completionminvalue'] = 'Receive a grade of at least {$a}';
$string['completionrule:completionsubmit'] = 'Submit a final video';
$string['criterion'] = 'Criterion';
$string['currenttimestamp'] = 'Current time';
$string['draftsaved'] = 'Draft saved.';
$string['duedate'] = 'Due date';
$string['duration'] = 'Duration';
$string['enablecamera'] = 'Enable camera';
$string['error:audiofilesize'] = 'The audio feedback file is too large.';
$string['error:audiotype'] = 'Unsupported audio feedback format.';
$string['error:duration'] = 'The video exceeds the configured maximum duration.';
$string['error:filesize'] = 'The video exceeds the activity upload limit.';
$string['error:incompleterubric'] = 'Select one level for every rubric criterion before finalizing the evaluation.';
$string['error:nonnegative'] = 'The value cannot be negative.';
$string['error:novideo'] = 'A video must be saved before final submission.';
$string['error:positivegrade'] = 'Maximum grade must be greater than zero.';
$string['error:submissionmethod'] = 'Enable at least one submission method.';
$string['error:upload'] = 'The file could not be uploaded.';
$string['error:videotype'] = 'Unsupported video format. Use MP4, WebM, MOV or M4V.';
$string['event:submissioncreated'] = 'Video submission created';
$string['event:submissiongraded'] = 'Video submission graded';
$string['event:submissionsubmitted'] = 'Video submission submitted';
$string['event:temporalcommentcreated'] = 'Timestamped comment created';
$string['feedback'] = 'Feedback';
$string['feedbacknotavailable'] = 'Feedback is not available yet.';
$string['finalizegrade'] = 'Finalize evaluation';
$string['finalscore'] = 'Final score';
$string['gradingoptions'] = 'Grading options';
$string['gradingstudent'] = 'Grading {$a}';
$string['invalidsubmission'] = 'Invalid submission.';
$string['level'] = 'Level';
$string['levelgood'] = 'Good';
$string['levelneedswork'] = 'Needs work';
$string['managerubric'] = 'Manage rubric';
$string['maxduration'] = 'Maximum recording duration (seconds)';
$string['maxduration_help'] = 'Maximum accepted duration for browser recordings. Use 0 for no limit.';
$string['maximumgrade'] = 'Maximum grade';
$string['modulename'] = 'Video Rubric';
$string['modulenameplural'] = 'Video Rubrics';
$string['nextstudent'] = 'Next student';
$string['norubric'] = 'No rubric has been defined for this activity yet.';
$string['opensubmission'] = 'Open submission';
$string['playbackspeed'] = 'Speed';
$string['pluginadministration'] = 'Video Rubric administration';
$string['pluginname'] = 'Video Rubric';
$string['points'] = 'Points';
$string['previousstudent'] = 'Previous student';
$string['privacy:metadata:comment'] = 'Stores comments linked to video timestamps.';
$string['privacy:metadata:comment:commenttext'] = 'The timestamped feedback text.';
$string['privacy:metadata:comment:graderid'] = 'The user who authored the comment.';
$string['privacy:metadata:comment:timeposition'] = 'Video timestamp associated with the comment.';
$string['privacy:metadata:files'] = 'Video submissions and audio feedback are stored in Moodle File API.';
$string['privacy:metadata:grade'] = 'Stores grading data and feedback.';
$string['privacy:metadata:grade:feedbacktext'] = 'Text feedback entered by the grader.';
$string['privacy:metadata:grade:finalscore'] = 'Final calculated score.';
$string['privacy:metadata:grade:graderid'] = 'The grader who authored the evaluation.';
$string['privacy:metadata:grade:timegraded'] = 'Time the evaluation was finalized.';
$string['privacy:metadata:submission'] = 'Stores student video submission metadata.';
$string['privacy:metadata:submission:duration'] = 'Video duration.';
$string['privacy:metadata:submission:status'] = 'Draft/final submission status.';
$string['privacy:metadata:submission:timesubmitted'] = 'Time the final version was submitted.';
$string['privacy:metadata:submission:userid'] = 'The user who owns the submission.';
$string['privacy:metadata:timecreated'] = 'Creation time.';
$string['privacy:metadata:timemodified'] = 'Last modification time.';
$string['recordaudio'] = 'Record audio';
$string['recording'] = 'Recording…';
$string['recordingnotallowed'] = 'Browser recording is disabled for this activity.';
$string['recordvideo'] = 'Record video';
$string['recordvideohelp'] = 'Record with the browser camera and microphone. You can preview and replace the recording before saving it.';
$string['reset:done'] = 'Video Rubric user data reset';
$string['reset:grades'] = 'Delete all Video Rubric evaluations and comments';
$string['reset:submissions'] = 'Delete all Video Rubric submissions';
$string['review'] = 'Review';
$string['reviewsubmissions'] = 'Review submissions';
$string['rubrichelp'] = 'Define the criteria and levels used in the permanent grading panel. Changes to a rubric that has already been used may require existing grades to be reviewed.';
$string['rubricsaved'] = 'Rubric saved.';
$string['saveaudio'] = 'Save audio';
$string['saved'] = 'Saved';
$string['savedraft'] = 'Save draft';
$string['saverecording'] = 'Save recording as draft';
$string['saving'] = 'Saving…';
$string['startrecording'] = 'Start recording';
$string['status:draft'] = 'Draft';
$string['status:graded'] = 'Graded';
$string['status:grading'] = 'In grading';
$string['status:notsubmitted'] = 'Not submitted';
$string['status:submitted'] = 'Submitted';
$string['stoprecording'] = 'Stop recording';
$string['student'] = 'Student';
$string['submission'] = 'Video submission';
$string['submissionlocked'] = 'The final submission is locked.';
$string['submissionlockedinfo'] = 'This is the final submitted version and can no longer be replaced by the student.';
$string['submissionnotavailable'] = 'This submission is not available.';
$string['submissionoptions'] = 'Submission options';
$string['submissionsent'] = 'Final video submitted.';
$string['submitfinal'] = 'Submit final version';
$string['temporalcommentplaceholder'] = 'Comment on the current video moment…';
$string['temporalcomments'] = 'Timestamped comments';
$string['textfeedback'] = 'Text feedback';
$string['uploadnotallowed'] = 'Video upload is disabled for this activity.';
$string['uploadvideo'] = 'Upload video';
$string['useweights'] = 'Use criterion weights';
$string['videorubric:addinstance'] = 'Add a new Video Rubric activity';
$string['videorubric:grade'] = 'Grade video submissions';
$string['videorubric:managerubric'] = 'Manage the activity rubric';
$string['videorubric:submit'] = 'Submit videos';
$string['videorubric:view'] = 'View Video Rubric activity';
$string['videorubric:viewallgroups'] = 'View submissions from all groups';
$string['videorubricname'] = 'Video Rubric name';
$string['weight'] = 'Weight';
