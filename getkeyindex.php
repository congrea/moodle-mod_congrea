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
$e = optional_param('e', null, PARAM_NOTAGS);

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('getkey');

$PAGE->set_url(new moodle_url('/admin/settings.php?section=getKey'));

$mform = new mod_congrea_key_form(null, array('email' => $USER->email, 'firstname' => $USER->firstname ,
    'lastname' => $USER->lastname , 'domain' => $CFG->wwwroot));
// There should be form submit
// Form submitted throug js and result received in url.
if ($mform->is_cancelled()) {
    // Do nothing.
} else if ($fromform = $mform->get_data()) {
    // Redirect($nexturl).
}
echo $OUTPUT->header('Get Congrea Free Plan');

if ($result = get_config('mod_congrea', 'keyvalue')) {
    echo html_writer::start_tag('div', array('class' => 'box generalbox alert'));
    echo get_string('keyis', 'mod_congrea').$result."\t";
    $url = new moodle_url('/mod/congrea/savekey.php', array('action' => 'confirmdelete', 'sesskey' => sesskey()));
    echo  html_writer::link($url,
    '<img src = "'.$OUTPUT->image_url('t/delete').'" class = "iconsmall" alt="'.
    get_string('delete').'" title = "'.get_string('delete').'" />');
    echo html_writer::end_tag('div');

    // Stat of vidya.io api start.
    $PAGE->requires->js('/mod/congrea/stat/d3.v3.min.js');
    $PAGE->requires->js('/mod/congrea/stat/underscore-min.js');
    $PAGE->requires->js('/mod/congrea/stat/function.js');
    $PAGE->requires->js('/mod/congrea/stat/jsonp.js');

    $module = array(
        'name' => 'congrea_stat',
        'fullpath' => '/mod/congrea/stat/stat.js',
        'requires' => array('node', 'event'),
        'strings' => array(),
    );
    $PAGE->requires->strings_for_js(array('msggraph', 'usrgraph', 'nodata'), 'mod_congrea');
    $PAGE->requires->js_init_call('congrea_stat_init', array($result), false, $module);

    echo html_writer::tag('h3', get_string('graphheading', 'mod_congrea'));
    echo html_writer::start_tag('div', array('id' => 'graph', 'class' => 'aGraph'));
    echo html_writer::start_tag('div', array('id' => 'option'));
    echo html_writer::empty_tag('input',
    array('type' => 'button', 'name' => 'dstat', 'value' => get_string('today', 'mod_congrea'),
    'id' => 'id_day_stat'));
    echo html_writer::empty_tag('input',
    array('type' => 'button', 'name' => 'currmstat', 'value' => get_string('currentmonth', 'mod_congrea'),
    'id' => 'id_currmonth_stat'));
    echo html_writer::empty_tag('input',
    array('type' => 'button', 'name' => 'premstat',
    'value' => get_string('previousmonth', 'mod_congrea'),
    'id' => 'id_premonth_stat'));
    echo html_writer::empty_tag('input',
    array('type' => 'button', 'name' => 'ystat', 'value' => get_string('year', 'mod_congrea'),
    'id' => 'id_year_stat'));
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', array('id' => 'msggraph', 'class' => 'aGraph'));
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', array('id' => 'usergraph', 'class' => 'aGraph'));
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo  html_writer::link($url, '<a href = "'. $CFG->wwwroot .
    '/admin/settings.php?section=modsettingcongrea' .'"> Return to congrea settings page </a>');
    // Stat of Congrea.com api end.

} else if ($k) { // Key received from Congrea.com.
    
    if (!set_config('keyvalue', $k, 'mod_congrea')) {
        echo $OUTPUT->error_text(get_string('keynotsaved', 'mod_congrea'));
    }
    echo $OUTPUT->heading(get_string('keyis', 'mod_congrea').$k, 3, 'box generalbox', 'jpoutput');
    echo  html_writer::link($url, '<a href = "'. $CFG->wwwroot .
    '/mod/congrea/getkeyindex.php' .'"> See your package details.</a>');
} else {
    if ($e) {

        echo html_writer::tag('div', $e, array('class' => 'alert alert-error'));
    }

    echo html_writer::tag('div', get_string('havekey', 'mod_congrea'), array('class' => 'alert alert-notice'));
    // Loading three other YUI modules.
    $jsmodule = array(
                'name' => 'mod_congrea',
                'fullpath' => '/mod/congrea/module.js',
                'requires' => array('json', 'jsonp', 'jsonp-url', 'io-base', 'node', 'io-form'));
    $PAGE->requires->js_init_call('M.mod_congrea.init', null, false, $jsmodule);
    $PAGE->requires->string_for_js('keyis', 'mod_congrea');

    echo $OUTPUT->box(get_string('message', 'mod_congrea'), "generalbox center clearfix");
    $mform->display();
}

// Create vm token.
if (!$re = get_config('mod_congrea', 'tokencode')) {
    $tokencode = substr(  time(), -4).substr( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" , mt_rand( 0 , 20 ) , 3 ) . substr(  time(), 0, 3);// Random string.
    set_config('tokencode', $tokencode, 'mod_congrea');
}
echo $OUTPUT->footer();
