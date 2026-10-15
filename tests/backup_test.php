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
 * Backup tests for Video Error Hunt.
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Verify that Video Error Hunt activity backups handle uploaded videos.
 */
final class backup_test extends \advanced_testcase {
    /**
     * Video files with itemid zero must not be interpreted as structure elements.
     */
    public function test_activity_backup_with_uploaded_video(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('videoerrorhunt', [
            'course' => $course->id,
            'videosource' => 'upload',
        ]);
        $context = \context_module::instance($activity->cmid);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_videoerrorhunt',
            'filearea' => 'video',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'video.mp4',
        ], 'Test video content');

        $controller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $activity->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        try {
            $controller->execute_plan();
            $results = $controller->get_results();
            $this->assertArrayHasKey('backup_destination', $results);
            $this->assertInstanceOf(\stored_file::class, $results['backup_destination']);
        } finally {
            $controller->destroy();
        }
    }
}
