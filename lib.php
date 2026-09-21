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
 * lib.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoerrorhunt\error_manager;
use mod_videoerrorhunt\source_manager;

function videoerrorhunt_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_ASSIGNMENT,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_GRADE_HAS_GRADE => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_ASSESSMENT,
        default => null,
    };
}

function videoerrorhunt_add_instance(stdClass $data, ?mod_videoerrorhunt_mod_form $mform = null): int {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $videodraftid = (int)($data->videofile ?? 0);
    unset($data->videofile);
    source_manager::normalise($data);
    $arrays = videoerrorhunt_extract_form_arrays($data);
    $id = $DB->insert_record('videoerrorhunt', $data);
    $data->id = $id;
    videoerrorhunt_save_video_file($data, $videodraftid);
    error_manager::save_errors($id, (object)$arrays);
    videoerrorhunt_grade_item_update($data);
    return $id;
}

function videoerrorhunt_update_instance(stdClass $data, ?mod_videoerrorhunt_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $videodraftid = (int)($data->videofile ?? 0);
    unset($data->videofile);
    source_manager::normalise($data);
    $arrays = videoerrorhunt_extract_form_arrays($data);
    $result = $DB->update_record('videoerrorhunt', $data);
    videoerrorhunt_save_video_file($data, $videodraftid);
    error_manager::save_errors($data->id, (object)$arrays);
    videoerrorhunt_grade_item_update($data);
    return $result;
}

function videoerrorhunt_extract_form_arrays(stdClass $data): array {
    $fields = ['errorid', 'errortitle', 'errordescription', 'errorstart', 'errorend', 'errorpoints',
        'errorstartseconds', 'errorendseconds'];
    $out = [];
    foreach ($fields as $field) {
        $out[$field] = $data->{$field} ?? [];
        unset($data->{$field});
    }
    unset($data->error_repeats, $data->error_add_fields);
    return $out;
}

function videoerrorhunt_save_video_file(stdClass $data, int $draftid): void {
    if (!isset($data->coursemodule)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    if ($data->videosource === 'upload' && $draftid > 0) {
        file_save_draft_area_files($draftid, $context->id, 'mod_videoerrorhunt', 'video', 0,
            ['subdirs' => 0, 'maxfiles' => 1]);
    } else if ($data->videosource !== 'upload') {
        get_file_storage()->delete_area_files($context->id, 'mod_videoerrorhunt', 'video', 0);
    }
}

function videoerrorhunt_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videoerrorhunt', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $cm = get_coursemodule_from_instance('videoerrorhunt', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_videoerrorhunt');
    }
    $DB->delete_records('videoerrorhunt_marks', ['videoerrorhuntid' => $id]);
    $DB->delete_records('videoerrorhunt_sessions', ['videoerrorhuntid' => $id]);
    $DB->delete_records('videoerrorhunt_progress', ['videoerrorhuntid' => $id]);
    $DB->delete_records('videoerrorhunt_errors', ['videoerrorhuntid' => $id]);
    $DB->delete_records('videoerrorhunt', ['id' => $id]);
    videoerrorhunt_grade_item_delete($activity);
    return true;
}

function mod_videoerrorhunt_pluginfile($course, $cm, $context, string $filearea, array $args,
                                       bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'video') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videoerrorhunt:view', $context);
    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videoerrorhunt', 'video', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function videoerrorhunt_get_file_areas($course, $cm, $context): array {
    return ['video' => get_string('videofile', 'videoerrorhunt')];
}

function videoerrorhunt_grade_item_update(stdClass $activity, ?array $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => clean_param($activity->name, PARAM_NOTAGS),
        'gradetype' => (float)$activity->grade > 0 ? GRADE_TYPE_VALUE : GRADE_TYPE_NONE,
        'grademin' => 0,
        'grademax' => max(0, (float)$activity->grade),
    ];
    return grade_update('mod/videoerrorhunt', $activity->course, 'mod', 'videoerrorhunt', $activity->id, 0, $grades, $item);
}

function videoerrorhunt_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;
    $conditions = ['videoerrorhuntid' => $activity->id, 'submitted' => 1];
    if ($userid) {
        $conditions['userid'] = $userid;
    }
    $records = $DB->get_records('videoerrorhunt_progress', $conditions);
    $grades = [];
    foreach ($records as $record) {
        $grades[$record->userid] = (object)['userid' => $record->userid, 'rawgrade' => (float)$record->grade];
    }
    if (!$grades && $userid && $nullifnone) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => null];
    }
    videoerrorhunt_grade_item_update($activity, $grades);
}

function videoerrorhunt_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/videoerrorhunt', $activity->course, 'mod', 'videoerrorhunt', $activity->id, 0, null,
        ['deleted' => 1]);
}

function videoerrorhunt_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;
    $activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance],
        'id,name,intro,introformat,completionpercent,completionerrors,requiresubmission');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videoerrorhunt', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionpercent' => (int)$activity->completionpercent,
            'completionerrors' => (int)$activity->completionerrors,
            'requiresubmission' => (bool)$activity->requiresubmission,
        ];
    }
    return $info;
}

function videoerrorhunt_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC || empty($cm->customdata['customcompletionrules'])) {
        return [];
    }
    $rules = $cm->customdata['customcompletionrules'];
    $out = [];
    if (!empty($rules['completionpercent'])) {
        $out[] = get_string('completiondetail:percent', 'videoerrorhunt', $rules['completionpercent']);
    }
    if (!empty($rules['completionerrors'])) {
        $out[] = get_string('completiondetail:errors', 'videoerrorhunt', $rules['completionerrors']);
    }
    if (!empty($rules['requiresubmission'])) {
        $out[] = get_string('completiondetail:submission', 'videoerrorhunt');
    }
    return $out;
}

function videoerrorhunt_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;
    $activity = $DB->get_record('videoerrorhunt', ['id' => $cm->instance], '*', MUST_EXIST);
    $progress = $DB->get_record('videoerrorhunt_progress', [
        'videoerrorhuntid' => $activity->id, 'userid' => $userid,
    ]);
    if (!$progress) {
        return false;
    }
    if ($activity->completionpercent > 0 && $progress->percent < $activity->completionpercent) {
        return false;
    }
    if ($activity->completionerrors > 0 && $progress->foundcount < $activity->completionerrors) {
        return false;
    }
    return !$activity->requiresubmission || !empty($progress->submitted);
}
