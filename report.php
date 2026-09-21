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
 * report.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videoerrorhunt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoerrorhunt:viewreport', $context);

$PAGE->set_url('/mod/videoerrorhunt/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'videoerrorhunt'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$errors = $DB->get_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activity->id], 'sortorder');
$totalerrors = count($errors);
$maxscore = 0.0;
foreach ($errors as $error) {
    $maxscore += (float)$error->points;
}

// Report the complete set of currently enrolled learners, including users who have not started yet.
$students = get_enrolled_users(
    $context,
    'mod/videoerrorhunt:submit',
    0,
    'u.id,u.firstname,u.lastname,u.picture,u.imagealt',
    'u.lastname ASC, u.firstname ASC',
    0,
    0,
    true
);
$progressrecords = $DB->get_records('videoerrorhunt_progress', ['videoerrorhuntid' => $activity->id]);
$progressbyuser = [];
foreach ($progressrecords as $progress) {
    $progressbyuser[(int)$progress->userid] = $progress;
}

$rows = [];
foreach ($students as $student) {
    $studentid = (int)$student->id;
    $progress = $progressbyuser[$studentid] ?? null;
    $found = $progress ? (int)$progress->foundcount : 0;
    $rows[] = [
        'name' => fullname($student),
        'percent' => $progress ? round((float)$progress->percent, 1) : 0,
        'found' => $found,
        'missed' => max(0, $totalerrors - $found),
        'incorrect' => $progress ? (int)$progress->incorrectcount : 0,
        'score' => format_float($progress ? (float)$progress->rawscore : 0, 2) . ' / ' . format_float($maxscore, 2),
        'grade' => $progress
            ? format_float((float)$progress->grade, 2) . ' / ' . format_float((float)$activity->grade, 2)
            : '—',
        'status' => !$progress
            ? get_string('notstarted', 'videoerrorhunt')
            : ($progress->submitted ? get_string('submitted', 'videoerrorhunt') : get_string('inprogress', 'videoerrorhunt')),
        'detailurl' => (new moodle_url('/mod/videoerrorhunt/report.php', [
            'id' => $cm->id,
            'userid' => $studentid,
        ]))->out(false),
    ];
}

// Count distinct enrolled learners who found each expected error.
$studentids = array_fill_keys(array_map('intval', array_keys($students)), true);
$participantids = [];
foreach ($progressbyuser as $progressuserid => $unused) {
    if (isset($studentids[(int)$progressuserid])) {
        $participantids[(int)$progressuserid] = true;
    }
}
$foundbyerror = [];
$correctmarks = $DB->get_records('videoerrorhunt_marks', [
    'videoerrorhuntid' => $activity->id,
    'iscorrect' => 1,
]);
foreach ($correctmarks as $mark) {
    $markuserid = (int)$mark->userid;
    $errorid = (int)$mark->errorid;
    if ($errorid > 0 && isset($participantids[$markuserid])) {
        $foundbyerror[$errorid][$markuserid] = true;
    }
}

$hardest = [];
$studentcount = count($participantids);
if ($studentcount > 0) {
    foreach ($errors as $error) {
        $found = isset($foundbyerror[(int)$error->id]) ? count($foundbyerror[(int)$error->id]) : 0;
        $rate = ($found / $studentcount) * 100;
        $hardest[] = [
            'title' => format_string($error->title),
            'range' => \mod_videoerrorhunt\timecode::format((float)$error->timestart) . '–' .
                \mod_videoerrorhunt\timecode::format((float)$error->timeend),
            'found' => $found,
            'rate' => round($rate, 1),
            'difficulty' => round(100 - $rate, 1),
        ];
    }
    usort($hardest, static fn(array $a, array $b): int => $b['difficulty'] <=> $a['difficulty']);
}

$detail = [];
$missederrors = [];
$detailuser = '';
if ($userid && isset($students[$userid])) {
    $user = $students[$userid];
    $detailuser = fullname($user);
    $marks = $DB->get_records('videoerrorhunt_marks', [
        'videoerrorhuntid' => $activity->id,
        'userid' => $userid,
    ], 'timecreated, id');
    $founderrorids = [];
    foreach ($marks as $mark) {
        $error = $mark->errorid ? ($errors[$mark->errorid] ?? null) : null;
        if ($mark->iscorrect && $error) {
            $founderrorids[(int)$error->id] = true;
        }
        $detail[] = [
            'timecode' => \mod_videoerrorhunt\timecode::format((float)$mark->timepoint),
            'explanation' => format_string($mark->explanation),
            'result' => $mark->iscorrect
                ? get_string('correct', 'videoerrorhunt')
                : get_string('incorrect', 'videoerrorhunt'),
            'error' => $error ? format_string($error->title) : '—',
            'points' => format_float((float)$mark->points, 2),
        ];
    }
    foreach ($errors as $error) {
        if (!isset($founderrorids[(int)$error->id])) {
            $missederrors[] = [
                'title' => format_string($error->title),
                'range' => \mod_videoerrorhunt\timecode::format((float)$error->timestart) . '–' .
                    \mod_videoerrorhunt\timecode::format((float)$error->timeend),
                'points' => format_float((float)$error->points, 2),
            ];
        }
    }
}

$data = [
    'activityname' => format_string($activity->name),
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'hardest' => $hardest,
    'hashardest' => !empty($hardest),
    'detail' => $detail,
    'hasdetail' => !empty($detail),
    'detailuser' => $detailuser,
    'missederrors' => $missederrors,
    'hasmissederrors' => !empty($missederrors),
    'backurl' => (new moodle_url('/mod/videoerrorhunt/view.php', ['id' => $cm->id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoerrorhunt/report', $data);
echo $OUTPUT->footer();
