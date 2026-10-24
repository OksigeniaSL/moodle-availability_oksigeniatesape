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

use local_oksigeniatesape\local\regions;

/**
 * The restriction by Tesapé in the form of restrict access.
 *
 * @package    availability_oksigeniatesape
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * The strings the form uses.
     *
     * @return string[]
     */
    protected function get_javascript_strings() {
        return ['label_kind', 'kind_place', 'kind_level', 'label_place', 'label_level', 'error_place', 'error_level'];
    }

    /**
     * The places of the mission of the course, for the form.
     *
     * @param \stdClass $course
     * @param \cm_info|null $cm
     * @param \section_info|null $section
     * @return array
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        return [array_map(
            fn($p) => ['code' => $p['code'], 'name' => format_string($p['name'])],
            condition::places((int) $course->id)
        )];
    }

    /**
     * Only in courses where the game was set up.
     *
     * @param \stdClass $course
     * @param \cm_info|null $cm
     * @param \section_info|null $section
     * @return bool
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        return regions::get((int) $course->id) !== null;
    }
}
