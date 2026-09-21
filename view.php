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
 * view.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoerrorhunt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoerrorhunt:view', $context);

$PAGE->set_url('/mod/videoerrorhunt/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->js_call_amd('mod_videoerrorhunt/app', 'init');
$cansubmit = has_capability('mod/videoerrorhunt:submit', $context);

$event = \mod_videoerrorhunt\event\course_module_viewed::create([
    'objectid' => $activity->id, 'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('videoerrorhunt', $activity);
$event->trigger();

// Notify Moodle completion that the activity was viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$tracker = new \mod_videoerrorhunt\tracking_manager();
if ($cansubmit) {
    $progress = $tracker->get_progress($activity, $USER->id);
    $marks = $DB->get_records('videoerrorhunt_marks', [
        'videoerrorhuntid' => $activity->id, 'userid' => $USER->id,
    ], 'timecreated, id');
} else {
    $progress = (object)[
        'duration' => 0, 'lastposition' => 0, 'percent' => 0, 'watchedsegments' => '[]',
        'foundcount' => 0, 'incorrectcount' => 0, 'rawscore' => 0,
        'maxscore' => \mod_videoerrorhunt\error_manager::max_score((int)$activity->id),
        'grade' => 0, 'submitted' => 0,
    ];
    $marks = [];
}
$expected = $DB->get_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activity->id], 'sortorder');
$reveal = (bool)$activity->feedbackmode || (bool)$progress->submitted || has_capability('mod/videoerrorhunt:viewreport', $context);

$markdata = [];
foreach ($marks as $mark) {
    $error = $mark->errorid ? ($expected[$mark->errorid] ?? null) : null;
    $markdata[] = [
        'id' => $mark->id,
        'timepoint' => (float)$mark->timepoint,
        'timecode' => \mod_videoerrorhunt\timecode::format((float)$mark->timepoint),
        'explanation' => format_string($mark->explanation),
        'reveal' => $reveal,
        'correct' => $reveal && $mark->iscorrect,
        'incorrect' => $reveal && !$mark->iscorrect,
        'errortitle' => $reveal && $error ? format_string($error->title) : '',
    ];
}

$duration = max(0.0, (float)$progress->duration);
$segments = [];
foreach (\mod_videoerrorhunt\tracking_manager::decode_segments($progress->watchedsegments) as $segment) {
    if ($duration <= 0) {
        continue;
    }
    $left = max(0, min(100, ($segment[0] / $duration) * 100));
    $width = max(0, min(100 - $left, (($segment[1] - $segment[0]) / $duration) * 100));
    $segments[] = ['left' => round($left, 4), 'width' => round($width, 4)];
}
$markpins = [];
foreach ($marks as $mark) {
    if ($duration > 0) {
        $markpins[] = [
            'left' => round(max(0, min(100, ($mark->timepoint / $duration) * 100)), 4),
            'timecode' => \mod_videoerrorhunt\timecode::format((float)$mark->timepoint),
        ];
    }
}

$config = [
    'cmid' => $cm->id,
    'userid' => $USER->id,
    'videosource' => $activity->videosource,
    'allowseek' => (bool)$activity->allowseek,
    'resumeplayback' => (int)$activity->resumeplayback,
    'lastposition' => (float)$progress->lastposition,
    'maxplaybackrate' => (float)$activity->maxplaybackrate,
    'segments' => \mod_videoerrorhunt\tracking_manager::decode_segments($progress->watchedsegments),
    'submitted' => (bool)$progress->submitted,
    'track' => $cansubmit,
];

$player = \mod_videoerrorhunt\source_manager::player_data($activity, $context);
$totalerrors = count($expected);
$markcount = count($marks);
$data = [
    'name' => format_string($activity->name),
    'cansubmit' => $cansubmit,
    'intro' => format_module_intro('videoerrorhunt', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $player,
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    'progresspercent' => round((float)$progress->percent, 1),
    'progressrounded' => (int)round((float)$progress->percent),
    'marks' => $markdata,
    'hasmarks' => !empty($markdata),
    'canmark' => $cansubmit && !$progress->submitted && (!$activity->maxmarks || $markcount < $activity->maxmarks),
    'submitted' => (bool)$progress->submitted,
    'notSubmitted' => $cansubmit && !$progress->submitted,
    'feedbackrevealed' => $reveal,
    'feedbackhidden' => !$reveal,
    'foundcount' => $reveal ? (int)$progress->foundcount : 0,
    'remainingcount' => $reveal ? max(0, $totalerrors - (int)$progress->foundcount) : 0,
    'totalerrors' => $totalerrors,
    'huntinstruction' => get_string('huntinstruction', 'videoerrorhunt', $totalerrors),
    'incorrectcount' => $reveal ? (int)$progress->incorrectcount : 0,
    'score' => $reveal ? format_float((float)$progress->rawscore, 2) : '',
    'maxscore' => format_float((float)$progress->maxscore, 2),
    'grade' => $reveal ? format_float((float)$progress->grade, 2) : '',
    'maxgrade' => format_float((float)$activity->grade, 2),
    'marksused' => $markcount,
    'maxmarks' => (int)$activity->maxmarks,
    'hasmarklimit' => (int)$activity->maxmarks > 0,
    'segments' => $segments,
    'markpins' => $markpins,
    'canviewreport' => has_capability('mod/videoerrorhunt:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videoerrorhunt/report.php', ['id' => $cm->id]))->out(false),
    'canedit' => has_capability('moodle/course:manageactivities', $context),
    'editurl' => (new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]))->out(false),
];

if ($data['canviewreport']) {
    $answers = [];
    foreach ($expected as $error) {
        $answers[] = [
            'title' => format_string($error->title),
            'description' => format_text($error->description, FORMAT_PLAIN),
            'range' => \mod_videoerrorhunt\timecode::format((float)$error->timestart) . '–' .
                \mod_videoerrorhunt\timecode::format((float)$error->timeend),
            'points' => format_float((float)$error->points, 2),
        ];
    }
    $data['answerkey'] = $answers;
    $data['hasanswerkey'] = !empty($answers);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoerrorhunt/view', $data);
echo $OUTPUT->footer();
