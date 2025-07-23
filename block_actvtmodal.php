<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block actvtmodal is defined here.
 *
 * @package     block_actvtmodal
 * @copyright   2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_actvtmodal extends block_base {
    /**
     * Block initialisation
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_actvtmodal');
    }

    /**
     * Returns the formats where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => false, 'course' => true];
    }

    /**
     * Get content
     *
     * @return stdClass
     */
    public function get_content() {
        $config = $this->config;
        $sitecss = get_config('block_actvtmodal', 'css');
        $this->content = (object)[
            'text' => '<textarea name="configjson" id="configjson" style="display: none;">'
                . json_encode($config) . '</textarea>
                <textarea name="sitecss" id="sitecss" style="display: none;">' . $sitecss . '</textarea>',
        ];
        return $this->content;
    }

    /**
     * Hide the block header.
     *
     * @return bool
     */
    public function hide_header() {
        return true;
    }

    /**
     * Loads the required JavaScript for the block.
     *
     * @return void
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        global $USER;
        // Add the main JavaScript file for the block.
        $this->page->requires->js_call_amd('block_actvtmodal/block_main', 'init', [
            'blockid' => $this->instance->id,
            'contextid' => $this->context->id,
            'courseid' => $this->page->course->id,
            'userid' => $USER->id,
            'canedit' => has_capability('moodle/course:update', context_course::instance($this->page->course->id)),
        ]);
    }

    /**
     * Returns true if this block has any settings.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }
}
