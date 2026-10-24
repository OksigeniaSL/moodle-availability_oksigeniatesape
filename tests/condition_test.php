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

namespace availability_oksigeniatesape;

use core_availability\tree;
use local_oksigeniatesape\local\characters;
use local_oksigeniatesape\local\missions;
use local_oksigeniatesape\local\regions;

/**
 * Tests for the restriction by Tesapé.
 *
 * @package    availability_oksigeniatesape
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_oksigeniatesape\condition
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    public function test_a_place_lit_by_the_class_and_the_level_of_the_hero_open_an_activity(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $CFG->enableavailability = true;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $c = (int) $course->id;
        regions::save($c, ['enabled' => 1]);
        $mission = missions::install($c, 'livingthings', 'valley', 'es');
        $user = $gen->create_and_enrol($course, 'student');
        $char = characters::create($user->id, 'scout', 'f');
        $page = $gen->create_module('page', ['course' => $c]);
        $info = new \core_availability\info_module(get_fast_modinfo($course)->get_cm($page->cmid));
        $places = condition::places($c);
        $this->assertNotEmpty($places);
        $code = $places[1]['code'];
        // The class has not lit the second place yet.
        $place = new condition(condition::get_json(condition::PLACE, $code));
        $this->assertFalse($place->is_available(false, $info, true, $user->id));
        $this->assertTrue($place->is_available(true, $info, true, $user->id));
        $this->assertStringContainsString($places[1]['name'], $place->get_description(true, false, $info));
        $DB->set_field('local_oksigeniatesape_chapter', 'litat', time(), ['missionid' => $mission->id, 'code' => $code]);
        $this->assertTrue($place->is_available(false, $info, true, $user->id));
        // Level three: the hero is at one.
        $level = new condition(condition::get_json(condition::LEVEL, 3));
        $this->assertFalse($level->is_available(false, $info, true, $user->id));
        $DB->set_field('local_oksigeniatesape_char', 'level', 3, ['id' => $char->id]);
        $this->assertTrue($level->is_available(false, $info, true, $user->id));
        $this->assertSame(
            get_string('requires_level', 'availability_oksigeniatesape', 3),
            $level->get_description(true, false, $info)
        );
        // Without a hero, no level.
        $other = $gen->create_and_enrol($course, 'student');
        $this->assertFalse($level->is_available(false, $info, true, $other->id));
        // Saved as it came.
        $this->assertEquals((object) ['type' => 'oksigeniatesape', 'c' => 'place', 'place' => $code], $place->save());
        $this->assertEquals((object) ['type' => 'oksigeniatesape', 'c' => 'level', 'level' => 3], $level->save());
        // In a tree of conditions, as Moodle reads it.
        $tree = new tree(tree::get_root_json([condition::get_json(condition::LEVEL, 3)]));
        $this->assertTrue($tree->check_available(false, $info, true, $user->id)->is_available());
    }

    public function test_in_a_game_more_the_place_is_that_of_its_class(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $CFG->enableavailability = true;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $c = (int) $course->id;
        regions::save($c, ['enabled' => 1]);
        missions::install($c, 'livingthings', 'valley', 'es');
        // 5th B plays apart, in a game more of the course, with its own mission.
        $group = $gen->create_group(['courseid' => $c, 'name' => '5.º B']);
        $user = $gen->create_and_enrol($course, 'student');
        $gen->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);
        $key = \local_oksigeniatesape\local\games::create($c, (int) $group->id);
        regions::save($key, ['enabled' => 1]);
        $theirs = missions::install($key, 'livingthings', 'valley', 'es');
        $page = $gen->create_module('page', ['course' => $c]);
        $info = new \core_availability\info_module(get_fast_modinfo($course)->get_cm($page->cmid));
        $code = condition::places($c)[1]['code'];
        $place = new condition(condition::get_json(condition::PLACE, $code));
        // The game of the course lights it: not theirs.
        $DB->set_field('local_oksigeniatesape_chapter', 'litat', time(), ['missionid' => missions::get($c)->id, 'code' => $code]);
        $this->assertFalse($place->is_available(false, $info, true, $user->id));
        // Their class lights it.
        $DB->set_field('local_oksigeniatesape_chapter', 'litat', time(), ['missionid' => $theirs->id, 'code' => $code]);
        $this->assertTrue($place->is_available(false, $info, true, $user->id));
    }

    public function test_a_wrong_structure_is_not_a_condition(): void {
        foreach ([(object) ['c' => 'place'], (object) ['c' => 'level', 'level' => 0], (object) ['c' => 'other']] as $bad) {
            try {
                new condition($bad);
                $this->fail('Not a condition');
            } catch (\coding_exception $e) {
                $this->assertStringContainsString('Tesapé', $e->getMessage());
            }
        }
    }
}
