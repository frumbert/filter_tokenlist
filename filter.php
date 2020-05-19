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
 * Version details
 *
 * @package    filter
 * @subpackage tokenlist
 * @copyright  tim.stclair@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_tokenlist extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        global $DB;

        // match predefined shortcode
        if (strpos($text, "[[list-available-tokens]]") !==-1) {

            // get the token with the most seats available for each course
            // that has tokens that are not expired
            $rows = $DB->get_records_sql("
                SELECT t.id token, c.fullname, t.courseid, t.seatsavailable
                FROM {enrol_token_tokens} t INNER JOIN {course} c ON t.courseid = c.id
                WHERE t.courseid IN (
                    SELECT DISTINCT(courseid)
                    FROM {enrol_token_tokens}
                    WHERE seatsavailable > 0 AND (timeexpire = 0 OR timeexpire > ?)
                ) GROUP BY t.courseid ORDER BY t.seatsavailable DESC
            ", [time()]);
            // build a quick table of that data
            $table = new html_table();
            $table->head = [
                get_string('course'),
                get_string('token', 'filter_tokenlist'),
                get_string('seats', 'filter_tokenlist')
            ];
            foreach ($rows as $row) {
                $table->data[] = [
                    html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), $row->fullname),
                    $row->token,
                    $row->seatsavailable
                ];
            }
            // substitute the table for the shortcode
            $text = str_replace("[[list-available-tokens]]", html_writer::table($table), $text);
        }
        return $text;
    }
}