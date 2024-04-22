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

use advanced_testcase;
use context_user;
use core_customfield_generator;
use core_customfield_test_instance_form;
use core_customfield\data;

/**
 * Tests for the data controller
 *
 * @package    customfield_picture
 * @covers     \customfield_picture\data_controller
 * @copyright  2022 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class data_controller_test extends advanced_testcase {

    /**
     * Test that using base field controller returns our picture type
     */
    public function test_create(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $generator->create_category();
        $field = $generator->create_field(['categoryid' => $category->get('id'), 'type' => 'picture']);
        $data = $generator->add_instance_data($field, (int) $course->id, 1);

        $this->assertInstanceOf(data_controller::class, \core_customfield\data_controller::create($data->get('id')));
        $this->assertInstanceOf(data_controller::class, \core_customfield\data_controller::create(0, $data->to_record()));
        $this->assertInstanceOf(data_controller::class, \core_customfield\data_controller::create(0, null, $field));
    }

    /**
     * Test submitting field instance form
     */
    public function test_form_save(): void {
        global $CFG, $USER;

        require_once("{$CFG->dirroot}/customfield/tests/fixtures/test_instance_form.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $generator->create_category();
        $field = $generator->create_field(['categoryid' => $category->get('id'), 'type' => 'picture']);

        // Populate user draft area.
        $draftid = file_get_unused_draft_itemid();
        $filerecord = [
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftid,
            'filepath'  => '/',
            'filename'  => 'logo.png',
        ];
        get_file_storage()->create_file_from_pathname($filerecord, "{$CFG->dirroot}/lib/tests/fixtures/gd-logo.png");

        $formdata = array_merge((array) $course, ['customfield_' . $field->get('shortname')  => $draftid]);
        core_customfield_test_instance_form::mock_submit($formdata);

        $form = new core_customfield_test_instance_form('POST', ['handler' => $category->get_handler(), 'instance' => $course]);
        $this->assertTrue($form->is_validated());

        $formsubmission = $form->get_data();
        $category->get_handler()->instance_form_save($formsubmission);

        // Validate file was stored.
        $datainstance = data::get_record(['fieldid' => $field->get('id'), 'instanceid' => $formsubmission->id]);
        $files = get_file_storage()->get_area_files($datainstance->get('contextid'), 'customfield_picture', 'file',
            $datainstance->get('id'), '', false);

        $this->assertCount(1, $files);
        $file = reset($files);

        $this->assertEquals('/', $file->get_filepath());
        $this->assertEquals('logo.png', $file->get_filename());
    }

    /**
     * Test exporting instance
     */
    public function test_export_value(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        /** @var core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $generator->create_category();
        $field = $generator->create_field(['categoryid' => $category->get('id'), 'type' => 'picture']);
        $data = $generator->add_instance_data($field, (int) $course->id, 1);

        // Populate file area.
        $filerecord = [
            'contextid' => $data->get('contextid'),
            'component' => 'customfield_picture',
            'filearea'  => 'file',
            'itemid'    => $data->get('id'),
            'filepath'  => '/',
            'filename'  => 'logo.png',
        ];
        get_file_storage()->create_file_from_pathname($filerecord, "{$CFG->dirroot}/lib/tests/fixtures/gd-logo.png");

        $result = \core_customfield\data_controller::create($data->get('id'))->export_value();
        $this->assertStringStartsWith('<img src="', $result);
        $this->assertStringContainsString("alt=\"{$field->get_formatted_name()}\"", $result);
    }
}
