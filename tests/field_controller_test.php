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
use core_customfield_generator;
use core_customfield\field_config_form;

/**
 * Tests for the field controller
 *
 * @package    customfield_picture
 * @covers     \customfield_picture\field_controller
 * @copyright  2022 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_controller_test extends advanced_testcase {

    /**
     * Test that using base field controller returns our picture type
     */
    public function test_create(): void {
        $this->resetAfterTest();

        /** @var core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $generator->create_category();
        $field = $generator->create_field(['categoryid' => $category->get('id'), 'type' => 'picture']);

        $this->assertInstanceOf(field_controller::class, \core_customfield\field_controller::create((int) $field->get('id')));
        $this->assertInstanceOf(field_controller::class, \core_customfield\field_controller::create(0, $field->to_record()));
    }

    /**
     * Test submitting field definition form
     */
    public function test_form_definition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var core_customfield_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $generator->create_category();
        $field = $generator->create_field([
            'categoryid' => $category->get('id'),
            'type' => 'picture',
            'configdata' => [
                'maximumbytes' => 1024,
            ],
        ]);

        $submitdata = (array) $field->to_record();
        $submitdata['configdata'] = $field->get('configdata');

        $formdata = field_config_form::mock_ajax_submit($submitdata);
        $form = new field_config_form(null, null, 'post', '', null, true, $formdata, true);

        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();
    }
}
