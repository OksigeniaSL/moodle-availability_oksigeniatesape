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

use core_availability\info;
use local_oksigeniatesape\local\characters;
use local_oksigeniatesape\local\missions;

/**
 * Restriction by Tesapé: an activity or a section opens when the class lights the lantern of a place of the mission
 * of the course, or when the hero of the student reaches a level.
 *
 * @package    availability_oksigeniatesape
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string The class lights the lantern of a place. */
    public const PLACE = 'place';

    /** @var string The hero of the student reaches a level. */
    public const LEVEL = 'level';

    /** @var string PLACE or LEVEL. */
    protected $kind;

    /** @var string The code of the place. */
    protected $place = '';

    /** @var int The level. */
    protected $level = 0;

    /**
     * The condition from its saved structure.
     *
     * @param \stdClass $structure c (place or level), and place or level.
     */
    public function __construct($structure) {
        $kind = $structure->c ?? '';
        if ($kind === self::PLACE && isset($structure->place) && is_string($structure->place) && $structure->place !== '') {
            $this->place = clean_param($structure->place, PARAM_ALPHANUMEXT);
        } else if ($kind === self::LEVEL && isset($structure->level) && is_int($structure->level) && $structure->level >= 1) {
            $this->level = $structure->level;
        } else {
            throw new \coding_exception('Invalid structure of a restriction by Tesapé');
        }
        $this->kind = $kind;
    }

    /**
     * The structure to save.
     *
     * @return \stdClass
     */
    public function save() {
        return self::get_json($this->kind, $this->kind === self::PLACE ? $this->place : $this->level);
    }

    /**
     * The structure of a condition, for tests and for code that adds one.
     *
     * @param string $kind PLACE or LEVEL.
     * @param string|int $value The code of the place, or the level.
     * @return \stdClass
     */
    public static function get_json(string $kind, $value): \stdClass {
        $json = ['type' => 'oksigeniatesape', 'c' => $kind];
        if ($kind === self::PLACE) {
            $json['place'] = (string) $value;
        } else {
            $json['level'] = (int) $value;
        }
        return (object) $json;
    }

    /**
     * Whether it lets the user in.
     *
     * @param bool $not
     * @param info $info
     * @param bool $grabthelot
     * @param int $userid
     * @return bool
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        $allow = $this->kind === self::PLACE ? self::lit((int) $info->get_course()->id, $this->place) :
            self::level((int) $userid) >= $this->level;
        return $not ? !$allow : $allow;
    }

    /**
     * What it asks for, in words.
     *
     * @param bool $full
     * @param bool $not
     * @param info $info
     * @return string
     */
    public function get_description($full, $not, info $info) {
        $a = $this->kind === self::PLACE ? self::place_name((int) $info->get_course()->id, $this->place) : $this->level;
        return get_string('requires_' . $this->kind . ($not ? '_not' : ''), 'availability_oksigeniatesape', $a);
    }

    /**
     * For the debugging of the tree of conditions.
     *
     * @return string
     */
    protected function get_debug_string() {
        return $this->kind . ' ' . ($this->kind === self::PLACE ? $this->place : $this->level);
    }

    /**
     * Whether the class of a course has lit the lantern of a place of its mission (or passed it by).
     *
     * @param int $courseid
     * @param string $code
     * @return bool
     */
    public static function lit(int $courseid, string $code): bool {
        global $DB;
        $mission = missions::get($courseid);
        return $mission && $DB->record_exists_select(
            'local_oksigeniatesape_chapter',
            'missionid = ? AND code = ? AND litat > 0',
            [$mission->id, $code]
        );
    }

    /**
     * The level of the hero of a user (0 without a hero).
     *
     * @param int $userid
     * @return int
     */
    public static function level(int $userid): int {
        $char = characters::get($userid);
        return $char ? (int) $char->level : 0;
    }

    /**
     * The places of the mission of a course, in their order: code and name.
     *
     * @param int $courseid
     * @return array
     */
    public static function places(int $courseid): array {
        $mission = missions::get($courseid);
        $out = [];
        foreach ($mission ? ($mission->data['chapters'] ?? []) : [] as $chapter) {
            $out[] = ['code' => (string) $chapter['code'], 'name' => missions::text(
                $chapter['name'] ?? $chapter['code'],
                (string) $mission->skin,
                (string) $mission->variant
            )];
        }
        return $out;
    }

    /**
     * The name of a place of the mission of a course.
     *
     * @param int $courseid
     * @param string $code
     * @return string
     */
    protected static function place_name(int $courseid, string $code): string {
        foreach (self::places($courseid) as $place) {
            if ($place['code'] === $code) {
                return $place['name'];
            }
        }
        return get_string('unknownplace', 'availability_oksigeniatesape');
    }
}
