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
 * mod_form.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoerrorhunt\timecode;
use mod_videoerrorhunt\source_manager;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity configuration form.
 */
class mod_videoerrorhunt_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoerrorhuntname', 'videoerrorhunt'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('html', '<h3>' . get_string('videosettings', 'videoerrorhunt') . '</h3>');
        $mform->addElement('select', 'videosource', get_string('videosource', 'videoerrorhunt'), source_manager::options());
        $mform->setDefault('videosource', 'upload');
        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videoerrorhunt'), null, [
            'subdirs' => 0, 'accepted_types' => ['video'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');
        $mform->addElement('url', 'directurl', get_string('directurl', 'videoerrorhunt'), ['size' => 80]);
        $mform->setType('directurl', PARAM_URL);
        $mform->hideIf('directurl', 'videosource', 'neq', 'url');
        $mform->addElement('url', 'youtubeurl', get_string('youtubeurl', 'videoerrorhunt'), ['size' => 80]);
        $mform->setType('youtubeurl', PARAM_URL);
        $mform->hideIf('youtubeurl', 'videosource', 'neq', 'youtube');
        $mform->addElement('url', 'vimeourl', get_string('vimeourl', 'videoerrorhunt'), ['size' => 80]);
        $mform->setType('vimeourl', PARAM_URL);
        $mform->hideIf('vimeourl', 'videosource', 'neq', 'vimeo');

        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'videoerrorhunt'), [
            1 => get_string('resumeautomatic', 'videoerrorhunt'),
            2 => get_string('resumeask', 'videoerrorhunt'),
            0 => get_string('resumefromstart', 'videoerrorhunt'),
        ]);
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videoerrorhunt'));
        $mform->setDefault('allowseek', 1);
        $mform->addElement('select', 'maxplaybackrate', get_string('maxplaybackrate', 'videoerrorhunt'), [
            '1' => '1x', '1.25' => '1.25x', '1.5' => '1.5x', '1.75' => '1.75x', '2' => '2x',
        ]);
        $mform->setDefault('maxplaybackrate', '2');

        $mform->addElement('html', '<h3>' . get_string('huntsettings', 'videoerrorhunt') . '</h3>');
        $mform->addElement('select', 'feedbackmode', get_string('feedbackmode', 'videoerrorhunt'), [
            1 => get_string('feedbackimmediate', 'videoerrorhunt'),
            0 => get_string('feedbackfinal', 'videoerrorhunt'),
        ]);
        $mform->setDefault('feedbackmode', 1);
        $mform->addElement('text', 'maxmarks', get_string('maxmarks', 'videoerrorhunt'), ['size' => 8]);
        $mform->setType('maxmarks', PARAM_INT);
        $mform->setDefault('maxmarks', 0);
        $mform->addElement('text', 'wrongpenalty', get_string('wrongpenalty', 'videoerrorhunt'), ['size' => 8]);
        $mform->setType('wrongpenalty', PARAM_FLOAT);
        $mform->setDefault('wrongpenalty', 0);

        $mform->addElement('html', '<h3>' . get_string('expectederrors', 'videoerrorhunt') . '</h3>');
        $mform->addElement('static', 'errorhelp', '', get_string('expectederrorshelp', 'videoerrorhunt'));
        $repeat = [];
        $repeat[] = $mform->createElement('hidden', 'errorid', 0);
        $repeat[] = $mform->createElement('text', 'errortitle', get_string('errortitle', 'videoerrorhunt'), ['size' => 42]);
        $repeat[] = $mform->createElement('textarea', 'errordescription', get_string('errordescription', 'videoerrorhunt'),
            ['rows' => 2, 'cols' => 52]);
        $repeat[] = $mform->createElement('text', 'errorstart', get_string('errorstart', 'videoerrorhunt'), ['size' => 12]);
        $repeat[] = $mform->createElement('text', 'errorend', get_string('errorend', 'videoerrorhunt'), ['size' => 12]);
        $repeat[] = $mform->createElement('text', 'errorpoints', get_string('errorpoints', 'videoerrorhunt'), ['size' => 8]);
        $options = [
            'errorid' => ['type' => PARAM_INT],
            'errortitle' => ['type' => PARAM_TEXT],
            'errordescription' => ['type' => PARAM_TEXT],
            'errorstart' => ['type' => PARAM_TEXT],
            'errorend' => ['type' => PARAM_TEXT],
            'errorpoints' => ['type' => PARAM_FLOAT, 'default' => 1],
        ];
        $this->repeat_elements($repeat, 3, $options, 'error_repeats', 'error_add_fields', 1,
            get_string('adderror', 'videoerrorhunt'), true);

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 100);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            $temp = (object)$data;
            \mod_videoerrorhunt\source_manager::normalise($temp);
        } catch (moodle_exception $e) {
            $field = match ($data['videosource'] ?? '') {
                'url' => 'directurl',
                'youtube' => 'youtubeurl',
                'vimeo' => 'vimeourl',
                default => 'videofile',
            };
            $errors[$field] = $e->getMessage();
        }
        if (($data['videosource'] ?? '') === 'upload' && empty($data['videofile'])) {
            $errors['videofile'] = get_string('requiredvideo', 'videoerrorhunt');
        }
        if ((int)($data['maxmarks'] ?? 0) < 0) {
            $errors['maxmarks'] = get_string('mustbenonnegative', 'videoerrorhunt');
        }
        if ((float)($data['wrongpenalty'] ?? 0) < 0) {
            $errors['wrongpenalty'] = get_string('mustbenonnegative', 'videoerrorhunt');
        }
        $definederrors = 0;
        foreach (($data['errortitle'] ?? []) as $i => $title) {
            if (trim((string)$title) === '') {
                continue;
            }
            $definederrors++;
            $start = timecode::parse((string)($data['errorstart'][$i] ?? ''));
            $end = timecode::parse((string)($data['errorend'][$i] ?? ''));
            if ($start === null) {
                $errors['errorstart[' . $i . ']'] = get_string('invalidtimecode', 'videoerrorhunt');
            }
            if ($end === null || ($start !== null && $end < $start)) {
                $errors['errorend[' . $i . ']'] = get_string('invalidtimerange', 'videoerrorhunt');
            }
            if ((float)($data['errorpoints'][$i] ?? 0) < 0) {
                $errors['errorpoints[' . $i . ']'] = get_string('mustbenonnegative', 'videoerrorhunt');
            }
        }
        if ($definederrors === 0) {
            $errors['errortitle[0]'] = get_string('atleastoneerror', 'videoerrorhunt');
        }
        $maxmarks = (int)($data['maxmarks'] ?? 0);
        if ($maxmarks > 0 && $maxmarks < $definederrors) {
            $errors['maxmarks'] = get_string('maxmarkstoosmall', 'videoerrorhunt');
        }
        $percentfield = $this->get_suffixed_name('completionpercent');
        $errorsfield = $this->get_suffixed_name('completionerrors');
        $completionpercent = (int)($data[$percentfield] ?? 0);
        $completionerrors = (int)($data[$errorsfield] ?? 0);
        if ($completionpercent < 0 || $completionpercent > 100) {
            $errors[$percentfield] = get_string('errorpercent', 'videoerrorhunt');
        }
        if ($completionerrors < 0 || $completionerrors > $definederrors || ($maxmarks > 0 && $completionerrors > $maxmarks)) {
            $errors[$errorsfield] = get_string('completionerrorsexceed', 'videoerrorhunt');
        }
        foreach (['videofile'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videoerrorhunt');
                }
            }
        }
        return $errors;
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        global $DB;
        foreach (['completionpercent', 'completionerrors', 'requiresubmission'] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$this->get_suffixed_name($field)] = $defaultvalues[$field];
            }
        }
        if (empty($this->current->instance)) {
            return;
        }
        $activity = $DB->get_record('videoerrorhunt', ['id' => $this->current->instance], '*', MUST_EXIST);
        source_manager::prepare_form($activity, $defaultvalues);
        $draftid = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area($draftid, $this->context->id, 'mod_videoerrorhunt', 'video', 0,
            ['subdirs' => 0, 'maxfiles' => 1]);
        $defaultvalues['videofile'] = $draftid;
        $records = array_values($DB->get_records('videoerrorhunt_errors', ['videoerrorhuntid' => $activity->id], 'sortorder'));
        foreach ($records as $i => $record) {
            $defaultvalues['errorid'][$i] = $record->id;
            $defaultvalues['errortitle'][$i] = $record->title;
            $defaultvalues['errordescription'][$i] = $record->description;
            $defaultvalues['errorstart'][$i] = timecode::format((float)$record->timestart);
            $defaultvalues['errorend'][$i] = timecode::format((float)$record->timeend);
            $defaultvalues['errorpoints'][$i] = $record->points;
        }
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $p = $this->get_suffixed_name('completionpercent');
        $e = $this->get_suffixed_name('completionerrors');
        $s = $this->get_suffixed_name('requiresubmission');
        $mform->addElement('text', $p, get_string('completionpercent', 'videoerrorhunt'), ['size' => 5]);
        $mform->setType($p, PARAM_INT);
        $mform->setDefault($p, 80);
        $mform->addElement('text', $e, get_string('completionerrors', 'videoerrorhunt'), ['size' => 5]);
        $mform->setType($e, PARAM_INT);
        $mform->setDefault($e, 0);
        $mform->addElement('selectyesno', $s, get_string('requiresubmission', 'videoerrorhunt'));
        $mform->setDefault($s, 1);
        return [$p, $e, $s];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return (int)($data[$this->get_suffixed_name('completionpercent')] ?? 0) > 0 ||
            (int)($data[$this->get_suffixed_name('completionerrors')] ?? 0) > 0 ||
            !empty($data[$this->get_suffixed_name('requiresubmission')]);
    }

    /**
     * Method get_data.
     *
     * @return mixed Return value.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        foreach (['completionpercent', 'completionerrors', 'requiresubmission'] as $field) {
            $suffixed = $this->get_suffixed_name($field);
            if (property_exists($data, $suffixed)) {
                $data->{$field} = $data->{$suffixed};
                unset($data->{$suffixed});
            }
        }
        $data->errorstartseconds = [];
        $data->errorendseconds = [];
        foreach (($data->errortitle ?? []) as $i => $title) {
            $data->errorstartseconds[$i] = timecode::parse((string)($data->errorstart[$i] ?? '')) ?? 0;
            $data->errorendseconds[$i] = timecode::parse((string)($data->errorend[$i] ?? '')) ?? 0;
        }
        return $data;
    }

    /**
     * Method get_suffixed_name.
     *
     * @param string $field Parameter field.
     * @return string Return value.
     */
    private function get_suffixed_name(string $field): string {
        return $field . '_videoerrorhunt';
    }
}
