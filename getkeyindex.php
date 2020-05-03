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
$PAGE->set_heading(get_string('getfreeplan', 'congrea'));

$configkey = get_config('mod_congrea', 'cgapi');
$configsecret = get_config('mod_congrea', 'cgsecretpassword');

if ($configkey && $configsecret) {
    redirect(new moodle_url('/admin/settings.php?section=modsettingcongrea'),
        get_string('afterkeyredirectmsg', 'congrea'), null, \core\output\notification::NOTIFY_SUCCESS);
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
    curl_close($curl);
    $errortext = '';
    if (!$response) {
        $errortext = "Curl error " . curl_errno($curl ) . PHP_EOL;
    } else {
        $response = jsonp_decode($response);
        if ($response->error != '') {
            $errortext = $response->error;
        }
    }
    if ($response->key && $response->secret) {
        if (!set_config('cgapi', $response->key, 'mod_congrea') ||
        !set_config('cgsecretpassword', $response->secret, 'mod_congrea')) {
            $errortext = get_string('cannotsavekey', 'mod_congrea');
        }
    }
    if ($errortext == '') {
            redirect(new moodle_url('/admin/settings.php?section=modsettingcongrea'),
                get_string('afterkeysavemsg', 'congrea'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->header();
        \core\notification::add($errortext, \core\output\notification::NOTIFY_ERROR);
        $mform->display();
    }

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('message', 'congrea'), "generalbox center clearfix");
    $mform->display();
}

echo $OUTPUT->footer();

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