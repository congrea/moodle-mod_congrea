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
 * Prints a particular instance of congrea
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
$pageloadstarttime = microtime(true); // Top of page.

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$report = optional_param('report', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT);  // Congrea instance ID - it should be named as the first character of the module.
$delete = optional_param('delete', 0, PARAM_CLEANHTML);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$recname = optional_param('recname', '', PARAM_CLEANHTML);   // Md5 confirmation hash.
$session = optional_param('session', '', PARAM_CLEANHTML);   // Md5 confirmation hash.
$sessionname = optional_param('sessionname', '', PARAM_CLEANHTML);   // Md5 confirmation hash.
$upcomingsession = optional_param('upcomingsession', 0, PARAM_INT);
$psession = optional_param('psession', 0, PARAM_INT);
$sessionsettings = optional_param('sessionsettings', 0, PARAM_INT);
$drodowndisplaymode = optional_param('drodowndisplaymode', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $congrea = $DB->get_record('congrea', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $congrea->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('congrea', $congrea->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('invalidcmidorinsid', 'congrea'));
}
$time = time();
$sessionlist = $DB->get_records('event', array('modulename' => 'congrea', 'courseid' => $course->id, 'instance' => $congrea->id));
usort($sessionlist, "compare_dates_scheduled_list");
$currenttime = time();
if (!empty($sessionlist)) {
    foreach ($sessionlist as $dummysession) {
        if ($dummysession->timeduration == 0) {
            $infinitesessions[] = $dummysession; // Collecting Infinite sessions.
        } else {
            $timestart = ($dummysession->timestart + $dummysession->timeduration);
            if (($timestart < $currenttime) && ($dummysession->repeatid == 0)) { // Past sessions.
                $pastsessions[] = $dummysession;
                continue;
            }
            $timedsessions[] = $dummysession; // Collecting Timed sessions.
        }
    }
}
if (!empty($infinitesessions)) {
    $currentsql = "SELECT id, timestart, timeduration, userid from {event}"
    . " where instance = $congrea->id and modulename = 'congrea' and timeduration = 0";
    $upcomingdata = '';
} else {
    $currentsql = "SELECT id, timestart, timeduration, userid from {event}"
    . " where instance = $congrea->id and modulename = 'congrea' and timestart <= $time and (timestart + (timeduration)) > $time";
    $upcomingsql = "SELECT id, timestart, timeduration, userid from {event}"
    . " where instance = $congrea->id and modulename = 'congrea' and timestart >= $time ORDER BY timestart ASC LIMIT 1";
    $upcomingdata = $DB->get_records_sql($upcomingsql);
}

$currentdata = $DB->get_records_sql($currentsql);

if (empty($currentdata) and empty($upcomingdata)) { // Todo.
    $duration = 0;
    $teacherid = 0;
    $sessionstarttime = 0;
    $sessionendtime = 0;
}
if (!empty($currentdata)) {
    $eventid = congrea_array_key_first($currentdata);
    $sessionstarttime = $currentdata[$eventid]->timestart;
    $duration = $currentdata[$eventid]->timeduration;
    $teacherid = $currentdata[$eventid]->userid;
    $starttime = date("Y-m-d H:i:s", $sessionstarttime);
    $endtime = date('Y-m-d H:i:s', strtotime("+$duration seconds", strtotime($starttime)));
    $sessionendtime = strtotime($endtime);
} else { // Todo.
    if (!empty($upcomingdata)) {
        $eventid = congrea_array_key_first($upcomingdata);
        $sessionstarttime = $upcomingdata[$eventid]->timestart;
        $duration = $upcomingdata[$eventid]->timeduration;
        $teacherid = $upcomingdata[$eventid]->userid;
        $starttime = date("Y-m-d H:i:s", $sessionstarttime);
        $endtime = date('Y-m-d H:i:s', strtotime("+$duration seconds", strtotime($starttime)));
        $sessionendtime = strtotime($endtime);
    }
}
require_login($course, true, $cm);
$coursecontext = context_course::instance($course->id);
$context = context_module::instance($cm->id);
// Print the page header.
$PAGE->set_url('/mod/congrea/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$key = get_config('mod_congrea', 'cgapi');
$secret = get_config('mod_congrea', 'cgsecretpassword');
$room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
echo '<link rel="chrome-webstore-item" href="https://chrome.google.com/webstore/detail/ijhofagnokdeoghaohcekchijfeffbjl">';
// Event log.
$event = \mod_congrea\event\course_module_viewed::create(array(
    'objectid' => $congrea->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('congrea', $congrea);
$event->trigger();

// Mark viewed by user (if required).
$completion = new completion_info($course);
$completion->set_module_viewed($cm);
// Output starts here.
$strdelete = get_string('delete');
$strplay = get_string('play', 'congrea');
$returnurl = new moodle_url('/mod/congrea/view.php', array('id' => $cm->id, 'psession' => true));
// Delete a selected recording, after confirmation.
if ($delete and confirm_sesskey()) {
    require_capability('mod/congrea:recordingdelete', $context);
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strdelete . " " . $congrea->name);
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(
            get_string('deleterecordingfile', 'mod_congrea', $recname),
            new moodle_url($returnurl, $optionsyes),
            $returnurl
        );
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        $postdata = json_encode(array('room' => $room, 'session' => $delete));
        $result = curl_request("https://api.congrea.net/backend/deleterecording", $postdata, $key);
        $success = json_decode($result);
        if ($success->data == "success") {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            $OUTPUT->notification($returnurl, get_string('deletednot', '', $recname));
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($congrea->name);

if (!empty($psession)) {
    $currenttab = 'psession';
} else if (!empty($sessionsettings)) {
    $currenttab = 'sessionsettings';
} else {
    $currenttab = 'upcomingsession';
}

congrea_print_tabs($currenttab, $context, $cm, $congrea);
// Validate https.
$url = parse_url($CFG->wwwroot);
if ($url['scheme'] !== 'https') {
    echo html_writer::tag('div', get_string('httpserror', 'congrea'), array('class' => 'alert alert-error'));
}
// Get audio status.
$audiostatus = $congrea->audio;
$videostatus = $congrea->video;
// Get congrea api key and Secret key from congrea setting.
$a = $CFG->wwwroot . "/admin/settings.php?section=modsettingcongrea";
$role = 's'; // Default role.

if (get_config('mod_congrea', 'allowoverride')) { // If override on.
    if (
        has_capability('mod/congrea:addinstance', $context) && ($USER->id == $teacherid)
    ) {
        if ($congrea->enablerecording) { // From individual setting.
            $recordingstatus = true;
        } else {
            $recordingstatus = false;
        }
    } else {
        if ($congrea->attendeerecording && $congrea->enablerecording) { // For student.
            $recordingstatus = true;
        } else {
            $recordingstatus = false;
        }
    }
} else { // If override off.
    if (
        has_capability('mod/congrea:addinstance', $context) && ($USER->id == $teacherid)
    ) {
        if (get_config('mod_congrea', 'enablerecording')) {
            $recordingstatus = true;
        } else {
            $recordingstatus = false;
        }
    } else {
        if (
            get_config('mod_congrea', 'attendeerecording') &&
            get_config('mod_congrea', 'enablerecording')
        ) { // For student.
            $recordingstatus = true;
        } else {
            $recordingstatus = false;
        }
    }
}
if (has_capability('mod/congrea:sessionpresent', $context) && ($USER->id == $teacherid)) {
    $role = 't';
}

if (!empty($cgapi = get_config('mod_congrea', 'cgapi')) && !empty($cgsecret = get_config('mod_congrea', 'cgsecretpassword'))) {
    $cgcolor = get_config('mod_congrea', 'colorpicker');
    if (strlen($cgsecret) >= 64 && strlen($cgapi) > 32) {
        $authdata = get_auth_data($cgapi, $cgsecret, $recordingstatus, $course, $cm, $role); // Call to authdata.
        $authusername = $authdata->authuser;
        $authpassword = $authdata->authpass;
        $role = $authdata->role;
        $room = $authdata->room;
        $rid = $authdata->path;
        $licensekey = $cgapi;
    } else {
        echo $OUTPUT->notification(get_string('wrongkey', 'congrea', $a));
        echo $OUTPUT->footer();
        exit();
    }
} else {
    echo $OUTPUT->notification(get_string('notsavekey', 'congrea', $a));
    echo $OUTPUT->footer();
    exit();
}

$a = new stdClass();

$a->timestart = userdate($sessionstarttime);
$a->endtime = $sessionstarttime + $duration;
$start = strtotime($sessionstarttime);
$end = strtotime($sessionendtime);
$time = new DateTime("now", core_date::get_user_timezone_object($pageloadstarttime));
$timestamppageload = $time->getTimestamp();
if ($duration != 0) {
    $sessendtime = $sessionstarttime + $duration;
    $timediff = round($sessendtime - $timestamppageload);
} else {
    $timediff = 0;
}
if (userdate($start, '%I:%M %p') == userdate($end , '%I:%M %p')) {
    $a->endtime = userdate($sessionendtime, '%I:%M %p');
}
if ($duration > 86400) {
    $a->endtime = userdate($sessionendtime);
}
if ($duration == 0) {
    $a->endtime = get_string('infinitesession', 'congrea');
}
$user = $DB->get_record('user', array('id' => $teacherid));
$classname = 'wrapper-button';
if (($sessionstarttime > time() && $sessionstarttime <= time())) {
    $classname .= ' online';
}

if (!$psession) {
    if (!empty($sessionstarttime) and !empty($sessionendtime) and !empty($teacherid)) {
        echo html_writer::start_tag('div', array('class' => $classname));
        echo html_writer::tag('div', get_string('congreatiming', 'mod_congrea', $a));
        $presentersobj = congrea_course_teacher_list($id);
        $presentersarray = json_decode(json_encode($presentersobj), true);
        if (array_key_exists($teacherid, $presentersarray) && ($user->deleted != 1) && ($user->suspended != 1)) {
            echo html_writer::tag('div', get_string('livepresenter', 'congrea', $user));
        } else {
            if (has_capability('mod/congrea:managesession', $context)) {
                echo html_writer::tag('h5', ucwords($user->firstname) . " " . ucwords($user->lastname)
                . get_string('notavaliduserwithname', 'congrea'));
            } else {
                echo html_writer::tag('h5', get_string('notavaliduser', 'congrea'));
            }
        }
    } else { // Sessions are past.
        echo html_writer::start_tag('div', array('class' => $classname));
        echo html_writer::tag('div', get_string('notsession', 'mod_congrea'));
        echo html_writer::tag('div', '');
    }
}
// Conditions to show the intro can change to look for own settings or whatever.
if ($congrea->intro) {
    echo $OUTPUT->box(format_module_intro('congrea', $congrea, $cm->id), 'generalbox mod_introbox', 'congreaintro');
}
echo html_writer::empty_tag('br');
// Serve online at vidya.io.
$url = "https://live.congrea.net"; // Online url.
$info = false; // Debugging off.
if ($USER->picture) {
    $userpicture = moodle_url::make_pluginfile_url(context_user::instance($USER->id)->id, 'user', 'icon', null, '/', 'f2');
    $userpicturesrc = $userpicture->out(false);
} else {
    $userpicturesrc = 'noimage';
}
$fromcms = true; // Identify congrea is from cms.
$upload = $CFG->wwwroot . "/mod/congrea/webapi.php?cmid=" . $cm->id . "&methodname=record_file_save";
$webapi = $CFG->wwwroot . "/mod/congrea/webapi.php?cmid=" . $cm->id;
$down = $CFG->wwwroot . "/mod/congrea/play_recording.php?cmid=$cm->id";
$PAGE->requires->js_call_amd('mod_congrea/congrea', 'congreaOnlinePopup');
$PAGE->requires->js_call_amd('mod_congrea/congrea', 'congreaPlayRecording');
$PAGE->requires->js_call_amd('mod_congrea/congrea', 'congreaHideJoin', array($timediff));
if ($CFG->debug == 32767 && $CFG->debugdisplay == 1) {
    $info = true;
}
if (get_config('mod_congrea', 'allowoverride')) { // If override on.
    // General Settings.
    $allowoverride = get_config('mod_congrea', 'allowoverride');
    $studentaudio = $congrea->studentaudio; // Todo for rename.
    $studentvideo = $congrea->studentvideo;
    $studentpc = $congrea->studentpc;
    $studentgc = $congrea->studentgc;
    $askquestion = $congrea->askquestion;
    $userlist = $congrea->userlist;
    $qamarknotes = $congrea->qamarknotes;
    $qaanswer = $congrea->qaanswer;
    $qacomment = $congrea->qacomment;
    $qaupvote = $congrea->qaupvote;
    // Recording Settings.
    if ($congrea->enablerecording) { // If enable recording.
        $enablerecording = $congrea->enablerecording;
        $recallowpresentoravcontrol = $congrea->recallowpresentoravcontrol;
        $showpresentorrecordingstatus = $congrea->showpresentorrecordingstatus;
        $attendeerecording = $congrea->attendeerecording;
        $recattendeeav = $congrea->recattendeeav;
        $recallowattendeeavcontrol = $congrea->recallowattendeeavcontrol;
        $showattendeerecordingstatus = $congrea->showattendeerecordingstatus;
        $trimrecordings = $congrea->trimrecordings;
    } else {
        $enablerecording = 0;
        $recallowpresentoravcontrol = 0;
        $showpresentorrecordingstatus = 0;
        $attendeerecording = 0;
        $recattendeeav = 0;
        $recallowattendeeavcontrol = 0;
        $showattendeerecordingstatus = 0;
        $trimrecordings = 0;
    }
} else { // If override off.
    // General Settings.
    $allowoverride = 0;
    $studentaudio = get_config('mod_congrea', 'studentaudio');
    $studentvideo = get_config('mod_congrea', 'studentvideo');
    $studentpc = get_config('mod_congrea', 'studentpc');
    $studentgc = get_config('mod_congrea', 'studentgc');
    $askquestion = get_config('mod_congrea', 'askQuestion');
    $userlist = get_config('mod_congrea', 'userlist');
    $qamarknotes = get_config('mod_congrea', 'qaMarkNotes');
    $qaanswer = get_config('mod_congrea', 'qaAnswer');
    $qacomment = get_config('mod_congrea', 'qaComment');
    $qaupvote = get_config('mod_congrea', 'qaUpvote');

    if (get_config('mod_congrea', 'enablerecording')) {
        $enablerecording = get_config('mod_congrea', 'enablerecording');
        $recallowpresentoravcontrol = get_config('mod_congrea', 'recAllowpresentorAVcontrol');
        if ($recallowpresentoravcontrol) {
            $showpresentorrecordingstatus = 1;
        } else {
            $showpresentorrecordingstatus = get_config('mod_congrea', 'recShowPresentorRecordingStatus');
        }
        $attendeerecording = get_config('mod_congrea', 'attendeerecording');
        if ($attendeerecording) { // Attendee recording on.
            $recattendeeav = get_config('mod_congrea', 'recattendeeav');
            if (!$recattendeeav) { // If students A/V recording is off then Student’s control over A/V recording should be off.
                $recallowattendeeavcontrol = 0;
            } else {
                $recallowattendeeavcontrol = get_config('mod_congrea', 'recAllowattendeeAVcontrol');
            }
            if ($recallowattendeeavcontrol) {
                $showattendeerecordingstatus = 1;
            } else {
                $showattendeerecordingstatus = get_config('mod_congrea', 'showAttendeeRecordingStatus');
            }
        } else { // Attendee recording off.
            $recattendeeav = 0;
            $recallowattendeeavcontrol = 0;
            $showattendeerecordingstatus = 0;
        }
        $trimrecordings = get_config('mod_congrea', 'trimRecordings');
    } else {
        $enablerecording = 0;
        $recallowpresentoravcontrol = 0;
        $showpresentorrecordingstatus = 0;
        $attendeerecording = 0;
        $recattendeeav = 0;
        $recallowattendeeavcontrol = 0;
        $showattendeerecordingstatus = 0;
        $trimrecordings = 0;
    }
}

$variableobject = (object) array(
    'allowoverride' => $allowoverride,
    'studentaudio' => $studentaudio,
    'studentvideo' => $studentvideo,
    'studentpc' => $studentpc,
    'studentgc' => $studentgc,
    'askquestion' => $askquestion,
    'userlist' => $userlist,
    'enablerecording' => $enablerecording,
    'recallowpresentoravcontrol' => $recallowpresentoravcontrol,
    'showpresentorrecordingstatus' => $showpresentorrecordingstatus,
    'recattendeeav' => $recattendeeav,
    'recallowattendeeavcontrol' => $recallowattendeeavcontrol,
    'showattendeerecordingstatus' => $showattendeerecordingstatus,
    'trimrecordings' => $trimrecordings,
    'attendeerecording' => $attendeerecording,
    'qamarknotes' => $qamarknotes,
    'qaanswer' => $qaanswer,
    'qacomment' => $qacomment,
    'qaupvote' => $qaupvote, 'x6' => 0
);
$hexcode = settingstohex($variableobject); // Todo- for validation.
if ($psession) {
    $joinbutton = true;
} else {
    $joinbutton = false;
}
if (($sessionendtime > time() && $sessionstarttime <= time()) || (!empty($infinitesessions))) {
    $murl = parse_url($CFG->wwwroot);
    if ($murl['scheme'] == 'https') {
        $sendmurl = $CFG->wwwroot;
    } else {
        $sendmurl = str_replace("http://", "https://", $CFG->wwwroot);
    }
        $recordingstatus = false;

    $form = congrea_online_server(
        $url,
        $authusername,
        $authpassword,
        $role,
        $rid,
        $room,
        $upload,
        $down,
        $info,
        $cgcolor,
        $webapi,
        $userpicturesrc,
        $fromcms,
        $licensekey,
        $audiostatus,
        $videostatus,
        $recordingstatus,
        $hexcode,
        $joinbutton,
        $sessionstarttime,
        $sessionendtime
    );
    echo $form;
} else {
    if (!$psession and !empty($sessionstarttime) and !empty($sessionendtime)) {
        echo $OUTPUT->heading(get_string('sessionclosed', 'congrea'));  // Congrea closed print on upcoming session tab.
    }
}
// Upload congrea recording.
$postdata = json_encode(array('room' => $room));
if ($psession) {
    // Recorded session.
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', array('class' => 'wrapper-record-list'));
    $result = curl_request("https://api.congrea.net/backend/recordings", $postdata, $key, $secret);
    $data = attendence_curl_request('https://api.congrea.net/data/analytics/attendance',
    $session, $key, $authpassword, $authusername, $room, $USER->id);
    $attendencestatus = json_decode($data);
    if (!empty($result)) {
        $recdata = json_decode($result);
        $recording = json_decode($recdata->data);
    }

    if (!empty($recording->Items) and !$session) {
        rsort($recording->Items);
        echo $OUTPUT->heading(get_string('recordedsessions', 'mod_congrea'));
    } else if ($session) {
        echo $OUTPUT->heading(get_string('sessionareport', 'mod_congrea'));
    } else {
        echo $OUTPUT->heading(get_string('norecordingtoshow', 'congrea'));
    }
    $table = new html_table();
    $table->head = array(get_string('filename', 'congrea'),
    get_string('timecreated', 'congrea'),
    get_string('viewreport', 'congrea'), get_string('action', 'congrea'));
    $table->colclasses = array('centeralign', 'centeralign');
    $table->attributes['class'] = 'admintable generaltable';
    $table->id = "recorded_data";
    foreach ($recording->Items as $record) {
        $buttons = array();
        $attendence = array();
        $lastcolumn = '';
        $row = array();
        $row[] = $record->name . ' ' . mod_congrea_module_get_rename_action($cm, $record);
        $row[] = userdate($record->time / 1000); // Todo.
        $vcsid = $record->key_room; // Todo.
        // Attendance button.
        if (has_capability('mod/congrea:attendance', $context)) {
            $imageurl = "$CFG->wwwroot/mod/congrea/pix/attendance.png";
            $attendancereport = html_writer::link(
                new moodle_url($returnurl, array('session' => $record->session, 'psession' => true)),
                html_writer::empty_tag('img', array(
                    'src' => $imageurl,
                    'alt' => 'Attendance Report', 'class' => 'attend'
                )),
                array('title' => get_string('viewreport', 'congrea'), 'target' => '_blank')
            );
            $row[] = $attendancereport;
        }
        if (has_capability('mod/congrea:playrecording', $context)) {
            $buttons[] = congrea_online_server_play(
                $url,
                $authusername,
                $authpassword,
                $role,
                $rid,
                $room,
                $upload,
                $down,
                $info,
                $cgcolor,
                $webapi,
                $userpicturesrc,
                $licensekey,
                $id,
                $vcsid,
                $record->session,
                $recordingstatus,
                $hexcode
            );
        }
        // Delete button.
        if (has_capability('mod/congrea:recordingdelete', $context)) {
            $imageurl = "$CFG->wwwroot/mod/congrea/pix/delete.png";
            $buttons[] = html_writer::link(new moodle_url($returnurl, array(
                'delete' => $record->session,
                'recname' => $record->name, 'sesskey' => sesskey()
            )), html_writer::empty_tag('img', array(
                'src' => $imageurl,
                'alt' => $strdelete, 'class' => 'iconsmall'
            )), array('title' => $strdelete));
        }
        $row[] = implode(' ', $buttons);
        $row[] = $lastcolumn;
        if (!has_capability('mod/congrea:attendance', $context)) { // Report view for student.
            $table->head = array(get_string('filename', 'congrea'),
            get_string('timecreated', 'congrea'),
            get_string('action', 'congrea'),
            get_string('attendance', 'congrea'));
            $table->attributes['class'] = 'admintable generaltable studentEnd';
            $apiurl = 'https://api.congrea.net/data/analytics/attendance';
            $data = attendence_curl_request($apiurl, $record->session, $key, $authpassword, $authusername, $room, $USER->id);
            $attendencestatus = json_decode($data);
            if (!empty($attendencestatus->attendance)) {
                $row[] = '<p style="color:green;">P</p>';
            } else {
                $row[] = '<p style="color:red;">A</p>';
            }
        }
        $table->data[] = $row;
    }
}
// Student Report according to session.
if ($session) {
    $table = new html_table();
    $table->head = array(get_string('name', 'congrea'),
    get_string('presence', 'congrea'), get_string('jointime', 'congrea'),
    get_string('exittime', 'congrea'), get_string('recordingviewed', 'congrea'));
    $table->colclasses = array('centeralign', 'centeralign');
    $table->attributes['class'] = 'admintable generaltable attendance';
    if (has_capability('mod/congrea:attendance', $context) || has_capability('mod/congrea:recordingdelete', $context)) {
        $role = 't';
    }
    $authdata = get_auth_data($cgapi, $cgsecret, $recordingstatus, $course, $cm, $role);
    $apiurl = 'https://api.congrea.net/t/analytics/attendance';
    $attendancedata = attendence_curl_request($apiurl, $session, $key, $authdata->authpass, $authdata->authuser, $authdata->room);
    $attendencestatus = json_decode($attendancedata);
    $apiurl2 = 'https://api.congrea.net/t/analytics/attendancerecording';
    $recordingdata = attendence_curl_request($apiurl2, $session, $key, $authdata->authpass, $authdata->authuser, $authdata->room);
    $recordingattendance = json_decode($recordingdata, true);
    $sessionstatus = get_total_session_time($attendencestatus->attendance); // Session time.
    $enrolusers = congrea_get_enrolled_users($id, $COURSE->id); // Enrolled users.
    $laterenrolled = 0;
    $absentstudents = 0;
    if (!empty($attendencestatus) and !empty($sessionstatus)) {
        foreach ($attendencestatus->attendance as $sattendence) {
            if (!empty($sattendence->connect) || !empty($sattendence->disconnect)) { // TODO for isset and uid.
                $attendence[] = $sattendence->uid; // Collect present user id for calculate absent user.
                $studentname = $DB->get_record('user', array('id' => $sattendence->uid));
                if (!empty($studentname)) {
                    $username = $studentname->firstname . ' ' . $studentname->lastname; // Todo-for function.
                } else {
                    $username = get_string('nouser', 'mod_congrea');
                }
                $connect = json_decode($sattendence->connect);
                $disconnect = json_decode($sattendence->disconnect);
                $studentsstatus = calctime($connect, $disconnect, $sessionstatus->sessionstarttime, $sessionstatus->sessionendtime);
                if (
                    !empty($studentsstatus->totalspenttime)
                    and $sessionstatus->totalsessiontime >= $studentsstatus->totalspenttime
                ) {
                    $presence = ($studentsstatus->totalspenttime * 100) / $sessionstatus->totalsessiontime;
                } else if ($studentsstatus->totalspenttime > $sessionstatus->totalsessiontime) {
                    $presence = 100; // Special case handle.
                } else {
                    $presence = '-';
                }
            }
            if (!empty(recording_view($sattendence->uid, $recordingattendance))) {
                $recview = recording_view($sattendence->uid, $recordingattendance);
                if ($recview->totalviewd < 60) {
                    $totalseconds = $recview->recodingtime;
                    $rectotalviewedpercent = round(($recview->totalviewd * 100) / $totalseconds);
                    $recviewed = $recview->totalviewd . ' ' . get_string('secs', 'congrea');
                } else {
                    $recviewed = round($recview->totalviewd / 60) . get_string('mins', 'congrea');
                    $rectotalviewedpercent = $recview->totalviewedpercent;
                }
            } else {
                $rectotalviewedpercent = 0;
                $recviewed = '-';
            }
            if (has_capability('mod/congrea:attendance', $context)) {
                if (!empty($studentsstatus->totalspenttime)) {
                    $table->data[] = array(
                        $username, $studentsstatus->totalspenttime . ' ' .
                        get_string('mins', 'congrea'), date('g:i A', $studentsstatus->starttime),
                        date('g:i A', $studentsstatus->endtime), $recviewed
                    );
                } else {
                    $table->data[] = array(
                        $username,  '<p style = "color: red;">A</p>', date('g:i A', $studentsstatus->starttime),
                        date('g:i A', $studentsstatus->endtime), $recviewed
                    );
                    $absentstudents++;
                }
            } else {
                if (!empty($studentsstatus->totalspenttime)) {
                    $table->data[] = array($username, $studentsstatus->totalspenttime . ' ' . get_string('mins', 'congrea'),
                    date('g:i A', $studentsstatus->starttime),
                    date('g:i A', $studentsstatus->endtime), $recviewed
                    );
                } else {
                    $table->data[] = array($username,  '<p style = "color: red;">A</p>',
                    date('g:i A', $studentsstatus->starttime),
                    date('g:i A', $studentsstatus->endtime), $recviewed
                    );
                    $absentstudents++;
                }
            }
        }
        if (!empty($attendence)) {
            if (!empty($enrolusers)) {
                $result = array_diff($enrolusers, $attendence);
            } else {
                echo get_string('notenrol', 'mod_congrea');
            }
            foreach ($result as $data) {
                $studentname = $DB->get_record('user', array('id' => $data));
                if (!empty($studentname)) {
                    $username = $studentname->firstname . ' ' . $studentname->lastname;
                } else {
                    $username = get_string('nouser', 'mod_congrea');
                }
                if (!empty(recording_view($data,  $recordingattendance))) {
                    $recview = recording_view($data, $recordingattendance);
                    if ($recview->totalviewd < 60) {
                        $totalseconds = $recview->recodingtime;
                        $rectotalviewedpercent = round(($recview->totalviewd * 100) / $totalseconds);
                        $recviewed = $recview->totalviewd . ' ' . get_string('secs', 'congrea');
                    } else {
                        $recviewed = round($recview->totalviewd / 60) . get_string('mins', 'congrea');
                        $rectotalviewedpercent = $recview->totalviewedpercent;
                    }
                } else {
                    $rectotalviewedpercent = 0;
                    $recviewed = '-';
                }
                if (in_array($studentname->id, $enrolusers)) {
                    if ($DB->record_exists('user_enrolments', array('userid' => $studentname->id))) {
                        $enrol = $DB->get_record('enrol', array('courseid' => $course->id), 'id', $strictness = IGNORE_MULTIPLE);
                        $enrolledontimestamp = $DB->get_record_sql("SELECT timestart from {user_enrolments}" .
                        " where enrolid = $enrol->id and userid = $studentname->id");
                        $dbuserenrolled = $DB->get_record('user_enrolments',
                        array('userid' => $studentname->id, 'enrolid' => $enrol->id), 'timestart',
                        $strictness = IGNORE_MULTIPLE);
                        $enrolledon = date('Y-m-d H:i', $dbuserenrolled->timestart);
                        if (strtotime($enrolledon) > ($sessionstatus->sessionendtime)) {
                            $table->data[] = array($username,
                            '<p style = "color: green;">' . get_string('enrolledlater', 'congrea') . '</p>', '-', '-', $recviewed);
                            $laterenrolled++;
                        } else {
                            $table->data[] = array($username, '<p style = "color: red;">A</p>', '-', '-', $recviewed);
                            $absentstudents++;
                        }
                    }
                }
            }
        } else {
            echo get_string('sessionnouser', 'mod_congrea');
        }
    } else {
        echo get_string('sessionnouser', 'mod_congrea');
    }
}
if (!empty($table->data) and !$session) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}
if (!empty($table) and $session and $sessionstatus) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    $presentusers = count($enrolusers) - $absentstudents - $laterenrolled;
    $present = '<h5><strong>' . date('D, d-M-Y, g:i A', $sessionstatus->sessionstarttime) .
    ' to ' . date('g:i A', $sessionstatus->sessionendtime) .
    '</strong></h5><strong>' .
    get_string('sessionduration', 'congrea') . '</strong>' . $sessionstatus->totalsessiontime . ' ' .
    get_string('mins', 'congrea') . '</br>' . '<strong>' .
    get_string('absent', 'congrea') . '</strong>' . $absentstudents . '</br><strong>' .
    get_string('present', 'congrea') . '</strong>'
    . $presentusers . '</br></br>';
    echo html_writer::tag('div', $present, array('class' => 'present'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}
if (!$psession) {
    echo html_writer::tag('div', "", array('class' => 'clear'));
    echo html_writer::end_tag('div');
    echo '</br>';
}
if ($upcomingsession || $upcomingsession == 0 and !$psession) {
    if (!empty(congrea_get_records($congrea, 1))) {
        congrea_print_dropdown_form($cm->id, $drodowndisplaymode);
    }
    if ($drodowndisplaymode == 1 || $drodowndisplaymode == 0 and !$psession) {
        congrea_get_records($congrea, 7);
    } else if ($drodowndisplaymode == 2) {
        congrea_get_records($congrea, 30);
    } else if ($drodowndisplaymode == 3) {
        congrea_get_records($congrea, 90);
    }
}
echo $OUTPUT->footer();