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
 * custom_completion.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt\completion;

use core_completion\activity_custom_completion;

/**
 * Moodle custom completion rules.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Method get_state.
     *
     * @param string $rule Parameter rule.
     * @return int Return value.
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('videoerrorhunt', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $progress = $DB->get_record('videoerrorhunt_progress', [
            'videoerrorhuntid' => $activity->id, 'userid' => $this->userid,
        ]);
        if (!$progress) {
            return COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionpercent') {
            return !$activity->completionpercent || $progress->percent >= $activity->completionpercent
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionerrors') {
            return !$activity->completionerrors || $progress->foundcount >= $activity->completionerrors
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return !$activity->requiresubmission || $progress->submitted ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionerrors', 'requiresubmission'];
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videoerrorhunt', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $out = [];
        if ($activity->completionpercent) {
            $out['completionpercent'] = get_string('completiondetail:percent', 'videoerrorhunt', $activity->completionpercent);
        }
        if ($activity->completionerrors) {
            $out['completionerrors'] = get_string('completiondetail:errors', 'videoerrorhunt', $activity->completionerrors);
        }
        if ($activity->requiresubmission) {
            $out['requiresubmission'] = get_string('completiondetail:submission', 'videoerrorhunt');
        }
        return $out;
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionpercent', 'completionerrors', 'requiresubmission',
            'completionusegrade', 'completionpassgrade'];
    }
}
