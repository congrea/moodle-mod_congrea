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
 * Form to edit uploaded file name
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/congrea/locallib.php');

/**
 * File update name form
 *
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class mod_congrea_session_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $sessionsettings = $this->_customdata['sessionsettings'];
        $edit = $this->_customdata['edit'];
        $action = $this->_customdata['action'];
        $mform->addElement('hidden', 'sessionsettings', $sessionsettings);
        $mform->setType('sessionsettings', PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'edit', $edit);
        $mform->setType('edit', PARAM_INT);
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_CLEANHTML);
        if (!$edit) {
            $mform->addElement('header', 'sessionsheader', get_string('sessionsettings', 'mod_congrea'));
        }

        $mform->addElement('date_time_selector', 'fromsessiondate', get_string('fromsessiondate', 'congrea'));
        $mform->addHelpButton('fromsessiondate', 'fromsessiondate', 'congrea');
        $mform->setType('timeduration', PARAM_INT);
        $durationfield = array();
        $durationfield[] =& $mform->createElement('text', 'timeduration', '', array('size' => 4));
        $durationfield[] =& $mform->createElement('static', 'repeattext', '', get_string('mins', 'congrea'));
        $mform->addGroup($durationfield, 'timeduration', get_string('timeduration', 'congrea'), array(' '), false);
        $mform->addHelpButton('timeduration', 'timeduration', 'congrea');
        $mform->addRule('timeduration', null, 'required', null, 'client');
        $mform->addRule('timeduration', null, 'numeric', null, 'client');
        // Select teacher.
        $teacheroptions = congrea_course_teacher_list($id);
        $mform->addElement('select', 'moderatorid', get_string('selectteacher', 'congrea'), $teacheroptions);
        $mform->addHelpButton('moderatorid', 'selectteacher', 'congrea');
        // Repeat.
        $mform->addElement('advcheckbox', 'addmultiple', '',
        get_string('addmultiplesessions', 'congrea'),
        array('group' => 1), array(0, 1));
        $mform->disabledIf('addmultiple', 'timeduration', 'eq', 0);
        $mform->disabledIf('repeattext', 'timeduration', 'eq', 0);
        $week = array(2 => 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $weeks = array();
        $weeks[] = $mform->createElement('select', 'week', '', $week, false, true);
        $weeks[] = $mform->createElement('static', 'weekdesc', '', get_string('sessions', 'congrea'));
        $mform->addGroup($weeks, 'weeks', get_string('repeatweekly', 'congrea'), '', false);
        $mform->hideIf('weeks', 'timeduration', 'eq', 0);
        $mform->hideIf('weeks', 'addmultiple', 'eq', 0);
        $this->add_action_buttons();
    }

    /**
     * Validate this form.
     *
     * @param array $data submitted data
     * @param array $files not used
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $durationinminutes = $data['timeduration'];
        $currentdate = time();
        $previousday = strtotime(date('Y-m-d H:i:s', strtotime("-24 hours", $currentdate)));
        if ($data['fromsessiondate'] < $previousday) {
            $errors['fromsessiondate'] = get_string('esessiondate', 'congrea');
        }
        $expr = '/^[0-9][0-9]*$/';
        if (!preg_match($expr, $durationinminutes)) {
            $errors['timeduration'] = get_string('onlyintegerallowed', 'congrea');
        }
        if (($durationinminutes != 0) || ($durationinminutes != '')) {
            if ((($durationinminutes >= 1) && ($durationinminutes < 10)) || ($durationinminutes > 1439 )) {
                $errors['timeduration'] = get_string('errortimeduration', 'congrea');
            }
        }
        if (empty($data['moderatorid'])) {
            $errors['moderatorid'] = get_string('enrolteacher', 'congrea');
        }
        $starttime = date("Y-m-d H:i:s", $data['fromsessiondate']);
        $endtime = strtotime(date('Y-m-d H:i:s', strtotime("+$durationinminutes minutes", strtotime($starttime))));
        if (!empty($data['week'])) {
            $repeat = $data['week'];
        } else {
            $repeat = 0;
        }
        return $errors;
    }
}