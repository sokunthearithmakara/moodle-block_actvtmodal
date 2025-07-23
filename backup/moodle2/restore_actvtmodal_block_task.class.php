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
 * Define all the backup steps that will be used by the backup_block_task
 *
 * @package    block_actvtmodal
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_actvtmodal_block_task extends restore_block_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
    }

    /**
     * Define the associated file areas
     */
    public function get_fileareas() {
        return []; // No associated fileareas.
    }

    /**
     * Define special handling of configdata.
     */
    public function get_configdata_encoded_attributes() {
        return []; // No special handling of configdata.
    }

    /**
     * Update the disablecm
     */
    public function after_restore() {
        global $DB;

        // Get the blockid.
        $blockid = $this->get_blockid();

        if ($configdata = $DB->get_field('block_instances', 'configdata', ['id' => $blockid])) {
            $config = $this->decode_configdata($configdata);
            if (!empty($config->disablecm)) {
                // Get the mapping and replace it in config.
                $disablecm = explode(',', $config->disablecm);
                $newcm = [];
                foreach ($disablecm as $cm) {
                    $mapping = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $cm);
                    if ($mapping) {
                        $newcm[] = $mapping->newitemid;
                    }
                }
                $config->disablecm = implode(',', $newcm);

                // Encode and save the config.
                $configdata = base64_encode(serialize($config));
                $DB->set_field('block_instances', 'configdata', $configdata, ['id' => $blockid]);
            }
        }
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        return [];
    }
}
