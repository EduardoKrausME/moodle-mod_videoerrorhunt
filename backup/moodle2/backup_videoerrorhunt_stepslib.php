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
 * backup_videoerrorhunt_stepslib.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class backup_videoerrorhunt_activity_structure_step.
 */
class backup_videoerrorhunt_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $activity = new backup_nested_element('videoerrorhunt', ['id'], [
            'name', 'intro', 'introformat', 'videosource', 'videourl', 'sourceconfig',
            'resumeplayback', 'allowseek', 'maxplaybackrate',
            'feedbackmode', 'maxmarks', 'wrongpenalty', 'completionpercent',
            'completionerrors', 'requiresubmission', 'grade', 'timecreated', 'timemodified',
            ]);
        $errors = new backup_nested_element('errors');
        $error = new backup_nested_element('error', ['id'],
            ['title', 'description', 'timestart', 'timeend', 'points', 'sortorder']);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'],
            [
                'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime', 'percent',
                'watchedsegments', 'foundcount', 'incorrectcount', 'rawscore', 'maxscore', 'grade',
                'submitted', 'timesubmitted', 'timecreated', 'timemodified',
            ]);
        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', ['id'],
            ['userid', 'sessionkey', 'sequence', 'lastheartbeat', 'lastclienttime', 'timecreated', 'timemodified']);
        $marks = new backup_nested_element('marks');
        $mark = new backup_nested_element('mark', ['id'],
            ['userid', 'errorid', 'timepoint', 'explanation', 'iscorrect', 'points', 'timecreated']);
        $activity->add_child($errors);
        $errors->add_child($error);
        $activity->add_child($progresses);
        $progresses->add_child($progress);
        $activity->add_child($sessions);
        $sessions->add_child($session);
        $activity->add_child($marks);
        $marks->add_child($mark);
        $activity->set_source_table('videoerrorhunt', ['id' => backup::VAR_ACTIVITYID]);
        $error->set_source_table('videoerrorhunt_errors', ['videoerrorhuntid' => backup::VAR_PARENTID]);
        if ($this->get_setting_value('userinfo')) {
            $progress->set_source_table('videoerrorhunt_progress', ['videoerrorhuntid' => backup::VAR_PARENTID]);
            $session->set_source_table('videoerrorhunt_sessions', ['videoerrorhuntid' => backup::VAR_PARENTID]);
            $mark->set_source_table('videoerrorhunt_marks', ['videoerrorhuntid' => backup::VAR_PARENTID]);
            $progress->annotate_ids('user', 'userid');
            $session->annotate_ids('user', 'userid');
            $mark->annotate_ids('user', 'userid');
        }
        $activity->annotate_files('mod_videoerrorhunt', 'intro', null);
        $activity->annotate_files('mod_videoerrorhunt', 'video', 0);
        return $this->prepare_activity_structure($activity);
    }
}
