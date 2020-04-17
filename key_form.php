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
 * Form for getting the new key
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Get closest timezone
 *
 * @param int $search
 * @param array $arr
 * @return int
 */
function get_closest($search, $arr) {
    $closest = null;
    foreach ($arr as $item) {
        if ($closest === null || abs($search - $closest) > abs($item - $search)) {
            $closest = $item;
        }
    }
    return $closest;
}


/**
 * Get Suitable Data Center
 * @return string
 */
function get_suitable_dc() {
    $timeoffset = (-(usertime(0) / 60));
    $timezones = [-420, -240, -240, 60, 330, 60, 480, 0];
    $closest = get_closest($timeoffset, $timezones);
    switch($closest) {
        case -420:
            return 'sf';
        case -240:
            return array_rand(array('ny', 'ca', ));
        case 60:
            return array_rand(array('de', 'nl', ));
        case 330:
            return 'in';
        case 480:
            return 'sg';
        default:
            return 'uk';
    }
}
/**
 * Get Congrea keys form.
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class mod_congrea_key_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        $mform =& $this->_form;
        $mform->addElement('text', 'firstname', get_string('firstname', 'congrea'), 'size="35"');
        $mform->addRule('firstname', null, 'required', null, 'client');
        $mform->addHelpButton('firstname', 'firstname', 'congrea');
        $mform->setType('firstname', PARAM_TEXT);
        $mform->setDefault('firstname', $this->_customdata['firstname']);
        $mform->addElement('text', 'lastname', get_string('lastname', 'congrea'), 'size="35"');
        $mform->addRule('lastname', null, 'required', null, 'client');
        $mform->addHelpButton('lastname', 'lastname', 'congrea');
        $mform->setType('lastname', PARAM_TEXT);
        $mform->setDefault('lastname', $this->_customdata['lastname']);
        $mform->addElement('text', 'email', get_string('email', 'congrea'), 'maxlength="100" size="35" ');
        $mform->addHelpButton('email', 'email', 'congrea');
        $mform->setType('email', PARAM_NOTAGS);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
        // Set default value by using a passed parameter.
        $mform->setDefault('email', $this->_customdata['email']);
        $mform->addElement('text', 'domain', get_string('domain', 'congrea'), 'maxlength="100" size="35" ');
        $mform->setType('domain', PARAM_NOTAGS);
        $mform->addRule('domain', get_string('missingdomain', 'congrea'), 'required', null, 'server');
        $mform->addHelpButton('domain', 'domain', 'congrea');
        // Set default value by using a passed parameter.
        $mform->setDefault('domain', $this->_customdata['domain']);

        $dcoptions = array(
            'sf' => 'San Francisco, CA, USA',
            'ny' => 'New York, NY, USA',
            'ca' => 'Toronto, CA',
            'de' => 'Frankfurt, DE',
            'in' => 'Bangalore, IN',
            'nl' => 'Amsterdam, NL',
            'sg' => 'Singapore, SG',
            'uk' => 'London, England, UK'
        );
        $mform->addElement('select', 'datacenter', get_string('datacenter', 'congrea'), $dcoptions);
        $mform->setType('datacenter', PARAM_ALPHANUM);
        $mform->addRule('datacenter', get_string('missingdatacenter', 'congrea'), 'required', null, 'server');
        $mform->addHelpButton('datacenter', 'datacenter', 'congrea');
        // Set default value by using a passed parameter.
        $mform->setDefault('datacenter', get_suitable_dc());

        $mform->addElement('checkbox', 'terms', '', get_string('terms', 'congrea'), 1, null);
        $mform->addHelpButton('terms', 'terms', 'congrea');
        $mform->addRule('terms', get_string('missingterms', 'congrea'), 'required', null, 'server');

        $mform->addElement('checkbox', 'privacy', '', get_string('privacy', 'congrea'), ' ', null);
        $mform->addRule('privacy', get_string('missingprivacy', 'congrea'), 'required', null, 'server');
        $mform->addHelpButton('privacy', 'privacy', 'congrea');

        $this->add_action_buttons($cancel = false);
    }
}