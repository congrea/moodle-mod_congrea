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
 * The main congrea recording upload form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/congrea/locallib.php');


/**
 * Settings form for the quiz module.
 *
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_congrea_upload_file extends moodleform {
    /** Constructor
     * @param moodle_url $submiturl
     * @param object $cm
     * @param object $congrea
     * @param context $context
     */
    public function __construct($submiturl, $cm, $congrea, $context) {
        $this->cm = $cm;
        $this->congrea = $congrea;
        $this->context = $context;
        parent::__construct($submiturl, null, 'post');
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $cm = $this->cm;
        $mform = $this->_form;
        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }

        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addElement('filepicker', 'userfile', get_string('file'), null,
                   array('maxbytes' => $CFG->maxbytes, 'accepted_types' => '*'));
        $mform->addRule('userfile', null, 'required');
        // Add standard buttons, common to all modules.
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
        $supportedtypes = array('vcp');
        $files = $this->get_draft_files('userfile');
        if ($files) {
            foreach ($files as $file) {
                if (!in_array(strtolower(preg_replace('/^.*\./', '', $file->get_filename())), $supportedtypes)) {
                    $errors['userfile'] = get_string('unsupportedfiletype', 'congrea');
                }
            }
        }
        return $errors;
    }

}
