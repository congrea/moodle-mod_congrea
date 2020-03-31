<?php
// This file is part of Moodle - http://vidyamantra.com/
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
 * Authentication key
 *
 * @package    mod_congrea
 * @copyright  2020 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('key_form.php');

$k = optional_param('k', 0, PARAM_NOTAGS);
$s = optional_param('s', 0, PARAM_NOTAGS);
$e = optional_param('e', null, PARAM_NOTAGS);

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('getkey');

$PAGE->set_url(new moodle_url('/mod/congrea/getkeyindex.php'));
$mform = new mod_congrea_key_form(null, array('email' => $USER->email, 'firstname' => $USER->firstname ,
    'lastname' => $USER->lastname , 'domain' => $CFG->wwwroot));
// There should be form submit
// Form submitted through js and result received in url.
if ($mform->is_cancelled()) {
    // Do nothing.
} else if ($fromform = $mform->get_data()) {
}
echo $OUTPUT->header();
if ($result = get_config('mod_congrea', 'cgapi')) {
    $k = get_config('mod_congrea', 'cgapi');
    $s = get_config('mod_congrea', 'cgsecretpassword');
    echo html_writer::start_tag('div', array('class' => 'box generalbox alert'));
    echo get_string('keyis', 'congrea') . $k . "<br>";
    echo 'Secret key: ' . $s;
    echo html_writer::end_tag('div');
} else if ($k) { // Key received from Congrea.com.
    if (!set_config('cgapi', $k, 'mod_congrea')) {
        echo $OUTPUT->error_text(get_string('keynotsaved', 'congrea'));
    }
    if (!set_config('cgsecretpassword', $s, 'mod_congrea')) {
        echo $OUTPUT->error_text(get_string('keynotsaved', 'congrea'));
    }
    echo $OUTPUT->heading(get_string('keyis', 'congrea').$k, 6, 'box generalbox', 'jpoutput');
    echo $OUTPUT->heading('Secret key: ' . $s, 6, 'box generalbox', 'jpoutput');
    echo html_writer::tag('p', get_string('configuredheading', 'mod_congrea'));
} else {
    if ($e) {
        echo html_writer::tag('div', $e, array('class' => 'alert alert-error'));
    }
    // Loading three other YUI modules.
    $jsmodule = array(
                'name' => 'mod_congrea',
                'fullpath' => '/mod/congrea/module.js',
                'requires' => array('json', 'jsonp', 'jsonp-url', 'io-base', 'node', 'io-form'));
    $PAGE->requires->js_init_call('M.mod_congrea.init', null, false, $jsmodule);

    $PAGE->requires->string_for_js('keyis', 'congrea');
    $PAGE->requires->string_for_js('secretkeyis', 'congrea');
    echo $OUTPUT->box(get_string('message', 'congrea'), "generalbox center clearfix");
    $mform->display();
}

// Create vm token.
if (!$re = get_config('mod_congrea', 'tokencode')) {
    $tokencode = substr(  time(), -4).substr( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" , mt_rand( 0 , 20 ) , 3 ) . substr(  time(), 0, 3);// Random string.
    set_config('tokencode', $tokencode, 'mod_congrea');
}
echo $OUTPUT->footer();
