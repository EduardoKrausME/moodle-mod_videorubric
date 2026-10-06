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
 * submission_submitted.php
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videorubric\event;

/**
 * Class submission_submitted.
 */
final class submission_submitted extends \core\event\base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videorubric_submission';
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string('event:submissionsubmitted', 'mod_videorubric');
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted video submission '{$this->objectid}'.";
    }

    /**
     * Method get_objectid_mapping.
     *
     * @return array Return value.
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'videorubric_submission', 'restore' => 'videorubric_submission'];
    }

    /**
     * Method get_other_mapping.
     *
     * @return array Return value.
     */
    public static function get_other_mapping(): array {
        return ['videorubricid' => ['db' => 'videorubric', 'restore' => 'videorubric']];
    }
}
