# Activity Modal #

Display an activity in a popup modal.

## Main features ##
- Displays an activity in a popup modal from a course content link or course index link.
- Supports all activity types (tested with standard activities).
- Independent of the theme and course format*.
- Excludes certain activity types and specific activities.

## Limitations ##
- Certain course formats (e.g. Tiles) implement their own activity modals on certain activity types. In this case, the feature will be conflicting. In such cases, teachers can disable the popup modal for those activity types.
- Certain non-standard themes (e.g. Adaptable, Universe) implement a ton of customizations. The embeded activity in the modal may not display the way it should. In such cases, site admins can inject additional CSS in the activity modal settings to fix the issues. Teachers can also inject additional CSS in their specific course.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/actvtmodal

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
