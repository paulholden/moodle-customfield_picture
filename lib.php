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
 * Plugin callbacks
 *
 * @package    customfield_picture
 * @copyright  2022 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_customfield\{data, data_controller, field_controller};

/**
 * Serve plugin files from storage
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if the file not found, otherwise serve the file
 */
function customfield_picture_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    $itemid = array_shift($args);
    $datarecord = $DB->get_record(data::TABLE, ['id' => $itemid], '*', MUST_EXIST);

    $field = field_controller::create($datarecord->fieldid);
    $data = data_controller::create(0, $datarecord, $field);

    if ($field->get('type') !== 'picture' || $data->get_context() !== $context ||
            !$field->get_handler()->can_view($field, $data->get('instanceid'))) {
        return false;
    }

    $filename = array_pop($args);
    $file = get_file_storage()->get_file($context->id, 'customfield_picture', $filearea, $itemid, '/', $filename);
    if (!$file) {
        return false;
    }

    // Success, serve the file.
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}
