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
 * Form for editing actvtmodal block instances.
 *
 * @package     block_actvtmodal
 * @copyright   2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_actvtmodal_edit_form extends block_edit_form {

    /**
     * Extends the configuration form for block_actvtmodal.
     *
     * @param MoodleQuickForm $mform The form being built.
     */
    protected function specific_definition($mform) {
        global $OUTPUT, $USER, $CFG;

        $config = $this->block->config;

        $dcms = $config->disablecm ? explode(',', $config->disablecm) : [];
        $dtypes = $config->disabletype ? json_decode($config->disabletype, true) : [];

        // Appearance.
        $mform->addElement('header', 'configappearance', get_string('appearance', 'block_actvtmodal'));
        // Embed options.
        $group = [];
        $group[] = $mform->createElement(
            'advcheckbox',
            'config_showpageheader',
            '',
            get_string('showpageheader', 'block_actvtmodal'),
            ['group' => 1],
            [0, 1]
        );
        $group[] = $mform->createElement(
            'advcheckbox',
            'config_showtitle',
            '',
            get_string('showtitle', 'block_actvtmodal'),
            ['group' => 1],
            [0, 1]
        );
        $group[] = $mform->createElement(
            'advcheckbox',
            'config_showactivityheader',
            '',
            get_string('showactivityheader', 'block_actvtmodal'),
            ['group' => 1],
            [0, 1]
        );
        $mform->setDefault('config_showactivityheader', 1);
        $group[] = $mform->createElement(
            'advcheckbox',
            'config_showsecondarynav',
            '',
            get_string('showsecondarynav', 'block_actvtmodal'),
            ['group' => 1],
            [0, 1]
        );
        $group[] = $mform->createElement(
            'advcheckbox',
            'config_showblock',
            '',
            get_string('showblock', 'block_actvtmodal'),
            ['group' => 1],
            [0, 1]
        );
        $mform->addGroup($group, 'embedoptiongroup', '', '', false);

        // CSS.
        $mform->addElement(
            'textarea',
            'config_css',
            get_string('css', 'block_actvtmodal'),
            ['rows' => 5, 'cols' => 100]
        );
        $mform->setType('css', PARAM_RAW);

        // Section header title.
        $mform->addElement('header', 'configheader_type', get_string('disablepopupbytype', 'block_actvtmodal'));
        $mform->setExpanded('configheader_type', true);

        $courseid = $this->block->page->course->id;
        $modinfo = get_fast_modinfo($courseid);
        $modarray = [];
        foreach ($modinfo->cms as $cm) {
            $mod = [];
            // Exclude activities that aren't visible or have no view link (e.g. label).
            // Account for folder being displayed inline.
            $mod["name"] = format_string($cm->name);
            if (isset($cm->url)) {
                $mod["url"] = $cm->url->__toString();
                $mod['hasview'] = true;
            } else {
                continue;
            }
            $modname = $cm->modname;
            $mod['id'] = $cm->id;
            $mod['type'] = $cm->modname;
            $mod['section'] = $cm->section;
            $mod['purpose'] = plugin_supports('mod', $cm->modname, FEATURE_MOD_PURPOSE);
            $mod["icon"] = $OUTPUT->image_icon(
                'monologo',
                get_string('pluginname', $modname),
                $modname,
                ['class' => 'activityicon']
            );
            array_push($modarray, $mod);
        }
        $modtype = [];
        $types = [];
        foreach ($modarray as $mod) {
            $type = [];
            if (in_array($mod['type'], $types)) {
                continue;
            } else {
                $types[] = $mod['type'];
                $type['name'] = $mod['type'];
                $type['icon'] = $mod['icon'];
                $type['purpose'] = $mod['purpose'];
                $type['label'] = get_string('pluginname', $mod['type']);
                $type['count'] = array_count_values(array_column($modarray, 'type'))[$mod['type']];
                array_push($modtype, $type);
            }
        }
        // Sort by name.
        usort($modtype, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        $text = '<div id="configtypewrapper" class="w-100 overflow-auto">';
        $text .= '<table class="table table-striped table-sm">';
        $text .= '<thead><tr>
        <th>' . get_string('moduletype', 'block_actvtmodal') . '</th>
        <th>' . get_string('disable', 'block_actvtmodal') . '</th>
        <th>' . get_string('override', 'block_actvtmodal') . '</th>
        <th>' . get_string('showpageheader', 'block_actvtmodal') . '</th>
        <th>' . get_string('showtitle', 'block_actvtmodal') . '</th>
        <th>' . get_string('showactivityheader', 'block_actvtmodal') . '</th>
        <th>' . get_string('showsecondarynav', 'block_actvtmodal') . '</th>
        <th>' . get_string('showblock', 'block_actvtmodal') . '</th>
        <th>' . get_string('urlparams', 'block_actvtmodal') . '</th>
        </tr></thead>
        <tbody>';
        // Create checkboxes for each mod type.
        foreach ($modtype as $mod) {
            $configtype = isset($dtypes[$mod['name']]) ? $dtypes[$mod['name']] : [];
            $text .= '<tr>';
            // Type label.
            $text .= '<td><div class="d-flex align-items-center">
            <div class="activity-icon activityiconcontainer smaller courseicon am-mr-2 ' . $mod['purpose'] . '">'
                . $mod['icon'] . '</div>' . $mod['label'] . '</div></td>';
            // Disable type.
            $text .= '<td><input type="checkbox" name="disabletype" value="' . $mod['name'] . '" '
                . ($configtype['d'] == true ? 'checked' : '') . '></td>';
            // Override type.
            $text .= '<td><input type="checkbox" name="overridetype" value="' . $mod['name'] . '" '
                . ($configtype['o'] == true ? 'checked' : '') . '></td>';
            // Show page header.
            $text .= '<td><input type="checkbox" name="showpageheader" value="1" '
                . ($configtype['p'] ? 'checked' : '') . '></td>';
            // Show title.
            $text .= '<td><input type="checkbox" name="showtitle" value="1" '
                . ($configtype['t'] ? 'checked' : '') . '></td>';
            // Show activity header.
            $text .= '<td><input type="checkbox" name="showactivityheader" value="1" '
                . ($configtype['a'] ? 'checked' : '') . '></td>';
            // Show secondary navigation.
            $text .= '<td><input type="checkbox" name="showsecondarynav" value="1" '
                . ($configtype['s'] ? 'checked' : '') . '></td>';
            // Show block.
            $text .= '<td><input type="checkbox" name="showblock" value="1" '
                . ($configtype['b'] ? 'checked' : '') . '></td>';
            $text .= '<td><input class="form-control form-control-sm" type="text" name="urlparams" value="'
                . $configtype['u'] . '"></td>';
            $text .= '</tr>';
        }
        $text .= '</tbody></table></div>';

        $mform->addElement('html', $text);

        $mform->addElement('header', 'configheadercm', get_string('disablepopupbycm', 'block_actvtmodal'));
        // Create checkboxes for each mod cm.
        $text = '<div id="configcmwrapper">';
        foreach ($modarray as $mod) {
            $text .= '<div class="form-check d-flex align-items-center">';
            $text .= '<input type="checkbox" name="config_modcm" value="' . $mod['id'] . '" '
                . (in_array($mod['id'], $dcms) ? 'checked' : '') . '>';
            $text .= '<label class="form-check-label d-flex align-items-center">
            <div class="activity-icon activityiconcontainer smaller  courseicon mx-2 ' . $mod['purpose'] . '">'
                . $mod['icon'] . '</div>' .  $mod['name'] . '</label>';
            $text .= '</div>';
        }
        $text .= '</div>';

        $mform->addElement('html', $text);

        $mform->addElement('hidden', 'config_disabletype', '');
        $mform->setType('config_disabletype', PARAM_TEXT);

        $mform->addElement('hidden', 'config_disablecm', '');
        $mform->setType('config_disablecm', PARAM_TEXT);

        $this->set_display_vertical();

        // Here we initialize the JS when the block is edited inline as opposed to in popup.
        if ($this->optional_param('bui_editid', 0, PARAM_INT)) {
            $mform->addElement('html', $OUTPUT->render_from_template(
                'block_actvtmodal/js',
                [
                    'courseid' => $this->block->page->course->id,
                    'userid' => $USER->id,
                ]
            ));
        }
    }
}
