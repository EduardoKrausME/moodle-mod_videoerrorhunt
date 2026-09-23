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
 * videoerrorhunt.php
 *
 * @package   mod_videoerrorhunt
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['adderror'] = 'Add another expected error';
$string['allowseek'] = 'Allow free seeking';
$string['alreadysubmitted'] = 'This error hunt has already been submitted.';
$string['answerkey'] = 'Expected errors';
$string['atleastoneerror'] = 'Define at least one expected error.';
$string['backtoactivity'] = 'Back to activity';
$string['completiondetail:errors'] = 'Find at least {$a} expected errors';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completiondetail:submission'] = 'Submit the error hunt';
$string['completionerrors'] = 'Require this many errors found (0 disables)';
$string['completionerrorsexceed'] = 'The completion error count cannot exceed the number of expected errors or the maximum number of marks.';
$string['completionpercent'] = 'Require watched percentage';
$string['completionrules'] = '';
$string['correct'] = 'Correct';
$string['difficulty'] = 'Difficulty';
$string['directurl'] = 'Direct video URL';
$string['editsettings'] = 'Edit settings';
$string['errordescription'] = 'Teacher description';
$string['errorend'] = 'Accepted until';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['errorpercent'] = 'Enter a percentage from 0 to 100.';
$string['errorpoints'] = 'Points';
$string['errorsfound'] = 'Errors found';
$string['errorsremaining'] = 'Errors remaining';
$string['errorstart'] = 'Accepted from';
$string['errortitle'] = 'Error';
$string['expectederrors'] = 'Expected errors';
$string['expectederrorshelp'] = 'Define each expected error, accepted start/end time range, and points. Timecodes accept MM:SS or HH:MM:SS.';
$string['explanation'] = 'What is wrong at this moment?';
$string['explanationrequired'] = 'Write a brief explanation before marking an error.';
$string['feedbackfinal'] = 'Show only after final submission';
$string['feedbackimmediate'] = 'Show immediately';
$string['feedbackmode'] = 'Correctness feedback';
$string['findrate'] = 'Find rate';
$string['finishhunt'] = 'Finish error hunt';
$string['found'] = 'Found';
$string['grade'] = 'Grade';
$string['hardesterrors'] = 'Hardest errors for the class';
$string['huntinstruction'] = 'This video contains {$a} expected errors. Find as many as you can.';
$string['huntsettings'] = 'Error hunt';
$string['ifoundanerror'] = 'I found an error';
$string['incorrect'] = 'Incorrect';
$string['incorrectmarks'] = 'Incorrect marks';
$string['inprogress'] = 'In progress';
$string['invalidtimecode'] = 'Enter a valid timecode such as 03:14 or 01:03:14.';
$string['invalidtimerange'] = 'The end time must be a valid timecode equal to or after the start time.';
$string['invalidvideourl'] = 'Enter a valid HTTP or HTTPS video URL.';
$string['invalidvimeourl'] = 'Enter a valid Vimeo video URL.';
$string['invalidyoutubeurl'] = 'Enter a valid YouTube video URL.';
$string['markcorrect'] = 'Correct: this mark matches an expected error.';
$string['markincorrect'] = 'This mark does not match an unused expected error.';
$string['marklimitreached'] = 'The maximum number of marks has been reached.';
$string['markregistered'] = 'Mark registered. Correctness will be shown after final submission.';
$string['marksregistered'] = 'Marks registered';
$string['matchedto'] = 'Matched expected error';
$string['maxmarks'] = 'Maximum number of error marks';
$string['maxmarks_help'] = 'Use 0 for unlimited marks. A limit reduces trial-and-error clicking.';
$string['maxmarkstoosmall'] = 'The maximum number of marks must be 0 (unlimited) or at least the number of expected errors.';
$string['maxplaybackrate'] = 'Maximum playback rate';
$string['missed'] = 'Not found';
$string['missederrorslist'] = 'Expected errors not found';
$string['modulename'] = 'Video Error Hunt';
$string['modulename_help'] = 'Students identify expected errors in a video by marking timestamps and explaining what they observed.';
$string['modulenameplural'] = 'Video Error Hunts';
$string['mustbenonnegative'] = 'This value must be zero or greater.';
$string['noactivities'] = 'There are no Video Error Hunt activities in this course.';
$string['nomarks'] = 'No error marks have been registered yet.';
$string['noreportdata'] = 'No student progress has been recorded yet.';
$string['notstarted'] = 'Not started';
$string['pluginadministration'] = 'Video Error Hunt administration';
$string['pluginname'] = 'Video Error Hunt';
$string['points'] = 'Points';
$string['privacy:metadata:explanation'] = 'The student explanation for a suspected error.';
$string['privacy:metadata:grade'] = 'The calculated activity grade.';
$string['privacy:metadata:lastheartbeat'] = 'The last time the player reported a tracking heartbeat.';
$string['privacy:metadata:marks'] = 'Stores timestamped error observations submitted by students.';
$string['privacy:metadata:percent'] = 'The unique percentage of the video watched.';
$string['privacy:metadata:progress'] = 'Stores video viewing progress and scoring state.';
$string['privacy:metadata:sessionkey'] = 'A random identifier for the current video player session.';
$string['privacy:metadata:sessions'] = 'Stores per-player tracking session sequence and heartbeat data.';
$string['privacy:metadata:timepoint'] = 'The timestamp selected by the student.';
$string['privacy:metadata:userid'] = 'The user ID associated with the data.';
$string['privacy:metadata:watchedsegments'] = 'The video intervals the user actually watched.';
$string['report'] = 'Report';
$string['requiredvideo'] = 'Select a video file.';
$string['requiresubmission'] = 'Require final submission';
$string['result'] = 'Result';
$string['resumeask'] = 'Ask the student';
$string['resumeautomatic'] = 'Resume automatically';
$string['resumefromstart'] = 'Always start at the beginning';
$string['resumeno'] = 'Start over';
$string['resumeplayback'] = 'Resume playback';
$string['resumequestion'] = 'Continue from {$a}?';
$string['resumeyes'] = 'Continue';
$string['score'] = 'Score';
$string['seekblocked'] = 'You can only seek inside parts of the video you have already watched.';
$string['sourceupload'] = 'Upload to Moodle';
$string['sourceurl'] = 'Direct video URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status'] = 'Status';
$string['student'] = 'Student';
$string['studentdetail'] = 'Student detail';
$string['studentsfound'] = 'Students who found it';
$string['submitted'] = 'Submitted';
$string['submittedmessage'] = 'This error hunt has been submitted.';
$string['time'] = 'Time';
$string['timeline'] = 'Video timeline';
$string['timerange'] = 'Accepted range';
$string['videoerrorhunt:addinstance'] = 'Add a new Video Error Hunt';
$string['videoerrorhunt:manageerrors'] = 'Manage expected errors';
$string['videoerrorhunt:submit'] = 'Submit responses in Video Error Hunt';
$string['videoerrorhunt:view'] = 'View Video Error Hunt';
$string['videoerrorhunt:viewreport'] = 'View Video Error Hunt report';
$string['videoerrorhuntname'] = 'Activity name';
$string['videofile'] = 'Video file';
$string['videosettings'] = 'Video';
$string['videosource'] = 'Video source';
$string['vimeourl'] = 'Vimeo URL';
$string['watched'] = 'Watched';
$string['wrongpenalty'] = 'Penalty per incorrect mark';
$string['yourmarks'] = 'Your error marks';
$string['youtubeurl'] = 'YouTube URL';
