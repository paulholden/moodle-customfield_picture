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

use backup_nested_element;
use html_writer;
use moodle_url;
use MoodleQuickForm;
use stdClass;

/**
 * Data controller class
 *
 * @package    customfield_picture
 * @copyright  2022 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_controller extends \core_customfield\data_controller {

    /**
     * Return the name of the field where the information is stored
     *
     * @return string
     */
    public function datafield(): string {
        return 'intvalue';
    }

    /**
     * Return options suitable for the file manager element
     *
     * @return array
     */
    private function get_filemanager_options(): array {
        return [
            'maxbytes' => $this->get_field()->get_configdata_property('maximumbytes'),
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => 'web_image',
        ];
    }

    /**
     * Add form elements for editing the custom field instance
     *
     * @param MoodleQuickForm $mform
     */
    public function instance_form_definition(MoodleQuickForm $mform): void {
        $mform->addElement('filemanager', $this->get_form_element_name(), $this->get_field()->get_formatted_name(), null,
            $this->get_filemanager_options());
    }

    /**
     * Prepare file draft area prior to loading form
     *
     * @param stdClass $data
     */
    public function instance_form_before_set_data(stdClass $data): void {
        $draftid = file_get_submitted_draft_itemid($this->get_form_element_name());

        file_prepare_draft_area($draftid, $this->get_context()->id, 'customfield_picture', 'file', $this->get('id'),
            $this->get_filemanager_options());

        $data->{$this->get_form_element_name()} = $draftid;
    }

    /**
     * Move submitted file to storage
     *
     * @param stdClass $data
     */
    public function instance_form_save(stdClass $data): void {
        $fieldname = $this->get_form_element_name();

        // Trigger save.
        parent::instance_form_save((object) [$fieldname => 1]);

        file_save_draft_area_files($data->{$fieldname}, $this->get_context()->id, 'customfield_picture', 'file',
            $this->get('id'), $this->get_filemanager_options());
    }

    /**
     * Returns the default value in non human-readable format
     *
     * @return int
     */
    public function get_default_value(): int {
        return 0;
    }

    /**
     * Implement the backup callback in order to include embedded files.
     *
     * @param \backup_nested_element $customfieldelement
     * @return void
     */
    public function backup_define_structure(backup_nested_element $customfieldelement): void {
        $annotations = $customfieldelement->get_file_annotations();

        if (!isset($annotations['customfield_picture']['file'])) {
            $customfieldelement->annotate_files('customfield_picture', 'file', 'id');
        }
    }

    /**
     * Implement the restore callback in order to restore embedded files.
     *
     * @param \restore_structure_step $step
     * @param int $newid
     * @param int $oldid
     * @return void
     */
    public function restore_define_structure(\restore_structure_step $step, int $newid, int $oldid): void {
        if (!$step->get_mappingid('customfield_picture_data', $oldid)) {
            $step->set_mapping('customfield_picture_data', $oldid, $newid, true);
            $step->add_related_files('customfield_picture', 'file', 'customfield_picture_data');
        }
    }

    /**
     * Returns value in a human-readable format
     *
     * @return string|null
     */
    public function export_value(): ?string {
        $files = get_file_storage()->get_area_files($this->get_context()->id, 'customfield_picture', 'file', $this->get('id'),
            '', false);

        if (empty($files)) {
            return null;
        }

        $file = reset($files);
        $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
            $file->get_itemid(), $file->get_filepath(), $file->get_filename());

        return html_writer::img((string) $fileurl, $this->get_field()->get_formatted_name());
    }

    /**
     * Delete individual field data
     *
     * @return bool
     */
    public function delete(): bool {
        get_file_storage()->delete_area_files($this->get_context()->id, 'customfield_picture', 'file', $this->get('id'));

        return parent::delete();
    }
}
