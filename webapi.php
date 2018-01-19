<?php

function set_session() {
    if (isset($_GET['key'])) {
        session_id($_GET['key']);
    }
}

set_session();
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include('weblib.php');

// The function list is avaible in weblib.php
define('Functions_list', serialize(array('record_file_save', 'poll_save', 'poll_data_retrieve',
'poll_delete', 'poll_update', 'poll_result', 'poll_option_drop','congrea_get_enrolled_users','congrea_quiz','congrea_get_quizdata', 'congrea_add_quiz', 'congrea_quiz_result')));

function set_header() {
    header("access-control-allow-origin: *");
}

/* Exit when there is request is happend by options method  
 * This generally happens when the request is coming from other domain
 * eg:- if the request is coming from l.vidya.io and main domain suman.moodlehub.com
 * it also know as preflight request
 * * */

function exit_if_request_is_options() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit;
    }
}

function received_get_data() {
    return (isset($_GET)) ? $_GET : false;
}

function received_post_data() {
    return (isset($_POST)) ? $_POST : false;
}

function validate_request() {
    $cmid = required_param('cmid', PARAM_INT);
    $userid = required_param('user', PARAM_INT);
    $getdata = received_get_data();
    $fun_name = $getdata['methodname'];
    $postdata = received_post_data();
    $qstring = array($postdata);
    switch ($fun_name) {
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

/* The function is executed which is passed by get */

function execute_action($valid_parameters) {
    $getdata = received_get_data();
    if ($getdata && isset($getdata['methodname'])) {
        $postdata = received_post_data();
        if ($postdata) {
            $function_list = unserialize(Functions_list);
            if (in_array($getdata['methodname'], $function_list)) {
                $getdata['methodname']($valid_parameters);
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

$validparams = validate_request();

execute_action($validparams);
?>
