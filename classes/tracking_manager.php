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
 * tracking_manager.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt;

use context_module;
use completion_info;
use stdClass;

/**
 * Server-authoritative watched segment tracker.
 */
class tracking_manager {
    /**
     * Method update.
     *
     * @param stdClass $activity Parameter activity.
     * @param stdClass $cm Parameter cm.
     * @param int $userid Parameter userid.
     * @param array $payload Parameter payload.
     * @return stdClass Return value.
     */
    public function update(stdClass $activity, stdClass $cm, int $userid, array $payload): stdClass {
        global $DB;
        $now = time();
        $progress = $this->get_progress($activity, $userid);
        $session = $DB->get_record('videoerrorhunt_sessions', [
            'videoerrorhuntid' => $activity->id,
            'userid' => $userid,
            'sessionkey' => $payload['sessionkey'],
        ]);
        if (!$session) {
            $session = (object)[
                'videoerrorhuntid' => $activity->id,
                'userid' => $userid,
                'sessionkey' => $payload['sessionkey'],
                'sequence' => 0,
                'lastheartbeat' => 0,
                'lastclienttime' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $session->id = $DB->insert_record('videoerrorhunt_sessions', $session);
        }
        if ((int)$payload['sequence'] <= (int)$session->sequence) {
            return $progress;
        }

        $duration = max((float)$progress->duration, (float)$payload['duration']);
        $start = max(0.0, min($duration, (float)$payload['segmentstart']));
        $end = max($start, min($duration, (float)$payload['segmentend']));
        $rate = max(0.25, min((float)$activity->maxplaybackrate ?: 2.0, (float)$payload['playbackrate']));
        $serverelapsed = $session->lastheartbeat ? max(1, min(20, $now - (int)$session->lastheartbeat)) : 12;
        $clientelapsed = 0;
        if ((int)$session->lastclienttime > 0) {
            $clientelapsed = (int)$payload['clienttime'] - (int)$session->lastclienttime;
            if ($clientelapsed < 1 || $clientelapsed > 20) {
                $clientelapsed = 0;
            }
        }
        // Client elapsed time keeps legitimate queued heartbeats usable after a temporary offline period.
        $elapsed = $clientelapsed > 0 ? max($serverelapsed, $clientelapsed) : $serverelapsed;
        $allowedlength = $elapsed * $rate + 3.0;
        $segmentlength = $end - $start;

        $segments = self::decode_segments($progress->watchedsegments);
        if ($segmentlength > 0 && $segmentlength <= $allowedlength) {
            $segments[] = [$start, $end];
            $segments = self::merge_segments($segments);
            $progress->totalwatchtime += $segmentlength;
        }
        $progress->duration = $duration;
        $progress->lastposition = max(0, min($duration ?: (float)$payload['currentposition'], (float)$payload['currentposition']));
        $progress->watchedsegments = json_encode($segments);
        $progress->uniquewatched = self::unique_duration($segments);
        $progress->percent = $duration > 0 ? min(100, ($progress->uniquewatched / $duration) * 100) : 0;
        $progress->timemodified = $now;
        $DB->update_record('videoerrorhunt_progress', $progress);

        $session->sequence = (int)$payload['sequence'];
        $session->lastheartbeat = $now;
        $session->lastclienttime = (int)$payload['clienttime'];
        $session->timemodified = $now;
        $DB->update_record('videoerrorhunt_sessions', $session);

        $this->update_completion($activity, $cm, $userid);
        return $progress;
    }

    /**
     * Method get_progress.
     *
     * @param stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @return stdClass Return value.
     */
    public function get_progress(stdClass $activity, int $userid): stdClass {
        global $DB;
        $progress = $DB->get_record('videoerrorhunt_progress', [
            'videoerrorhuntid' => $activity->id, 'userid' => $userid,
        ]);
        if ($progress) {
            return $progress;
        }
        $now = time();
        $progress = (object)[
            'videoerrorhuntid' => $activity->id,
            'userid' => $userid,
            'duration' => 0,
            'lastposition' => 0,
            'uniquewatched' => 0,
            'totalwatchtime' => 0,
            'percent' => 0,
            'watchedsegments' => '[]',
            'foundcount' => 0,
            'incorrectcount' => 0,
            'rawscore' => 0,
            'maxscore' => error_manager::max_score($activity->id),
            'grade' => 0,
            'submitted' => 0,
            'timesubmitted' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $progress->id = $DB->insert_record('videoerrorhunt_progress', $progress);
        return $progress;
    }

    /**
     * Method update_completion.
     *
     * @param stdClass $activity Parameter activity.
     * @param stdClass $cm Parameter cm.
     * @param int $userid Parameter userid.
     * @return void Return value.
     */
    public function update_completion(stdClass $activity, stdClass $cm, int $userid): void {
        global $DB;
        if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }
        $course = $DB->get_record('course', ['id' => $activity->course], '*', MUST_EXIST);
        $completion = new completion_info($course);
        $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
    }

    /**
     * Method decode_segments.
     *
     * @param ?string $json Parameter json.
     * @return array Return value.
     */
    public static function decode_segments(?string $json): array {
        $segments = json_decode((string)$json, true);
        if (!is_array($segments)) {
            return [];
        }
        return array_values(array_filter($segments, static fn($s) => is_array($s) && count($s) === 2));
    }

    /**
     * Method merge_segments.
     *
     * @param array $segments Parameter segments.
     * @return array Return value.
     */
    public static function merge_segments(array $segments): array {
        usort($segments, static fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($segments as $segment) {
            $start = max(0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if (!$merged || $start > $merged[count($merged) - 1][1] + 0.5) {
                $merged[] = [$start, $end];
            } else {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $end);
            }
        }
        return $merged;
    }

    /**
     * Method unique_duration.
     *
     * @param array $segments Parameter segments.
     * @return float Return value.
     */
    public static function unique_duration(array $segments): float {
        $total = 0;
        foreach (self::merge_segments($segments) as $segment) {
            $total += max(0, $segment[1] - $segment[0]);
        }
        return $total;
    }
}
