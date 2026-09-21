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
 * update_progress.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoerrorhunt\tracking_manager;

/**
 * AJAX service for video tracking.
 */
class update_progress extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'currentposition' => new external_value(PARAM_FLOAT, 'Current position'),
            'duration' => new external_value(PARAM_FLOAT, 'Duration'),
            'playbackrate' => new external_value(PARAM_FLOAT, 'Playback rate'),
            'segmentstart' => new external_value(PARAM_FLOAT, 'Watched segment start'),
            'segmentend' => new external_value(PARAM_FLOAT, 'Watched segment end'),
            'sequence' => new external_value(PARAM_INT, 'Session sequence'),
            'sessionkey' => new external_value(PARAM_ALPHANUMEXT, 'Session key'),
            'clienttime' => new external_value(PARAM_INT, 'Client timestamp'),
            'playerstate' => new external_value(PARAM_ALPHANUMEXT, 'Player state'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param float $currentposition Parameter currentposition.
     * @param float $duration Parameter duration.
     * @param float $playbackrate Parameter playbackrate.
     * @param float $segmentstart Parameter segmentstart.
     * @param float $segmentend Parameter segmentend.
     * @param int $sequence Parameter sequence.
     * @param string $sessionkey Parameter sessionkey.
     * @param int $clienttime Parameter clienttime.
     * @param string $playerstate Parameter playerstate.
     * @return array Return value.
     */
    public static function execute(int    $cmid, float $currentposition, float $duration, float $playbackrate,
                                   float  $segmentstart, float $segmentend, int $sequence, string $sessionkey, int $clienttime,
                                   string $playerstate): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'currentposition', 'duration',
            'playbackrate', 'segmentstart', 'segmentend', 'sequence', 'sessionkey', 'clienttime', 'playerstate'));
        $cm = get_coursemodule_from_id('videoerrorhunt', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoerrorhunt:submit', $context);
        $activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance], '*', MUST_EXIST);
        $progress = (new tracking_manager())->update($activity, $cm, $USER->id, $params);
        return [
            'percent' => (float)$progress->percent,
            'uniquewatched' => (float)$progress->uniquewatched,
            'lastposition' => (float)$progress->lastposition,
            'segments' => (string)$progress->watchedsegments,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Unique watched percent'),
            'uniquewatched' => new external_value(PARAM_FLOAT, 'Unique watched seconds'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Last position'),
            'segments' => new external_value(PARAM_RAW, 'JSON watched segments'),
        ]);
    }
}
