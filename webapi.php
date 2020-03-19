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
 * Congrea module internal API.
 *
 *
 * @package   mod_congrea
 * @copyright 2017 Suman Bogati
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require('weblib.php');

// The function list is avaible in weblib.php.
define('FUNCTIONS_LIST', serialize(array('record_file_save', 'poll_save', 'poll_data_retrieve',
      'poll_delete', 'poll_update', 'poll_result', 'poll_option_drop', 'congrea_get_enrolled_users',
      'congrea_quiz', 'congrea_get_quizdata', 'congrea_add_quiz', 'congrea_quiz_result')));

/**
 * function for set header
 * serving for virtual class
 */
function set_header() {
    if (isset($_SERVER["HTTP_ORIGIN"])) {
        header("access-control-allow-origin:" . $_SERVER["HTTP_ORIGIN"]);
        header("Access-Control-Allow-Credentials: true");
    } else {
        header("access-control-allow-origin: https://live.congrea.net");
        header("Access-Control-Allow-Credentials: true");
    }
}

/** Exit when there is request is happend by options method
 * This generally happens when the request is coming from other domain
 * eg:- if the request is coming from l.vidya.io and main domain suman.moodlehub.com
 * it also know as preflight request
 * serving for virtual class
 *
 */
function exit_if_request_is_options() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit;
    }
}

/**
 * Function to get data by get method
 * serving for virtual class
 */
function received_get_data() {
    return (isset($_GET)) ? $_GET : false;
}

/**
 * Function to get data by post method
 * serving for virtual class
 */
function received_post_data() {
    return (isset($_POST)) ? $_POST : false;
}

/**
 * Function to validate request
 * which is invoke by ajax
 * serving for virtual class
 *
 * @return array
 */
function validate_request() {
    $cmid = required_param('cmid', PARAM_INT);
    $userid = required_param('user', PARAM_INT);
    $getdata = received_get_data();
    $funname = $getdata['methodname'];
    $postdata = received_post_data();
    $qstring = array($postdata);
    switch ($funname) {
        case 'record_file_save' :
            unset($qstring); // Post data is not needed.
            $qstring = array($cmid, $userid);
            $filenum = required_param('cn', PARAM_INT);
            $qstring[] = $filenum;
            $vmsession = required_param('sesseionkey', PARAM_FILE);
            $qstring[] = $vmsession;
            $data = required_param('record_data', PARAM_RAW);
            $qstring[] = $data;
            break;
        case 'poll_save':
            $qstring[] = $cmid;
            $qstring[] = $userid;
            break;
        case 'congrea_get_enrolled_users':
            unset($qstring); // Post data is not needed.
            $qstring[] = $cmid;
            break;
    }

    return $qstring;
}


/**
 * The function is executed which is passed by get
 * serving for virtual class
 *
 * @param array $validparameters
 */
function execute_action($validparameters) {
    $getdata = received_get_data();
    if ($getdata && isset($getdata['methodname'])) {
        $postdata = received_post_data();
        if ($postdata) {
            $functionlist = unserialize(FUNCTIONS_LIST);
            if (in_array($getdata['methodname'], $functionlist)) {
                $getdata['methodname']($validparameters);
            } else {
                throw new Exception('There is no ' . $getdata['methodname'] . ' method to execute.');
            }
        }
    } else {
        throw new Exception('There is no method to execute.');
    }
}

set_header();

exit_if_request_is_options();
// Commented : require_login(); // Hack to mitigate SameSite - https://www.chromium.org/updates/same-site/.
$validparams = validate_request();

execute_action($validparams);
