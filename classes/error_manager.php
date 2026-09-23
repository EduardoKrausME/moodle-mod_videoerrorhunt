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
 * error_manager.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt;

use stdClass;

/**
 * Expected error definitions, marking and scoring.
 */
class error_manager {
    /**
     * Method max_score.
     *
     * @param int $activityid Parameter activityid.
     * @return float Return value.
     */
    public static function max_score(int $activityid): float {
        global $DB;
        return (float)$DB->get_field_sql(
            'SELECT COALESCE(SUM(points), 0) FROM {videoerrorhunt_errors} WHERE videoerrorhuntid = ?', [$activityid]
        );
    }

    /**
     * Method save_errors.
     *
     * @param int $activityid Parameter activityid.
     * @param stdClass $data Parameter data.
     * @return void Return value.
     */
    public static function save_errors(int $activityid, stdClass $data): void {
        global $DB;
        $existing = $DB->get_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activityid]);
        $keep = [];
        $titles = $data->errortitle ?? [];
        foreach ($titles as $i => $title) {
            $title = trim((string)$title);
            if ($title === '') {
                continue;
            }
            $record = (object)[
                'videoerrorhuntid' => $activityid,
                'title' => $title,
                'description' => (string)($data->errordescription[$i] ?? ''),
                'timestart' => (float)($data->errorstartseconds[$i] ?? 0),
                'timeend' => (float)($data->errorendseconds[$i] ?? 0),
                'points' => max(0, (float)($data->errorpoints[$i] ?? 1)),
                'sortorder' => count($keep) + 1,
            ];
            $id = (int)($data->errorid[$i] ?? 0);
            if ($id && isset($existing[$id])) {
                $record->id = $id;
                $DB->update_record('videoerrorhunt_errors', $record);
                $keep[] = $id;
            } else {
                $keep[] = $DB->insert_record('videoerrorhunt_errors', $record);
            }
        }
        foreach ($existing as $id => $unused) {
            if (!in_array((int)$id, $keep, true)) {
                $DB->delete_records('videoerrorhunt_errors', ['id' => $id]);
            }
        }
        self::regrade_all($activityid);
    }

    /**
     * Method add_mark.
     *
     * @param stdClass $activity Parameter activity.
     * @param stdClass $cm Parameter cm.
     * @param int $userid Parameter userid.
     * @param float $timepoint Parameter timepoint.
     * @param string $explanation Parameter explanation.
     * @return array Return value.
     */
    public static function add_mark(stdClass $activity, stdClass $cm, int $userid, float $timepoint,
                                    string $explanation): array {
        global $DB;
        $tracker = new tracking_manager();
        $progress = $tracker->get_progress($activity, $userid);
        if ($progress->submitted) {
            throw new \moodle_exception('alreadysubmitted', 'videoerrorhunt');
        }
        $count = $DB->count_records('videoerrorhunt_marks', [
            'videoerrorhuntid' => $activity->id, 'userid' => $userid,
        ]);
        if ($activity->maxmarks > 0 && $count >= $activity->maxmarks) {
            throw new \moodle_exception('marklimitreached', 'videoerrorhunt');
        }

        $errors = $DB->get_records_select('videoerrorhunt_errors',
            'videoerrorhuntid = :id AND timestart <= :time1 AND timeend >= :time2',
            ['id' => $activity->id, 'time1' => $timepoint, 'time2' => $timepoint], 'sortorder ASC');
        $credited = $DB->get_fieldset_select('videoerrorhunt_marks', 'errorid',
            'videoerrorhuntid = :id AND userid = :userid AND iscorrect = 1',
            ['id' => $activity->id, 'userid' => $userid]);
        $match = null;
        foreach ($errors as $error) {
            if (!in_array((int)$error->id, array_map('intval', $credited), true)) {
                $match = $error;
                break;
            }
        }
        $correct = $match !== null;
        $mark = (object)[
            'videoerrorhuntid' => $activity->id,
            'userid' => $userid,
            'errorid' => $correct ? $match->id : 0,
            'timepoint' => $timepoint,
            'explanation' => $explanation,
            'iscorrect' => $correct ? 1 : 0,
            'points' => $correct ? $match->points : 0,
            'timecreated' => time(),
        ];
        $DB->insert_record('videoerrorhunt_marks', $mark);
        $progress = self::recalculate_user($activity, $userid);

        $count++;
        if ($activity->maxmarks > 0 && $count >= $activity->maxmarks && !$progress->submitted) {
            $progress = self::submit($activity, $cm, $userid);
        } else {
            $tracker->update_completion($activity, $cm, $userid);
        }
        $total = $DB->count_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activity->id]);
        return [
            'correct' => $correct,
            'found' => (int)$progress->foundcount,
            'total' => $total,
            'incorrect' => (int)$progress->incorrectcount,
            'score' => (float)$progress->rawscore,
            'maxscore' => (float)$progress->maxscore,
            'submitted' => (bool)$progress->submitted,
            'marksused' => $count,
            'maxmarks' => (int)$activity->maxmarks,
        ];
    }

    /**
     * Method submit.
     *
     * @param stdClass $activity Parameter activity.
     * @param stdClass $cm Parameter cm.
     * @param int $userid Parameter userid.
     * @return stdClass Return value.
     */
    public static function submit(stdClass $activity, stdClass $cm, int $userid): stdClass {
        global $DB;
        $progress = self::recalculate_user($activity, $userid);
        if (!$progress->submitted) {
            $progress->submitted = 1;
            $progress->timesubmitted = time();
            $progress->timemodified = time();
            $DB->update_record('videoerrorhunt_progress', $progress);
        }
        videoerrorhunt_update_grades($activity, $userid, false);
        (new tracking_manager())->update_completion($activity, $cm, $userid);
        return $progress;
    }

    /**
     * Method recalculate_user.
     *
     * @param stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @return stdClass Return value.
     */
    public static function recalculate_user(stdClass $activity, int $userid): stdClass {
        global $DB;
        $tracker = new tracking_manager();
        $progress = $tracker->get_progress($activity, $userid);
        $marks = $DB->get_records('videoerrorhunt_marks', [
            'videoerrorhuntid' => $activity->id, 'userid' => $userid,
        ], 'timecreated ASC, id ASC');
        $foundids = [];
        $score = 0.0;
        $incorrect = 0;
        foreach ($marks as $mark) {
            if ($mark->iscorrect && $mark->errorid && !isset($foundids[$mark->errorid])) {
                $foundids[$mark->errorid] = true;
                $score += (float)$mark->points;
            } else if (!$mark->iscorrect) {
                $incorrect++;
            }
        }
        $maxscore = self::max_score($activity->id);
        $score = max(0, $score - $incorrect * (float)$activity->wrongpenalty);
        $grade = $maxscore > 0 && (float)$activity->grade > 0
            ? min((float)$activity->grade, ($score / $maxscore) * (float)$activity->grade)
            : 0;
        $progress->foundcount = count($foundids);
        $progress->incorrectcount = $incorrect;
        $progress->rawscore = $score;
        $progress->maxscore = $maxscore;
        $progress->grade = $grade;
        $progress->timemodified = time();
        $DB->update_record('videoerrorhunt_progress', $progress);
        return $progress;
    }

    /**
     * Method regrade_all.
     *
     * @param int $activityid Parameter activityid.
     * @return void Return value.
     */
    public static function regrade_all(int $activityid): void {
        global $DB;
        if (!$activity = $DB->get_record('videoerrorhunt', ['id' => $activityid])) {
            return;
        }
        $marks = $DB->get_records('videoerrorhunt_marks', ['videoerrorhuntid' => $activityid], 'userid, timecreated, id');
        $errors = array_values($DB->get_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activityid], 'sortorder'));
        $credited = [];
        $users = [];
        foreach ($marks as $mark) {
            $users[$mark->userid] = true;
            $credited[$mark->userid] ??= [];
            $match = null;
            foreach ($errors as $error) {
                if ($mark->timepoint >= $error->timestart && $mark->timepoint <= $error->timeend &&
                    empty($credited[$mark->userid][$error->id])) {
                    $match = $error;
                    break;
                }
            }
            $mark->errorid = $match ? $match->id : 0;
            $mark->iscorrect = $match ? 1 : 0;
            $mark->points = $match ? $match->points : 0;
            if ($match) {
                $credited[$mark->userid][$match->id] = true;
            }
            $DB->update_record('videoerrorhunt_marks', $mark);
        }
        foreach (array_keys($users) as $userid) {
            self::recalculate_user($activity, (int)$userid);
            if ($DB->get_field('videoerrorhunt_progress', 'submitted', [
                'videoerrorhuntid' => $activityid, 'userid' => $userid,
            ])) {
                videoerrorhunt_update_grades($activity, (int)$userid, false);
            }
        }
    }
}
