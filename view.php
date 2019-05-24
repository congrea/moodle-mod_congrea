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
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$report = optional_param('report', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT);  // Congrea instance ID - it should be named as the first character of the module.
$delete = optional_param('delete', 0, PARAM_CLEANHTML);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.
$recname = optional_param('recname', '',  PARAM_CLEANHTML);   // Md5 confirmation hash.
$session = optional_param('session', '',  PARAM_CLEANHTML);   // Md5 confirmation hash.
$sessionname = optional_param('sessionname', '',  PARAM_CLEANHTML);   // Md5 confirmation hash.
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $congrea = $DB->get_record('congrea', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $congrea->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('congrea', $congrea->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
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
$returnurl = new moodle_url('/mod/congrea/view.php', array('id' => $cm->id));
// Delete a selected recording, after confirmation.
if ($delete and confirm_sesskey()) {
    require_capability('mod/congrea:recordingdelete', $context);
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strdelete . " " . $congrea->name);
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('deleterecordingfile', 'mod_congrea', $recname),
                            new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        $postdata = json_encode(array('room' => $room, 'session' => $delete));
        $result = curl_request("https://api.congrea.net/backend/deleterecording", $postdata, $key);
        $sucess = json_decode($result);
        if ($sucess->data == "success") {
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
$recording = $congrea->cgrecording;

// Dorecording have manager and teacher and nonediting teacher Permission.
if (has_capability('mod/congrea:addinstance', $context) &&
        ($USER->id == $congrea->moderatorid)) {
    $role = 't';
} else if (has_capability('mod/congrea:dorecording', $context) and $session) {
    $role = 't';
}

if (!empty($cgapi = get_config('mod_congrea', 'cgapi')) && !empty($cgsecret = get_config('mod_congrea', 'cgsecretpassword'))) {
    $cgcolor = get_config('mod_congrea', 'colorpicker');
    if (strlen($cgsecret) >= 64 && strlen($cgapi) > 32) {
        require_once('auth.php');
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
$a->open = userdate($congrea->opentime);
$a->close = userdate($congrea->closetime);
$user = $DB->get_record('user', array('id' => $congrea->moderatorid));

$classname = 'wrapper-button';
if (($congrea->closetime > time() && $congrea->opentime <= time())) {
    $classname .= ' online';
}
echo html_writer::start_tag('div', array('class' => $classname));

echo html_writer::tag('div', get_string('congreatiming', 'mod_congrea', $a));
if (!empty($congrea->moderatorid)) {
    echo html_writer::tag('div', get_string('teachername', 'mod_congrea', $user));
} else {
    echo html_writer::tag('div', 'Moderator : None');
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
if ($CFG->debug == 32767 && $CFG->debugdisplay == 1) {
    $info = true;
}
// Check congrea is open.
if ($congrea->closetime > time() && $congrea->opentime <= time()) {
    $murl = parse_url($CFG->wwwroot);
    if ($murl['scheme'] == 'https') {
        $sendmurl = $CFG->wwwroot;
    } else {
        $sendmurl = str_replace("http://", "https://", $CFG->wwwroot);
    }
    // Todo this should be changed with actual server path.
    if ($session) {
        $joinbutton = true; // Not display join button.
    } else {
        $joinbutton = false;
    }
    $form = congrea_online_server($url, $authusername, $authpassword,
                                    $role, $rid, $room, $upload,
                                    $down, $info, $cgcolor, $webapi,
                                    $userpicturesrc, $fromcms, $licensekey, $audiostatus, $videostatus,
                                    $congrea->cgrecording, $joinbutton);
    echo $form;
} else {
    // Congrea closed.
    echo $OUTPUT->heading(get_string('sessionclosed', 'congrea'));
}
// Upload congrea recording.
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', array('class' => 'wrapper-record-list'));
$postdata = json_encode(array('room' => $room));
$result = curl_request("https://api.congrea.net/backend/recordings", $postdata, $key, $secret);
if (!empty($result)) {
    $data = json_decode($result);
    $recording = json_decode($data->data);
}
if (!empty($recording->Items) and ! $session) {
    rsort($recording->Items);
    echo $OUTPUT->heading('Recorded sessions');
} else if ($session) {
    echo $OUTPUT->heading(get_string('sessionareport', 'mod_congrea'));
} else {
    echo $OUTPUT->heading('There are no recording to show');
}
$table = new html_table();
$table->head = array('Filename', 'Time created', 'Action');
$table->colclasses = array('centeralign', 'centeralign');
$table->attributes['class'] = 'admintable generaltable';
$table->id = "recorded_data";
foreach ($recording->Items as $record) {
    $buttons = array();
    $attendence = array();
    $connecttime = array();
    $disconnecttime = array();
    $lastcolumn = '';
    $row = array();
    $arow = array();
    $row[] = $record->name . ' ' .mod_congrea_module_get_rename_action($cm, $record);
    $row[] = userdate($record->time / 1000); // Todo: for exact time.
    $vcsid = $record->key_room; // Todo.
    if (has_capability('mod/congrea:playrecording', $context)) {
        $buttons[] = congrea_online_server_play($url, $authusername, $authpassword, $role,
                                                $rid, $room, $upload, $down,
                                                $info, $cgcolor, $webapi,
                                                $userpicturesrc, $licensekey, $id,
                                                $vcsid, $record->session, $congrea->cgrecording);
    }
    // Attendance button.
    if (has_capability('mod/congrea:recordingdelete', $context)) { // TODO.
        $imageurl = "$CFG->wwwroot/mod/congrea/pix/attendance.png";
        $buttons[] = html_writer::link(new moodle_url($returnurl, array('session' => $record->session)),
                         html_writer::empty_tag('img', array('src' => $imageurl,
                        'alt' => 'Attendance Report', 'class' => 'attend')), array('title' => 'Attendance Report'));
    }
    // Delete button.
    if (has_capability('mod/congrea:recordingdelete', $context)) {
        $imageurl = "$CFG->wwwroot/mod/congrea/pix/delete.png";
        $buttons[] = html_writer::link(new moodle_url($returnurl, array('delete' => $record->session,
                        'recname' => $record->name, 'sesskey' => sesskey())),
                         html_writer::empty_tag('img', array('src' => $imageurl,
                        'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
    }
    $row[] = implode(' ', $buttons);
    $row[] = $lastcolumn;
    if (!get_role($COURSE->id, $USER->id)) { // Report for student.
        $table->head = array('Filename', 'Time created', 'Action', "Attandence");
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
// Student Report according to session.
if ($session) {
    $table = new html_table();
    $table->head = array('Student Name', 'Start Time', 'Exit Time', 'Duration Attended', 'Presence', 'Attendance');
    $table->colclasses = array('centeralign', 'centeralign');
    $table->attributes['class'] = 'admintable generaltable';
    $apiurl = 'https://api.congrea.net/t/analytics/attendance';
    $data = attendence_curl_request($apiurl, $session, $key, $authpassword, $authusername, $room); // TODO.
    $attendencestatus = json_decode($data);
    $sessionstatus = get_total_session_time($attendencestatus->attendance); // Session time.
    $enrolusers = congrea_get_enrolled_users($id, $COURSE->id);
    if (!empty($attendencestatus) and ! empty($sessionstatus)) {
        foreach ($attendencestatus->attendance as $sattendence) {
            if (!get_role($COURSE->id, $USER->id)) {
                continue;
            }
            if (!empty($sattendence->connect) || !empty($sattendence->disconnect)) { // TODO for isset and uid.
                $attendence[] = $sattendence->uid; // Collect present user id for calculate absent user.
                $studentname = $DB->get_record('user', array('id' => $sattendence->uid));
                if (!empty($studentname)) {
                    $username = $studentname->firstname . ' ' . $studentname->lastname;
                } else {
                    $username = get_string('nouser', 'mod_congrea');
                }
                $connect = json_decode($sattendence->connect);
                $disconnect = json_decode($sattendence->disconnect);
                $studentsstatus = calctime($connect, $disconnect, $sessionstatus->sessionstarttime, $sessionstatus->sessionendtime);
                if (!empty($studentsstatus->totalspenttime) and
                        $sessionstatus->totalsessiontime >= $studentsstatus->totalspenttime) {
                    $presence = ($studentsstatus->totalspenttime * 100) / $sessionstatus->totalsessiontime;
                } else {
                    $presence = '-';
                }
            }
            $table->data[] = array($username, date('y-m-d h:i:s', $studentsstatus->starttime),
                date('y-m-d h:i:s', $studentsstatus->endtime), $studentsstatus->totalspenttime . ' ' . 'minutes',
                round($presence) . '%', '<p style="color:green;">P</p>');
        }
        if (!empty($attendence)) {
            if (!empty($enrolusers)) {
                $result = array_diff($enrolusers, $attendence);
            } else {
                echo get_string('notenrol', 'mod_congrea');
            }
            foreach ($result as $data) {
                $studentname = $DB->get_record('user', array('id' => $data));
                $username = $studentname->firstname . ' ' . $studentname->lastname;
                $table->data[] = array($username, '-', '-', '-', '-', '<p style="color:red;">A</p>');
            }
        } else {
            echo get_string('absentuser', 'mod_congrea');
        }
    } else {
        echo get_string('absentsessionuser', 'mod_congrea');
    }
}

if (!empty($table->data) and !$session) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

if (!empty($table) and $session and $sessionstatus) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    $countenroluser = count($enrolusers);
    $presentnroluser = count($attendence);
    $upsentuser = $countenroluser - $presentnroluser;
    $present = '<b> Session Duration: </b>' . $sessionstatus->totalsessiontime.' '.
    'minutes'.'</br>'.'<b> Students Absent: </b>'. $upsentuser .'</br>'. '<b> Students Present: </b>' . $presentnroluser;
    echo html_writer::tag('div', $present, array('class' => 'present'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

echo html_writer::tag('div', "", array('class' => 'clear'));
echo html_writer::end_tag('div');
// Finish the page.
echo $OUTPUT->footer();
