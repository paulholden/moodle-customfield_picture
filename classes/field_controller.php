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

declare(strict_types=1);

namespace customfield_picture;

use MoodleQuickForm;

/**
 * Field controller class
 *
 * @package    customfield_picture
 * @copyright  2022 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_controller  extends \core_customfield\field_controller {

    /**
     * Add form elements for editing the custom field definition
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form_definition(MoodleQuickForm $mform): void {
        global $CFG;

        $mform->addElement('header', 'header_specificsettings', get_string('specificsettings', 'core_customfield'));
        $mform->setExpanded('header_specificsettings', true);

        // Maximum upload size.
        $mform->addElement('select', 'configdata[maximumbytes]', get_string('maxbytes', 'core_admin'),
            get_max_upload_sizes($CFG->maxbytes));
        $mform->setType('configdata[maximumbytes]', PARAM_INT);
    }

    /**
     * Delete all field data
     *
     * @return bool
     */
    public function delete(): bool {
        global $DB;

        // Delete all files linked to the field being deleted.
        $select = 'component = :component AND itemid IN (SELECT id FROM {customfield_data} WHERE fieldid = :fieldid)';

        $files = $DB->get_records_select('files', $select, ['component' => 'customfield_picture', 'fieldid' => $this->get('id')]);
        foreach ($files as $file) {
            get_file_storage()->get_file_instance($file)->delete();
        }

        return parent::delete();
    }
}
