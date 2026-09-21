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
 * restore_videoerrorhunt_stepslib.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class restore_videoerrorhunt_activity_structure_step.
 */
class restore_videoerrorhunt_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [new restore_path_element('videoerrorhunt', '/activity/videoerrorhunt'),
            new restore_path_element('videoerrorhunt_error', '/activity/videoerrorhunt/errors/error')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videoerrorhunt_progress', '/activity/videoerrorhunt/progresses/progress');
            $paths[] = new restore_path_element('videoerrorhunt_session', '/activity/videoerrorhunt/sessions/session');
            $paths[] = new restore_path_element('videoerrorhunt_mark', '/activity/videoerrorhunt/marks/mark');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_videoerrorhunt.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoerrorhunt($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videoerrorhunt', $data);
        $this->apply_activity_instance($data->id);
        $this->set_mapping('videoerrorhunt', $oldid, $data->id, true);
    }

    /**
     * Method process_videoerrorhunt_error.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoerrorhunt_error($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videoerrorhuntid = $this->get_new_parentid('videoerrorhunt');
        $newid = $DB->insert_record('videoerrorhunt_errors', $data);
        $this->set_mapping('videoerrorhunt_error', $oldid, $newid);
    }

    /**
     * Method process_videoerrorhunt_progress.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoerrorhunt_progress($data): void {
        global $DB;
        $data = (object)$data;
        $data->videoerrorhuntid = $this->get_new_parentid('videoerrorhunt');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        $DB->insert_record('videoerrorhunt_progress', $data);
    }

    /**
     * Method process_videoerrorhunt_session.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoerrorhunt_session($data): void {
        global $DB;
        $data = (object)$data;
        $data->videoerrorhuntid = $this->get_new_parentid('videoerrorhunt');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        $DB->insert_record('videoerrorhunt_sessions', $data);
    }

    /**
     * Method process_videoerrorhunt_mark.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoerrorhunt_mark($data): void {
        global $DB;
        $data = (object)$data;
        $data->videoerrorhuntid = $this->get_new_parentid('videoerrorhunt');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        if ($data->errorid) {
            $data->errorid = $this->get_mappingid('videoerrorhunt_error', $data->errorid, 0);
        }
        $DB->insert_record('videoerrorhunt_marks', $data);
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videoerrorhunt', 'intro', null);
        $this->add_related_files('mod_videoerrorhunt', 'video', 0);
    }
}
