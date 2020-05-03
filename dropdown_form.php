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
 * This file contains the forms for duration
 *
 * @package   mod_congrea
 * @copyright 2020 vidyamantra.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * class for displaying duration form.
 *
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_congrea_dropdown_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $options = array(1 => get_string('7sessions', 'congrea'), 2 => get_string('30sessions', 'congrea'),
        3 => get_string('90sessionss', 'congrea'), 4 => get_string('180sessions', 'congrea'));
        $mform->addElement('select', 'dropdownid', get_string('filter', 'congrea'), $options);
        $mform->addHelpButton('dropdownid', 'filter', 'congrea');
        $this->add_action_buttons();
    }
}
