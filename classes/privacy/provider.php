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
 * provider.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoerrorhunt\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoerrorhunt_progress', [
            'userid' => 'privacy:metadata:userid',
            'watchedsegments' => 'privacy:metadata:watchedsegments',
            'percent' => 'privacy:metadata:percent',
            'grade' => 'privacy:metadata:grade',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('videoerrorhunt_marks', [
            'userid' => 'privacy:metadata:userid',
            'timepoint' => 'privacy:metadata:timepoint',
            'explanation' => 'privacy:metadata:explanation',
        ], 'privacy:metadata:marks');
        $collection->add_database_table('videoerrorhunt_sessions', [
            'userid' => 'privacy:metadata:userid',
            'sessionkey' => 'privacy:metadata:sessionkey',
            'lastheartbeat' => 'privacy:metadata:lastheartbeat',
        ], 'privacy:metadata:sessions');
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $sql = "SELECT ctx.id FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videoerrorhunt} v ON v.id = cm.instance
             LEFT JOIN {videoerrorhunt_progress} p ON p.videoerrorhuntid = v.id AND p.userid = :userid1
             LEFT JOIN {videoerrorhunt_marks} mk ON mk.videoerrorhuntid = v.id AND mk.userid = :userid2
             LEFT JOIN {videoerrorhunt_sessions} s ON s.videoerrorhuntid = v.id AND s.userid = :userid3
                 WHERE p.id IS NOT NULL OR mk.id IS NOT NULL OR s.id IS NOT NULL";
        $list->add_from_sql($sql, ['contextmodule' => CONTEXT_MODULE, 'modname' => 'videoerrorhunt',
            'userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid]);
        return $list;
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videoerrorhunt', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $progress = $DB->get_record('videoerrorhunt_progress', ['videoerrorhuntid' => $cm->instance,
                'userid' => $contextlist->get_user()->id]);
            $marks = $DB->get_records('videoerrorhunt_marks', ['videoerrorhuntid' => $cm->instance,
                'userid' => $contextlist->get_user()->id], 'timecreated');
            $sessions = $DB->get_records('videoerrorhunt_sessions', ['videoerrorhuntid' => $cm->instance,
                'userid' => $contextlist->get_user()->id], 'timecreated');
            writer::with_context($context)->export_data([get_string('pluginname', 'videoerrorhunt')], (object)[
                'progress' => $progress,
                'marks' => array_values($marks),
                'sessions' => array_values($sessions),
            ]);
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param \context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videoerrorhunt', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $DB->delete_records('videoerrorhunt_marks', ['videoerrorhuntid' => $cm->instance]);
        $DB->delete_records('videoerrorhunt_sessions', ['videoerrorhuntid' => $cm->instance]);
        $DB->delete_records('videoerrorhunt_progress', ['videoerrorhuntid' => $cm->instance]);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videoerrorhunt', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            foreach (['videoerrorhunt_marks', 'videoerrorhunt_sessions', 'videoerrorhunt_progress'] as $table) {
                $DB->delete_records($table, ['videoerrorhuntid' => $cm->instance, 'userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $sql = "SELECT p.userid FROM {videoerrorhunt_progress} p
                  JOIN {course_modules} cm ON cm.instance = p.videoerrorhuntid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid
                 UNION
                SELECT mk.userid FROM {videoerrorhunt_marks} mk
                  JOIN {course_modules} cm2 ON cm2.instance = mk.videoerrorhuntid
                  JOIN {modules} m2 ON m2.id = cm2.module AND m2.name = :modname2
                 WHERE cm2.id = :cmid2
                 UNION
                SELECT s.userid FROM {videoerrorhunt_sessions} s
                  JOIN {course_modules} cm3 ON cm3.instance = s.videoerrorhuntid
                  JOIN {modules} m3 ON m3.id = cm3.module AND m3.name = :modname3
                 WHERE cm3.id = :cmid3";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'videoerrorhunt', 'cmid' => $context->instanceid,
            'modname2' => 'videoerrorhunt', 'cmid2' => $context->instanceid,
            'modname3' => 'videoerrorhunt', 'cmid3' => $context->instanceid]);
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videoerrorhunt', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['activity'] = $cm->instance;
        foreach (['videoerrorhunt_marks', 'videoerrorhunt_sessions', 'videoerrorhunt_progress'] as $table) {
            $DB->delete_records_select($table, "videoerrorhuntid = :activity AND userid $insql", $params);
        }
    }
}
