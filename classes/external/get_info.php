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

namespace block_actvtmodal\external;

use external_function_parameters;
use external_single_structure;
use external_api;
use external_value;
use core_availability\info;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
/**
 * Class add
 *
 * @package    block_actvtmodal
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_info extends external_api {
    /**
     * Describes the parameters for get_info
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'cmid' => new external_value(PARAM_INT, 'Course Module ID'),
            'completiononly' => new external_value(PARAM_BOOL, 'Only get the completion details'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Implementation of web service get_info
     *
     * @param int $courseid Course ID
     * @param int $cmid Course Module ID
     * @param bool $completiononly If true, get completion only
     * @param int $userid User ID
     * @return array
     */
    public static function execute($courseid, $cmid, $completiononly = false, $userid = 0) {
        global $OUTPUT, $USER, $CFG;
        // Parameter validation.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'userid' => $userid,
            'completiononly' => $completiononly,
        ]);

        require_course_login($courseid);

        if (!$userid || $userid == 0) {
            $userid = $USER->id;
        }

        // Get completion information.
        $cminfo = get_fast_modinfo($courseid);
        $cm = $cminfo->get_cm($cmid);

        if (!$cm) {
            return [
                'data' => json_encode(['error' => get_string('invalidcmid', 'block_actvtmodal')]),
            ];
        }

        $response = [];
        $completiondetails = \core_completion\cm_completion_details::get_instance($cm, $userid);
        if ($cm->completion == COMPLETION_TRACKING_NONE) {
            $response['overallcompletion'] = -1;
        } else {
            $response['overallcompletion'] = $completiondetails->get_overall_completion() == COMPLETION_COMPLETE ? 1 : 0;
        }

        if ($cm->completion == COMPLETION_TRACKING_MANUAL) {
            $response['manualcompletion'] = '1';
        } else {
            $response['manualcompletion'] = '0';
        }

        if ($cm->completionview == COMPLETION_VIEW_REQUIRED) {
            $response['completionview'] = '1';
        } else {
            $response['completionview'] = '0';
        }

        if ($completiononly) {
            return [
                'data' => json_encode($response),
            ];
        }

        // Get withavailability information.
        $course = $cm->get_course();
        $withavailability = !empty($CFG->enableavailability) && info::completion_value_used($course, $cmid);
        if ($withavailability) {
            $response['withavailability'] = 1;
        } else {
            $response['withavailability'] = 0;
        }

        // Get activity information.
        $response['activity'] = [
            'name' => format_string($cm->name),
            'url' => $cm->url->__toString(),
            'icon' => $OUTPUT->image_icon('monologo', get_string('pluginname', $cm->modname), $cm->modname),
            'cmid' => $cm->id,
            'modname' => $cm->modname,
            'purpose' => plugin_supports('mod', $cm->modname, FEATURE_MOD_PURPOSE),
            'uservisible' => $cm->uservisible || $cm->deleted,
        ];

        return [
            'data' => json_encode($response),
        ];
    }

    /**
     * Describes the return value for get_info
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'The data of the response'),
        ]);
    }
}
