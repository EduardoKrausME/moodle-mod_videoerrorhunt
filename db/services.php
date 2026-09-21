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
 * services.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'mod_videoerrorhunt_update_progress' => [
        'classname' => 'mod_videoerrorhunt\\external\\update_progress',
        'methodname' => 'execute',
        'description' => 'Store a server-authoritative video tracking update.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoerrorhunt:submit',
    ],
    'mod_videoerrorhunt_add_mark' => [
        'classname' => 'mod_videoerrorhunt\\external\\add_mark',
        'methodname' => 'execute',
        'description' => 'Register an error mark at the current video position.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoerrorhunt:submit',
    ],
    'mod_videoerrorhunt_finish_hunt' => [
        'classname' => 'mod_videoerrorhunt\\external\\finish_hunt',
        'methodname' => 'execute',
        'description' => 'Submit the current Video Error Hunt attempt.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoerrorhunt:submit',
    ],
];
