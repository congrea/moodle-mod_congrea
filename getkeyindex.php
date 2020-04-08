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
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('key_form.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/mod/congrea/getkeyindex.php'));

$PAGE->set_pagelayout('standard');
$PAGE->set_heading('Get Congrea free plan');

echo $OUTPUT->header();

$configkey = get_config('mod_congrea', 'cgapi');
$configsecret = get_config('mod_congrea', 'cgsecretpassword');

if ($configkey && $configsecret) {
    redirect(new moodle_url('/admin/settings.php?section=modsettingcongrea'));
}

$mform = new mod_congrea_key_form(null, array('email' => $USER->email, 'firstname' => $USER->firstname ,
'lastname' => $USER->lastname , 'domain' => $CFG->wwwroot));

if ($fromform = $mform->get_data()) {
    $postdata = array(
        'firstname' => $fromform->firstname,
        'lastname' => $fromform->lastname,
        'email' => $fromform->email,
        'domain' => $fromform->domain,
        'datacenter' => $fromform->datacenter
    );
    $request = json_encode($postdata);
    $serverurl = 'https://www.vidyamantra.com/portal/getvmkey.php?data=' . $request;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $serverurl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0 );
    $response = curl_exec($curl);
    if (!$response) { // TODO: check for error here - curl may return error
        print "curl error " . curl_errno($curl ) . PHP_EOL;
    } else {
        $response = jsonp_decode($response);
        curl_close($curl);
    }
    if (($key = $response->key) && ($secret = $response->secret)) {
        if (!set_config('cgapi', $key, 'mod_congrea')) {
            $OUTPUT->error_text(get_string('keynotsaved', 'mod_congrea'));
        }
        if (!set_config('cgsecretpassword', $secret, 'mod_congrea')) {
            $OUTPUT->error_text(get_string('keynotsaved', 'mod_congrea'));
        }
        // redirect(new moodle_url('/admin/settings.php?section=modsettingcongrea'));
        displaykeys($key, $secret, 'configuredheading');
    } else if ($error = $response->error) {
        echo html_writer::tag('h4', get_string('submiterror', 'congrea') . $error);
        echo $OUTPUT->box(get_string('message', 'congrea'), "generalbox center clearfix");
        $mform->display();
    }
} else {
    echo $OUTPUT->box(get_string('message', 'congrea'), "generalbox center clearfix");
    $mform->display();
}
echo $OUTPUT->footer();

/**
 * Display keys
 *
 * @param string $firstkey
 * @param string $secondkey
 * @param string $languagestr
 * @return string
 */
function displaykeys($firstkey, $secondkey, $languagestr) {
    echo html_writer::tag('h4', get_string($languagestr, 'congrea'));
    echo html_writer::tag('p', get_string('keyis', 'congrea') . $firstkey);
    echo html_writer::tag('p', get_string('secretis', 'congrea') . $secondkey);
}

/**
 * Json decode
 *
 * @param string $jsonp
 * @param array $assoc
 * @return string
 */
function jsonp_decode($jsonp, $assoc = false) {
    if ($jsonp[0] !== '[' && $jsonp[0] !== '{') {
        $jsonp = substr($jsonp, strpos($jsonp, '('));
    }
    return json_decode(trim($jsonp, '();'), $assoc);
}