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
 * @package    mod_congrea
 * @subpackage backup-moodle2
 * @copyright 2014 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_congrea_activity_task
 */

/**
 * Structure step to restore one congrea activity
 */
class restore_congrea_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('congrea', '/activity/congrea');
        if ($userinfo) {
            $files = new restore_path_element('congrea_files',
                                                   '/activity/congrea/files/file');
            $paths[] = $files;
        }
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_congrea($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->moderatorid = $this->get_mappingid('user', $data->moderatorid);
        $data->opentime = $this->apply_date_offset($data->opentime);
        $data->closetime = $this->apply_date_offset($data->closetime);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (!isset($data->themecolor)) {
            $data->themecolor = 'black';
        }
        if (!isset($data->audio)) {
            $data->audio = 0;
        }
        if (!isset($data->pushtotalk)) {
            $data->pushtotalk = 0;
        }
        // Insert the congrea record.
        $newitemid = $DB->insert_record('congrea', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function recurse_copy_files($src, $dst) {

        $dir = opendir($src);
        if (!file_exists ($dst) ) {
            mkdir($dst, 0777, true);
        }
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
    closedir($dir);
    }

    /**
     * Process a congrea files restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_congrea_files($data) {
        global $DB, $CFG;

        $data = (object)$data;
        $oldid = $data->id;


        $olddata = $DB->get_record('congrea_files', array('id' => $data->id));
        $filepath = "{$CFG->dataroot}/congrea/{$olddata->courseid}/{$olddata->vcid}/".$data->vcsessionkey;

        $data->courseid = $this->get_courseid();
        $data->vcid = $this->get_new_parentid('congrea');
        $vcsessionkey = 'b'.time();
        // New path where file will be copied
        $newfilepath = "{$CFG->dataroot}/congrea/{$data->courseid}/{$data->vcid}/".$vcsessionkey;
        // Copy file to new destination
        $this->recurse_copy_files($filepath, $newfilepath);

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        if ($data->userid > 0) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }
        if (!isset($data->vcsessionkey)) {
            $data->vcsessionkey = '';
        } else {
            $data->vcsessionkey = $vcsessionkey;
        }
        if (!isset($data->vcsessionname)) {
            $data->vcsessionname = null;
        }
        if (!isset($data->numoffiles)) {
            $data->numoffiles = 0;
        }

        $newitemid = $DB->insert_record('congrea_files', $data);
        // Note - the old contextid is required in order to be able to restore files stored in
        // sub plugin file areas attached to the submissionid.
        $this->set_mapping('congrea_files', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        // Add congrea related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_congrea', 'intro', null);
    }
}
