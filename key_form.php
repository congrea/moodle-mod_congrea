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

require_once($CFG->dirroot.'/lib/formslib.php');

class mod_congrea_key_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        //$mform->addElement('static', 'description', get_string('description', 'exercise'));
        $mform->addElement('text', 'firstname', get_string('firstname', 'moodle'), 'size="35"');
        $mform->addRule('firstname', null, 'required', null, 'client');
        $mform->addHelpButton('firstname', 'firstname', 'congrea');
        $mform->setType('firstname', PARAM_TEXT);
        $mform->setDefault('firstname', $this->_customdata['firstname']);
        $mform->addElement('text', 'lastname', get_string('lastname', 'moodle'), 'size="35"');
        $mform->addRule('lastname', null, 'required', null, 'client');
        $mform->addHelpButton('lastname', 'lastname', 'congrea');
        $mform->setType('lastname', PARAM_TEXT);
        $mform->setDefault('lastname', $this->_customdata['lastname']);
        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="35" ');
        $mform->addHelpButton('email', 'email', 'congrea');
        $mform->setType('email', PARAM_NOTAGS);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
        // Set default value by using a passed parameter.
        $mform->setDefault('email', $this->_customdata['email']);
        $mform->addElement('text', 'domain', get_string('domain', 'mod_congrea'), 'maxlength="100" size="35" ');
        $mform->setType('domain', PARAM_NOTAGS);
        $mform->addRule('domain', get_string('missingdomain', 'mod_congrea'), 'required', null, 'server');
        $mform->addHelpButton('domain', 'domain', 'congrea');
        // Set default value by using a passed parameter.
        $mform->setDefault('domain', $this->_customdata['domain']);
        $mform->addElement('hidden', 'zerovalue', '0');
        $mform->setType('zerovalue', PARAM_ALPHANUM);

        $dcOptions = array(
            '0' =>  'Choose a data center',
            'sf' => 'San Francisco, CA, USA',
            'ny' => 'New York, NY, USA',
            'ca' => 'Toronto, CA',
            'de' => 'Frankfurt, DE',
            'in' => 'Bangalore, IN',
            'nl' => 'Amsterdam, NL',
            'sg' => 'Singapore, SG',
            'uk' => 'London, England, UK'
        );
        $mform->addElement('select', 'datacenter', get_string('datacenter', 'congrea'), $dcOptions);
        $mform->setType('datacenter', PARAM_ALPHANUM);
       // $mform->addRule(['datacenter', 'zerovalue'], 'Choose a data center', 'compare', 'neq', 'server');
        $mform->addRule('datacenter', get_string('missingdatacenter', 'mod_congrea'), 'required', null, 'server');
        $mform->addHelpButton('datacenter', 'datacenter', 'congrea');
        // Set default value by using a passed parameter.
        $mform->setDefault('datacenter', '0');

        $mform->addElement('advcheckbox', 'terms', '', get_string('terms', 'congrea'), ' ', null);
        $mform->addHelpButton('terms', 'terms', 'congrea');
        $mform->addRule('terms', get_string('missingterms'), 'required', null, 'server');
        if (get_config('mod_congrea', 'terms')) {
            $mform->setDefault('terms', 1);
        } else {
            $mform->setDefault('terms', 0);
        }
        $mform->addElement('advcheckbox', 'privacy', '', get_string('privacy', 'congrea'), ' ', null);
        $mform->addRule('privacy', get_string('missingprivacy'), 'required', null, 'server');
        $mform->addHelpButton('privacy', 'privacy', 'congrea');
        if (get_config('mod_congrea', 'privacy')) {
            $mform->setDefault('privacy', 1);
        } else {
            $mform->setDefault('privacy', 0);
        }
        $this->add_action_buttons($cancel = false);
    }
}
class mod_congrea_savekey_form extends moodleform { // Not required.
    function definition() {
        $kform =& $this->_form;
        $kform->addElement('text', 'key', get_string('apikey', 'mod_congrea'), 'maxlength="100" size="25" ');
        $kform->setType('key', PARAM_NOTAGS);
        $kform->addRule('key', get_string('missingkey', 'mod_congrea'), 'required', null, 'server');
        // Set default value by using a passed parameter.
        $this->add_action_buttons($cancel = true);
    }
}