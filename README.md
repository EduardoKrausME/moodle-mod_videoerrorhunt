# Video Error Hunt

Moodle activity module for systematic identification of errors inside videos.

Teachers define expected errors using time ranges and individual point values. Students watch the video, click **I found
an error**, and explain what they observed. The activity records the current timestamp, checks whether it belongs to an
expected range, prevents duplicate credit, optionally applies penalties for incorrect marks, and can reveal correctness
immediately or only after submission.

Main features:

- Video sources: Moodle upload, direct video URL, YouTube, and Vimeo.
- Real watched-segment tracking with heartbeat, unique watched percentage, total watch time, resume position, and local
  retry queue.
- Optional seek restriction to already watched regions.
- Expected errors with start/end ranges, descriptions, ordering, and individual points.
- Configurable maximum number of marks and penalty for incorrect marks.
- Immediate feedback or feedback only after final submission.
- Student timeline with watched segments and clickable error marks.
- Moodle gradebook integration and custom activity completion.
- Teacher report with watched percentage, found/missed errors, incorrect marks, score, grade, submission status, and
  hardest errors for the class.
- Backup/restore and Moodle Privacy API support.

Requires Moodle 4.4 or later.

The player/tracking architecture follows the same design goals as `mod_videoprogress`: server-authoritative watched
segments, resume support, source abstraction, AJAX web services, completion and reporting.

## Installation

Copy the `videoerrorhunt` directory to `mod/videoerrorhunt` in the Moodle installation, then visit **Site
administration > Notifications** to install or upgrade the database tables.

The activity requires Moodle 4.4 or later. It does not require external libraries. YouTube and Vimeo playback load their
official player APIs only when those sources are selected.
