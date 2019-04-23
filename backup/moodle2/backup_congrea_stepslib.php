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

/** Class for the structure used for backup congrea.
 *
 * @package    mod_congrea
 * @subpackage backup-moodle2
 * @copyright 2014 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Define all the backup steps that will be used by the backup_congrea_activity_task.
 *
 * @copyright 2014 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_congrea_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the complete congrea structure for backup.
     *
     * @return object
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $congrea = new backup_nested_element('congrea', array('id'), array(
            'name', 'intro', 'introformat', 'moderatorid',
            'opentime', 'closetime', 'themecolor', 'audio', 'video', 'pushtotalk', 'timecreated',
            'timemodified'));
        $files = new backup_nested_element('files');
        $congrea->add_child($files);
        // Define sources.
        $congrea->set_source_table('congrea', array('id' => backup::VAR_ACTIVITYID));
        // Define id annotations.
        $congrea->annotate_ids('user', 'moderatorid');
        // Define file annotations.
        $congrea->annotate_files('mod_congrea', 'intro', null); // This file area hasn't itemid.
        // Return the root element (congrea), wrapped into standard activity structure.
        return $this->prepare_activity_structure($congrea);
    }

}
