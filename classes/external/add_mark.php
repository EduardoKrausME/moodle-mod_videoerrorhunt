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
 * add_mark.php
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
use mod_videoerrorhunt\error_manager;

/**
 * AJAX service for recording an error observation.
 */
class add_mark extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'timepoint' => new external_value(PARAM_FLOAT, 'Video time in seconds'),
            'explanation' => new external_value(PARAM_TEXT, 'Student explanation'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param float $timepoint Parameter timepoint.
     * @param string $explanation Parameter explanation.
     * @return array Return value.
     */
    public static function execute(int $cmid, float $timepoint, string $explanation): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'timepoint', 'explanation'));
        if (trim($params['explanation']) === '') {
            throw new \moodle_exception('explanationrequired', 'videoerrorhunt');
        }
        $cm = get_coursemodule_from_id('videoerrorhunt', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoerrorhunt:submit', $context);
        $activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance], '*', MUST_EXIST);
        $result = error_manager::add_mark($activity, $cm, $USER->id, max(0, $params['timepoint']),
            trim($params['explanation']));
        $reveal = (bool)$activity->feedbackmode || $result['submitted'];
        return [
            'feedbackrevealed' => $reveal ? 1 : 0,
            'correct' => $reveal ? ($result['correct'] ? 1 : 0) : -1,
            'found' => $reveal ? $result['found'] : 0,
            'total' => $result['total'],
            'incorrect' => $reveal ? $result['incorrect'] : 0,
            'score' => $reveal ? $result['score'] : 0,
            'maxscore' => $result['maxscore'],
            'submitted' => $result['submitted'] ? 1 : 0,
            'marksused' => $result['marksused'],
            'maxmarks' => $result['maxmarks'],
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'feedbackrevealed' => new external_value(PARAM_INT, 'Whether correctness can be shown'),
            'correct' => new external_value(PARAM_INT, '1 correct, 0 incorrect, -1 hidden'),
            'found' => new external_value(PARAM_INT, 'Correct errors found'),
            'total' => new external_value(PARAM_INT, 'Expected error count'),
            'incorrect' => new external_value(PARAM_INT, 'Incorrect marks'),
            'score' => new external_value(PARAM_FLOAT, 'Current score'),
            'maxscore' => new external_value(PARAM_FLOAT, 'Maximum raw score'),
            'submitted' => new external_value(PARAM_INT, 'Submission state'),
            'marksused' => new external_value(PARAM_INT, 'Marks used'),
            'maxmarks' => new external_value(PARAM_INT, 'Maximum marks, zero unlimited'),
        ]);
    }
}
