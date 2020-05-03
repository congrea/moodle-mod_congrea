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
 * Internal library of functions for module congrea
 *
 * All the congrea specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/calendar/lib.php');
require_once(dirname(__FILE__) . '/lib.php');

define('SEVEN_DAYS', 1);
define('THIRTY_DAYS', 2);
define('THREE_MONTH', 3);

/**
 * Get list of teacher of current course
 * serving for virtual class
 * @param int $cmid
 * @return object
 */
function congrea_course_teacher_list($cmid) {

    $modcontext = context_module::instance($cmid);
    $heads = get_users_by_capability($modcontext, 'mod/congrea:sessionpresent');

    $teachers = array();
    foreach ($heads as $head) {
        $teachers[$head->id] = fullname($head);
    }
    return $teachers;
}

/**
 * Create form and send sumbitted value to
 * given url and open in popup
 *
 * @param string $url congrea online url
 * @param string $authusername  authenticated user
 * @param string $authpassword  authentication password
 * @param string $role user role eight student or teacher
 * @param string $rid user authenticated path
 * @param string $room  unique id
 * @param string $upload
 * @param string $down
 * @param boolean $debug
 * @param string $cgcolor
 * @param string $webapi
 * @param string $userpicturesrc
 * @param string $fromcms
 * @param string $licensekey
 * @param string $audiostatus
 * @param string $videostatus
 * @param string $recording
 * @param string $hexcode
 * @param boolean $joinbutton
 * @param integer $sstart
 * @param integer $send
 * @return string
 */
function congrea_online_server(
    $url,
    $authusername,
    $authpassword,
    $role,
    $rid,
    $room,
    $upload,
    $down,
    $debug = false,
    $cgcolor,
    $webapi,
    $userpicturesrc,
    $fromcms,
    $licensekey,
    $audiostatus,
    $videostatus,
    $recording = false,
    $hexcode,
    $joinbutton = false,
    $sstart,
    $send
) {
    global $USER;
    $username = $USER->firstname . ' ' . $USER->lastname;
    $form = html_writer::start_tag('form', array('id' => 'overrideform', 'action' => $url, 'method' => 'post'));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uid', 'value' => $USER->id));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'name', 'value' => $username));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'role', 'value' => $role));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'room', 'value' => $room));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sid', 'value' => $USER->sesskey));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'user', 'value' => $authusername));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pass', 'value' => $authpassword));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'rid', 'value' => $rid));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'upload', 'value' => $upload));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'down', 'value' => $down));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'debug', 'value' => $debug));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'congreacolor', 'value' => $cgcolor));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'webapi', 'value' => $webapi));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userpicture', 'value' => $userpicturesrc));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'fromcms', 'value' => $fromcms));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'licensekey', 'value' => $licensekey));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'audio', 'value' => $audiostatus));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'video', 'value' => $videostatus));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recording', 'value' => $recording));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'settings', 'value' => $hexcode));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sstart', 'value' => $sstart));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'send', 'value' => $send));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'expectedendtime'));
    if (!$joinbutton) {
        if ($role == 't') {
            $form .= html_writer::empty_tag('input', array(
                'type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton',
                'value' => get_string('joinasteacher', 'congrea')
            ));
        } else {
            $form .= html_writer::empty_tag('input', array(
                'type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton',
                'value' => get_string('joinasstudent', 'congrea')
            ));
        }
    }
    $form .= html_writer::end_tag('form');
    return $form;
}

// TODO, this function should be merge with congrea_online_server.
/**
 * Create form and send sumbitted value to
 * given url
 *
 * @param string $url congrea online url
 * @param string $authusername  authenticated user
 * @param string $authpassword  authentication password
 * @param string $role user role eight student or teacher
 * @param string $rid user authenticated path
 * @param string $room unique id
 * @param string $upload
 * @param string $down
 * @param boolean $debug
 * @param string $cgcolor
 * @param string $webapi
 * @param string $userpicturesrc
 * @param string $licensekey
 * @param int $id
 * @param int $vcsid
 * @param string $recordingsession
 * @param string $recording
 * @param string $hexcode
 * @return string
 */
function congrea_online_server_play(
    $url,
    $authusername,
    $authpassword,
    $role,
    $rid,
    $room,
    $upload,
    $down,
    $debug = false,
    $cgcolor,
    $webapi,
    $userpicturesrc,
    $licensekey,
    $id,
    $vcsid,
    $recordingsession = false,
    $recording = false,
    $hexcode
) {
    global $USER;
    $username = $USER->firstname . ' ' . $USER->lastname;
    $form = html_writer::start_tag('form', array(
        'id' => 'playRec' . $vcsid, 'class' => 'playAct',
        'action' => $url, 'method' => 'post'
    ));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uid', 'value' => $USER->id));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'name', 'value' => $username));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'role', 'value' => $role));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'room', 'value' => $room));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sid', 'value' => $USER->sesskey));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'user', 'value' => $authusername));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pass', 'value' => $authpassword));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'rid', 'value' => $rid));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'upload', 'value' => $upload));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'down', 'value' => $down));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'debug', 'value' => $debug));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'congreacolor', 'value' => $cgcolor));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'webapi', 'value' => $webapi));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userpicture', 'value' => $userpicturesrc));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'licensekey', 'value' => $licensekey));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'vcSid', 'value' => $vcsid));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'session', 'value' => $recordingsession));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recording', 'value' => $recording));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'settings', 'value' => $hexcode));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'play', 'value' => 1));
    $form .= html_writer::empty_tag('input', array(
        'type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton playbtn',
        'value' => '', 'title' => 'Play'
    ));
    $form .= html_writer::end_tag('form');
    return $form;
}

/**
 * Update the calendar entries for this congrea.
 *
 * @param object $congrea
 * @param object $data
 * @return bool
 */
function mod_congrea_update_calendar($congrea, $data) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');
    $endtime = ($data->fromsessiondate + $data->timeduration);
    if ($data->fromsessiondate && $endtime) {
        $event = new stdClass();
        $event->name = $congrea->name;
        $event->timestart = $data->fromsessiondate; // Change because of sessionsettings.
        // Convert the links to pluginfile. It is a bit hacky but at this stage the files.
        // Might not have been saved in the module area yet.
        $intro = $congrea->intro;
        if ($draftid = file_get_submitted_draft_itemid('introeditor')) {
            $intro = file_rewrite_urls_to_pluginfile($intro, $draftid);
        }
        // We need to remove the links to files as the calendar is not ready.
        // to support module events with file areas.
        $intro = strip_pluginfile_content($intro);
        $event->description = array(
            'text' => $intro,
            'format' => $congrea->introformat
        );
        $event->addmultiple = 0;
        $event->courseid = $congrea->course;
        $event->groupid = 0;
        $event->userid = $data->moderatorid;
        $event->modulename = 'congrea';
        $event->instance = $congrea->id;
        $event->description = '-';
        $event->eventtype = 'start session';
        $event->repeatid = 0;
        $event->timeduration = $data->timeduration;
        calendar_event::create($event);
    } else {
        $DB->delete_records('event', array('modulename' => 'congrea', 'instance' => $congrea->id));
    }
}
/**
 * Update the calendar entries for this congrea on upgrade script (For past entries after we upgrade plugin).
 *
 * @param object $congreaname
 * @param int $startime
 * @param int $endtime
 * @param int $courseid
 * @param int $teacherid
 * @param int $instanceid
 * @param int $sessionid
 * @param int $timeduration
 *
 * @return bool
 */
function mod_congrea_update_calendar_on_upgrade($congreaname, $startime, $endtime, $courseid,
$teacherid, $instanceid, $sessionid, $timeduration) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');
    if ($startime && $endtime) {
        $event = new stdClass();
        $event->name = $congreaname;
        $event->timestart = $startime; // Change because of sessionsettings.
        $event->courseid = $courseid;
        $event->groupid = 0;
        $event->userid = $teacherid;
        $event->modulename = 'congrea';
        $event->instance = $instanceid;
        $event->eventtype = $sessionid;
        $event->timeduration = $timeduration * 60;
        calendar_event::create($event);
    } else {
        $DB->delete_records('event', array('modulename' => 'congrea', 'instance' => $instanceid));
    }
}
/**
 * Repeat calendar entries for this congrea.
 *
 * @param object $congrea
 * @param object $data
 * @param int $startdate
 * @param int $presenter
 * @param int $repeatid
 * @param int $weeks
 * @return bool
 */
function repeat_calendar($congrea, $data, $startdate, $presenter, $repeatid, $weeks) {
    $event = new stdClass();
    $event->name = $congrea->name;
    $event->description = $data->description;
    $event->timestart = $startdate;
    $event->format = 1;
    $event->courseid = $congrea->course;
    $event->groupid = 0;
    $event->userid = $presenter;
    $event->repeatid = $repeatid;
    $event->modulename = 'congrea';
    $event->instance = $congrea->id;
    $event->eventtype = 'session start';
    $event->timeduration = $data->timeduration;
    calendar_event::create($event);
}

/**
 * Delete recorded files with folder.
 *
 * @param string $directory - Path of folder where
 * recording files of one session has been stored.
 * @param boolean $empty
 * @return bool
 */
function mod_congrea_deleteall($directory, $empty = false) {
    if (substr($directory, -1) == "/") {
        $directory = substr($directory, 0, -1);
    }
    if (!file_exists($directory) || !is_dir($directory)) {
        return false;
    } else if (!is_readable($directory)) {
        return false;
    } else {
        $directoryhandle = opendir($directory);
        while ($contents = readdir($directoryhandle)) {
            if ($contents != '.' && $contents != '..') {
                $path = $directory . "/" . $contents;
                if (is_dir($path)) {
                    mod_congrea_deleteall($path);
                } else {
                    unlink($path);
                }
            }
        }
        closedir($directoryhandle);
        if ($empty == false) {
            if (!rmdir($directory)) {
                return false;
            }
        }
        return true;
    }
}

/**
 * Returns the rename action.
 *
 * @param object $cm The module to produce editing buttons for
 * @param object $instance
 * @param int $sr The section to link back to (used for creating the links)
 * @return The markup for the rename action, or an empty string if not available.
 */
function mod_congrea_module_get_rename_action($cm, $instance, $sr = null) {
    global $COURSE, $OUTPUT, $USER;

    static $str;
    static $baseurl;

    $modcontext = context_module::instance($cm->id);
    $hasmanageactivities = has_capability('mod/congrea:addinstance', $modcontext);
    if (!isset($str)) {
        $str = get_strings(array('edittitle'));
    }
    if (!isset($baseurl)) {
        $baseurl = new moodle_url('edit.php', array('id' => $cm->id, 'sesskey' => sesskey()));
    }
    if ($sr !== null) {
        $baseurl->param('sr', $sr);
    }
    if ($hasmanageactivities) {
        // We will not display link if we are on some other-course page (where we should not see this module anyway).
        return html_writer::span(
            html_writer::link(
                new moodle_url($baseurl, array('update' => $instance->session, 'sessionname' => $instance->name)),
                $OUTPUT->pix_icon(
                    't/editstring',
                    '',
                    'moodle',
                    array('class' => 'iconsmall visibleifjs', 'title' => '')
                ),
                array(
                    'class' => 'editing_title',
                    'data-action' => 'edittitle',
                    'title' => $str->edittitle,
                )
            )
        );
    }
    return '';
}

/**
 * Generate random string of specified length
 *
 * @param int $length - length of random string
 * @return bool
 */
function mod_congrea_generaterandomstring($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characterslength = strlen($characters);
    $randomstring = '';
    for ($i = 0; $i < $length; $i++) {
        $randomstring .= $characters[rand(0, $characterslength - 1)];
    }
    return $randomstring;
}

/**
 * This function authenticate the user with required
 * detail and request for sever connection
 *
 * @param string $url congrea auth server url
 * @param array $postdata
 * @param string $key
 * @param string $secret
 *
 * @return string $resutl json_encoded object
 */
function curl_request($url, $postdata, $key, $secret = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    if ($secret) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'x-api-key:' . $key,
            'x-congrea-secret:' . $secret,
        ));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'x-api-key:' . $key,
        ));
    }
    curl_setopt($ch, CURLOPT_TRANSFERTEXT, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, false);
    $result = @curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * This function authenticate the user attendence
 * detail and request for sever connection
 *
 * @param string $apiurl congrea auth server url
 * @param string $sessionid
 * @param string $key
 * @param string $authpass
 * @param string $authuser
 * @param string $room
 * @param int $uid
 *
 * @return string $resutl json_encoded object
 */
function attendence_curl_request($apiurl, $sessionid, $key, $authpass, $authuser, $room, $uid = false) {
    $curl = curl_init();
    $data = json_encode(array('session' => $sessionid, 'uid' => $uid));
    curl_setopt_array($curl, array(
        CURLOPT_URL => "$apiurl",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "x-api-key: $key",
            "x-congrea-authpass: $authpass",
            "x-congrea-authuser: $authuser",
            "x-congrea-room: $room"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return $response;
    }
}

/**
 * Returns list of users enrolled into course
 * serving for virtual class
 *
 * @param int $cmid
 * @param int $courseid
 * @return array of user records
 */
function congrea_get_enrolled_users($cmid, $courseid) {
    global $DB, $OUTPUT, $CFG;
    if (!empty($cmid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error(get_string('incorrectcmid', 'congrea'));
        }
        $context = context_module::instance($cm->id);
        $withcapability = '';
        $groupid = 0;
        $userfields = "u.*";
        $orderby = null;
        $limitfrom = 0;
        $limitnum = 0;
        $onlyactive = false;
        list($esql, $params) = get_enrolled_sql($context, $withcapability, $groupid, $onlyactive);
        $sql = "SELECT $userfields
             FROM {user} u
             JOIN ($esql) je ON je.id = u.id
            WHERE u.deleted = 0";

        if ($orderby) {
            $sql = "$sql ORDER BY $orderby";
        } else {
            list($sort, $sortparams) = users_order_by_sql('u');
            $sql = "$sql ORDER BY $sort";
            $params = array_merge($params, $sortparams);
        }
        $list = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        if (!empty($list)) {
            foreach ($list as $userdata) {
                if ($userdata) {
                    $user = $userdata->id;
                    $userlist[] = $user;
                }
            }
            if (!empty($userlist)) {
                return $userlist; // Return list of enrolled users.
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
            json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        json_encode($unsuccess);
    }
}

/**
 * Get user role.
 * serving for virtual class
 *
 * @param int $courseid
 * @param int $userid
 * @return int of user id
 */
function get_role($courseid, $userid) {
    $rolestr = array();
    $adminrole = array();
    $context = context_course::instance($courseid);
    $roles = get_user_roles($context, $userid);
    foreach ($roles as $role) {
        $rolestr[] = role_get_name($role, $context);
    }
    if (!in_array("Student", $rolestr)) { // TODO.
        return $userid; // Teacher.
    } else {
        return false;
    }
}

/**
 * Get total session time.
 * serving for virtual class
 *
 * @param object $attendance
 * @return user object
 */
function get_total_session_time($attendance) {
    if (!empty($attendance)) {
        foreach ($attendance as $data) {
            $connect = json_decode($data->connect);
            $disconnect = json_decode($data->disconnect);
            if (!empty($connect)) {
                sort($connect);
                $connecttime[] = current($connect);
            }
            if (!empty($disconnect)) {
                sort($disconnect);
                $disconnecttime[] = end($disconnect);
            }
        }
    }
    if (!empty($connecttime) and !empty($disconnecttime)) {
        $sessionstarttime = min($connecttime);
        $sessionendtime = max($disconnecttime);
        $totaltime = round(($sessionendtime - $sessionstarttime) / 60); // Total session time in minutes.
        return (object) array(
            'totalsessiontime' => $totaltime,
            'sessionstarttime' => $sessionstarttime, 'sessionendtime' => $sessionendtime
        ); // TODO.
    }
}

/**
 * Get total student time.
 * serving for virtual class
 *
 * @param array $connect
 * @param array $disconnect
 * @param int $x
 * @param int $y
 * @return int minutes of student.
 */
function calctime($connect, $disconnect, $x, $y) {
    if (!empty($connect)) {
        sort($connect);
    }
    if (!empty($disconnect)) {
        sort($disconnect);
    }
    // Step-2 Do we need x in connect.
    if (empty($connect)) {
        $connect[] = $x;
    }
    // Step-3 Do we need y in disconnect.
    if (empty($disconnect)) {
        $disconnect[] = $y;
    }
    if (!empty($connect) and !empty($disconnect)) {
        if ($connect[0] > $disconnect[0]) {
            $connect[0] = $x;
        }
    }
    $lastconnect = count($connect) - 1;
    $lastdisconnect = count($disconnect) - 1;
    if ($connect[$lastconnect] > $disconnect[$lastdisconnect]) {
        $disconnect[$lastconnect] = $y;
    }
    // Step 4 work on middle values.
    $clen = count($connect);
    $dlen = count($disconnect);
    $tlen = $clen;
    if ($tlen < $dlen) {
        $tlen = $dlen;
    }
    $lastcon = 0;
    $lastdis = 0;
    for ($i = 0; $i < $tlen; $i++) {
        // Validate all pairs.
        if (!empty($connect[$i])) { // If connect exists.
            if ($connect[$i] < $lastdis) { // If connect smaller than last disconnect.
                $connect[$i] = $lastdis;
            }
            if (empty($disconnect[$i])) { // If disconnect pair is empty.
                // TODO handle this case
                $disconnect[$i] = $y; // Max value of session.
            }
            if ($disconnect[$i] < $connect[$i]) { // If connect larger than disconnect.
                if (!empty($connect[$i + 1])) {
                    $disconnect[$i] = $connect[$i + 1];
                }
            }
            $lastcon = $connect[$i];
        } else {
            unset($disconnect[$i - 1]); // Beoz of array sort.
        }
        if (!empty($disconnect[$i])) { // If disconnect exists.
            if ($disconnect[$i] < $lastcon) {
                $disconnect[$i] = $y;
            }

            if (empty($connect[$i])) { // If connect pair is empty.
                // TODO handle this case
                unset($disconnect[$i - 1]); // Becoz of array sort.
            }
            if ($disconnect[$i] > $y) {
                $disconnect[$i] = $y;
            }
            $lastdis = $disconnect[$i];
        } else {
            $disconnect[$i] = $y;
            $lastdis = $disconnect[$i];
        }
    }
    $connect = array_values($connect);
    $disconnect = array_values($disconnect);
    if (!empty($connect) and !empty($disconnect)) {
        $starttime = min($connect); // Student start time.
        $endtime = max($disconnect); // Student exit time.
        $totaltime = calc_student_time($connect, $disconnect); // Total time of student.
        return (object) array('totalspenttime' => $totaltime, 'starttime' => $starttime, 'endtime' => $endtime);
    }
}

/**
 * Get total student time.
 * serving for virtual class
 *
 * @param array $connect
 * @param array $disconnect
 * @return int minutes of student.
 */
function calc_student_time($connect, $disconnect) {
    $sum = 0;
    for ($i = 0; $i < count($connect); $i++) {
        if ($disconnect[$i] >= $connect[$i]) {
            $studenttime = round((abs($disconnect[$i] - $connect[$i]) / 60));
            $sum = $studenttime + $sum;
        }
    }
    return $sum;
}

/**
 * Get Setting to hex.
 * serving for virtual class
 *
 * @param object $variablesobject
 * @return string.
 */
function settingstohex($variablesobject) {
    $localsettings = array();
    $localsettings[0] = $variablesobject->allowoverride;
    $localsettings[1] = $variablesobject->studentaudio;
    $localsettings[2] = $variablesobject->studentvideo;
    $localsettings[3] = $variablesobject->studentpc;
    $localsettings[4] = $variablesobject->studentgc;
    $localsettings[5] = $variablesobject->askquestion;
    $localsettings[6] = $variablesobject->userlist;
    $localsettings[7] = $variablesobject->enablerecording;
    $localsettings[8] = $variablesobject->recallowpresentoravcontrol;
    $localsettings[9] = $variablesobject->showpresentorrecordingstatus;
    $localsettings[10] = $variablesobject->recattendeeav;
    $localsettings[11] = $variablesobject->recallowattendeeavcontrol;
    $localsettings[12] = $variablesobject->showattendeerecordingstatus;
    $localsettings[13] = $variablesobject->trimrecordings;
    $localsettings[14] = $variablesobject->attendeerecording;
    $localsettings[15] = $variablesobject->qamarknotes;
    $localsettings[16] = $variablesobject->qaanswer;
    $localsettings[17] = $variablesobject->qacomment;
    $localsettings[18] = $variablesobject->qaupvote;
    $localsettings[19] = $variablesobject->x6;
    return binarytohex(join('', $localsettings));
}

/**
 * Get Binary to hex.
 * serving for virtual class
 *
 * @param object $s
 * @return string.
 */
function binarytohex($s) {
    $ret = '';
    for ($i = strlen($s) - 1; $i >= 3; $i -= 4) {
        $part = substr($s, $i + 1 - 4, 4);
        $accum = 0;
        for ($k = 0; $k < 4; $k += 1) {
            if ($part[$k] !== '0' && $part[$k] !== '1') {
                return false;
            }
            // Compute the length 4 substring.
            $accum = $accum * 2 + intval($part[$k], 10); // Parseint.
        }
        if ($accum >= 10) {
            // A to F.
            $ret = chr($accum - 10 + ord('A'[0])) . $ret;
        } else {
            // 0 to 9.
            $ret = strval($accum) . $ret; // Todo.
        }
    }
    if ($i >= 0) {
        for ($k = 0; $k <= $i; $k += 1) {
            if ($s[$k] !== '0' && $s[$k] !== '1') {
                return false;
            }
            $accum = $accum * 2 + intval($s[$k], 10);
        }
        // Three bits, value cannot exceed 2^3 - 1 = 7, just convert.
        $ret = strval($accum) . $ret; // Todo.
    }
    return $ret;
}

/**
 * Returns array of between dates difference
 * @param int $startdate
 * @param int $weeks
 * @return array
 */
function repeat_date_list($startdate, $weeks) {
    $nextdate = array();
    $count = 1;
    while ($count < $weeks) {
        $startdate = strtotime(date('Y-m-d H:i:s', strtotime("+1 week", $startdate)));
        $nextdate[] = $startdate;
        $count++;
    }
    return $nextdate;
}

/**
 * Returns array of between dates difference
 * @param int $date1
 * @param int $date2
 * @return array
 */
function week_between_two_dates($date1, $date2) {
    $first = DateTime::createFromFormat('Y-m-d', $date1);
    $second = DateTime::createFromFormat('Y-m-d', $date2);
    return floor($first->diff($second)->days / 7);
}

/**
 * Returns array of forum attempt modes
 *
 * @return array
 */
function congrea_get_dropdown() {
    return array(
        SEVEN_DAYS => get_string('next7sessions', 'congrea'),
        THIRTY_DAYS => get_string('next30sessions', 'congrea'),
        THREE_MONTH => get_string('next90sessions', 'congrea')
    );
}

/**
 * Print dropdown form of sessions filter
 * @param int $id
 * @param int $drodowndisplaymode
 * @return array
 */
function congrea_print_dropdown_form($id, $drodowndisplaymode) {
    global $OUTPUT;
    $select = new single_select(
        new moodle_url("/mod/congrea/view.php", array('id' => $id, 'upcomingsession' => true)),
        'drodowndisplaymode',
        congrea_get_dropdown(),
        $drodowndisplaymode,
        null,
        "drodowndisplaymode"
    );
    $select->set_label(get_string('displaymode', 'congrea'), array('class' => 'accesshide'));
    $select->class = "forummode";
    echo $OUTPUT->render($select);
}

/**
 * Get upcoming session according to days.
 * serving for virtual class
 *
 * @param object $congrea
 * @param int $type
 */
function congrea_get_records($congrea, $type) {
    global $DB, $OUTPUT;
    $table = new html_table();
    $table->head = array(get_string('dateandtime', 'congrea'),
    get_string('timedur', 'congrea'),
    get_string('teacher', 'congrea'));
    $timestart = time();
    $sql = "SELECT * FROM {event} where modulename = 'congrea' and instance = $congrea->id  and timestart >= $timestart ORDER BY timestart ASC LIMIT $type"; // To do.
    $sessionlist = $DB->get_records_sql($sql);
    if ($type == 1) {
        return $sessionlist;
    }
    if (!empty($sessionlist)) {
        foreach ($sessionlist as $list) {
            $row = array();
            $row[] = userdate($list->timestart);
            if ($list->timeduration != 0) {
                $row[] = round($list->timeduration / 60) . get_string('mins', 'congrea');
            } else {
                $row[] = get_string('openended', 'congrea');
            }
            $presenter = $DB->get_record('user', array('id' => $list->userid));
            if (!empty($presenter)) {
                $username = $presenter->firstname . ' ' . $presenter->lastname; // Todo-for function.
            } else {
                $username = get_string('nouser', 'mod_congrea');
            }
            $row[] = $username;
            $table->data[] = $row;
        }
        if (!empty($table->data)) {
            echo html_writer::start_tag('div', array('class' => 'no-overflow'));
            echo html_writer::table($table);
            echo html_writer::end_tag('div');
        }
    } else {
        echo $OUTPUT->notification(get_string('noupcomingsession', 'congrea'));
    }
}

/**
 * Print tree structure.
 * serving for virtual class
 *
 * @param string $currenttab
 * @param object $context
 * @param object $cm
 * @param object $congrea
 * @return html.
 */
function congrea_print_tabs($currenttab, $context, $cm, $congrea) {
    global $OUTPUT;
    $row = array();
    $row[] = new tabobject(
        'upcomingsession',
        new moodle_url(
            '/mod/congrea/view.php',
            array('id' => $cm->id, 'upcomingsession' => $congrea->id)
        ),
        get_string('upcomingsession', 'mod_congrea')
    );
    $row[] = new tabobject(
        'psession',
        new moodle_url(
            '/mod/congrea/view.php',
            array('id' => $cm->id, 'psession' => $congrea->id)
        ),
        get_string('psession', 'mod_congrea')
    );
    if (has_capability('mod/congrea:managesession', $context)) {
        $row[] = new tabobject(
            'sessionsettings',
            new moodle_url(
                '/mod/congrea/sessionsettings.php',
                array('id' => $cm->id, 'sessionsettings' => $congrea->id)
            ),
            get_string('sessionsettings', 'mod_congrea')
        );
    }
    echo $OUTPUT->tabtree($row, $currenttab);
}

/**
 * Get first key from array.
 * serving for virtual class
 * @param array $arr
 * @return int.
 */
function congrea_array_key_first(array $arr) {
    foreach ($arr as $key => $unused) {
        return $key;
    }
    return null;
}

/**
 * Get Recording view status.
 * serving for virtual class
 *
 * @param array $data
 * @param object $mapasobject
 * @return array.
 */
function unmarshalitem(array $data, $mapasobject = false) {
    return unmarshalvalue(['M' => $data], $mapasobject);
}

/**
 * Api for format array of recording.
 * serving for virtual class
 *
 * @param array $value
 * @param object $mapasobject
 */
function unmarshalvalue(array $value, $mapasobject = false) {
    $type = key($value);
    $value = $value[$type];
    switch ($type) {
        case 'S':
        case 'BOOL':
            return $value;
        case 'NULL':
            return null;
        case 'N':
            // Use type coercion to unmarshal numbers to int/float.
            return $value + 0;
        case 'M':
            if ($mapasobject) {
                $data = new \stdClass;
                foreach ($value as $k => $v) {
                    $data->$k = unmarshalvalue($v, $mapasobject);
                }
                return $data;
            }
        case 'L':
            foreach ($value as $k => $v) {
                $value[$k] = unmarshalvalue($v, $mapasobject);
            }
            return $value;
        case 'B':
            return new BinaryValue($value);
        case 'SS':
        case 'NS':
        case 'BS':
            foreach ($value as $k => $v) {
                $value[$k] = unmarshalvalue([$type[0] => $v]);
            }
            return new SetValue($value);
    }
    throw new \UnexpectedValueException("Unexpected type: {$type}.");
}

/**
 * Get Recording view status.
 * serving for virtual class
 *
 * @param int $uid
 * @param object $recordingattendance
 * @return int.
 */
function recording_view($uid, $recordingattendance) {
    $sum = 0;
    $datapercent = 0;
    $recodingtime = null;
    foreach ($recordingattendance['Items'] as $i) {
        $rdata = unmarshalitem($i);
        $userid = (int) filter_var($rdata['sk'], FILTER_SANITIZE_NUMBER_INT);
        if ($uid == $userid) {
            if (is_array($rdata['data'])) {
                if (empty($recodingtime)) {
                    $recodingtime = $rdata['data']['rtt'];
                }
                foreach ($rdata['data'] as $data) {
                    if (is_array($data)) {
                        foreach ($data as $key) {
                            $k = array_keys($key);
                            $v = array_values($key);
                            $arrkey = $k[0];
                            $arrvalue = $v[0];
                            $viewdata = $arrvalue - $arrkey;
                            $sum = $viewdata + $sum;
                        }
                    }
                }
                $datapercent = round((($sum * 5) / ($recodingtime / 1000)) * 100);
            }
            return (object) array('totalviewd' => round($sum * 5),
            'totalviewedpercent' => $datapercent, 'recodingtime' => ($recodingtime / 1000));
        }
    }
}

/**
 * Returns array of between dates difference
 * @param int $startdate
 * @param int $expecteddate
 * @param str $days
 * @param int $duration
 * @return array
 */
function repeat_date_list_check($startdate, $expecteddate, $days, $duration) {
    if (!empty($days)) {
        $listdays = str_replace('"', '', $days);
        $dayslist = explode(", ", $listdays);
        while (strtotime($startdate) < strtotime($expecteddate)) {
            $startdate = date('Y-m-d H:i:s', strtotime("+1 days", strtotime($startdate)));
            $nameofday = date('D', strtotime($startdate));
            if (in_array($nameofday, $dayslist)) {
                $starttime = date("Y-m-d H:i:s", strtotime($startdate));
                $enddate = date('Y-m-d H:i:s', strtotime("+$duration minutes", strtotime($starttime))); // DB Enddate.
                $nextdate[] = (object) array('startdate' => $startdate, 'enddate' => $enddate);
            }
        }
        return $nextdate;
    }
}
/** Function to sort the scheduled list of sessions
 * @param array $sessionlist
 * @param int $session
 * @return array
 */
function compare_dates_scheduled_list($sessionlist, $session) {
    if ($sessionlist->timestart == $session->timestart) {
        return 0;
    }
    return ($sessionlist->timestart < $session->timestart) ? -1 : 1;
}
/**
 * This function authenticate the user with required
 * detail and request for sever connection
 *
 * @param string $url congrea auth server url
 * @param array $postdata
 * @param string $key
 * @param string $secret
 *
 * @return string $result json_encoded object
 */
function congrea_curl_request($url, $postdata, $key, $secret) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'x-api-key: ' . $key,
        'x-congrea-secret: ' . $secret,
    ));
    curl_setopt($ch, CURLOPT_TRANSFERTEXT, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXY, false);
    $result = @curl_exec($ch);
    curl_close($ch);
    return $result;
}
/** Function to send auth detail to server.
 * @param int $cgapi
 * @param int $cgsecret
 * @param boolean $recordingstatus
 * @param int $course
 * @param int $cm
 * @param string $role
 *
 * @return object $authdata
 */
function get_auth_data($cgapi, $cgsecret, $recordingstatus, $course, $cm, $role='s') {
    $authusername = substr(str_shuffle(md5(microtime())), 0, 20);
    $authpassword = substr(str_shuffle(md5(microtime())), 0, 20);
    $licensekey = $cgapi;
    $secret = $cgsecret;
    $recording = $recordingstatus;
    $room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
    $authdata = array('authuser' => $authusername, 'authpass' => $authpassword, 'role' => $role,
                'room' => $room, 'recording' => $recording);
    $postdata = json_encode($authdata);
    $rid = congrea_curl_request("https://api.congrea.net/backend/auth", $postdata, $licensekey, $secret);
    if (!$rid = json_decode($rid)) {
        echo "{\"error\": \"403\"}";
        exit;
    } else if (isset($rid->message)) {
        echo "{\"error\": \"$rid->message\"}";
        exit;
    } else if (!isset($rid->result)) {
        echo "{\"error\": \"invalid\"}";
        exit;
    }
    $rid = "wss://$rid->result";
    $authdata = (object) array_merge( (array)$authdata, array( 'path' => $rid));
    return $authdata;
}