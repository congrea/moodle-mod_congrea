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
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get list of teacher of current course
 * serving for virtual class
 *
 * @return object
 */
function congrea_course_teacher_list() {
    global $COURSE;

    $courseid = $COURSE->id;

    $context = context_course::instance($courseid);
    $heads = get_users_by_capability($context, 'moodle/course:update');

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
 * @param string $recordingstatus
 * @param boolean $joinbutton
 * @return string
 */
function congrea_online_server($url, $authusername, $authpassword, $role, $rid, $room,
            $upload, $down, $debug = false,
            $cgcolor, $webapi, $userpicturesrc, $fromcms, $licensekey, $audiostatus, $videostatus,
            $recordingstatus = false, $joinbutton = false) {
    global $USER;
    $username = $USER->firstname.' '.$USER->lastname;
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
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recording', 'value' => $recordingstatus));
    if (!$joinbutton) {
        $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton',
                    'value' => get_string('joinroom', 'congrea')));
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
 * @param string $enablerecording
 * @return string
 */
function congrea_online_server_play($url, $authusername, $authpassword, $role, $rid, $room,
            $upload, $down, $debug = false,
            $cgcolor, $webapi, $userpicturesrc, $licensekey, $id, $vcsid, $recordingsession = false, $enablerecording = false) {
    global $USER;
    $username = $USER->firstname.' '.$USER->lastname;
    $form = html_writer::start_tag('form', array('id' => 'playRec'.$vcsid, 'class' => 'playAct',
                                                         'action' => $url, 'method' => 'post'));
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
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recording', 'value' => $enablerecording));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'play', 'value' => 1));
    $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton playbtn',
                    'value' => '', 'title' => 'Play'));
    $form .= html_writer::end_tag('form');
    return $form;
}

/**
 * Update the calendar entries for this congrea.
 *
 * @param object $congrea
 * @return bool
 */
function mod_congrea_update_calendar($congrea) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    if ($congrea->closetime && $congrea->closetime > time()) {
        $event = new stdClass();
        $params = array('modulename' => 'congrea', 'instance' => $congrea->id);
        $event->id = $DB->get_field('event', 'id', $params);
        $event->name = $congrea->name;
        $event->timestart = $congrea->opentime;
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
        if ($event->id) {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            unset($event->id);
            $event->courseid = $congrea->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'congrea';
            $event->instance = $congrea->id;
            $event->eventtype = 'open';
            $event->timeduration = 0;
            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'congrea', 'instance' => $congrea->id));
    }
}

/**
 * Delete recoded files with folder.
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
                $OUTPUT->pix_icon('t/editstring', '', 'moodle', array('class' => 'iconsmall visibleifjs', 'title' => '')),
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
            'x-api-key:' . $key,
            'x-congrea-secret:' . $secret,
        ));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
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
            print_error('Course Module ID was incorrect');
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
        return $userid;
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
    if (!empty($connecttime) and ! empty($disconnecttime)) {
        $sessionstarttime = min($connecttime);
        $sessionendtime = max($disconnecttime);
        $totaltime = round(($sessionendtime - $sessionstarttime) / 60); // Total session time in minutes.
        return (object) array('totalsessiontime' => $totaltime,
                'sessionstarttime' => $sessionstarttime, 'sessionendtime' => $sessionendtime); // TODO.
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

    if (!empty($connect) and ! empty($disconnect)) {
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
    if (!empty($connect) and ! empty($disconnect)) {
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