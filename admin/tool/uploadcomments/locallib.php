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
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard comment fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function uu_validate_comments_upload_columns(csv_import_reader $cir, $stdfields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // Test columns.
    $processed = array();
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];

        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) || in_array($lcfield, $stdfields)) {
            // Standard fields are only lowercase.
            $newfield = $lcfield;
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}

function create_new_comment($comment) {
    global $DB, $USER;

    $comment->authoredby = $USER->id;
    return $DB->insert_record('local_commentbank', $comment, true);

}

/**
 * Tracking of processed comments.
 *
 * This class prints comment information into a html table.
 *
 * @package    core
 * @subpackage admin
 * @copyright  2007 Petr Skoda  {@link http://skodak.org} and 2020 Kieran Briggs (kbriggs@chartered.college)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uc_progress_tracker {
    private $_row;
    public $columns = array('line', 'comment', 'context', 'instance', 'status');

    /**
     * Print table header.
     *
     * @return void
     */
    public function start() {
        $ci = 0;
        echo '<table id="ucresults" class="generaltable boxaligncenter flexible-wrap" summary="' .
                get_string('uploaccommentsresults', 'tool_uploadcomments') . '">';
        echo '<tr class="heading r0">';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('uccsvline', 'tool_uploadcomments') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('comment', 'tool_uploadcomments') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('context', 'tool_uploadcomments') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('instance', 'tool_uploadcomments') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('status') . '</th>';
        echo '</tr>';
        $this->_row = null;
    }

    /**
     * Flush previous line and start a new one.
     *
     * @return void
     */
    public function flush() {
        if (empty($this->_row) || empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number.
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r' . $ri . '">';
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uc' . $type . '">' . $field[$type] . '</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c' . $ci++ . '">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    /**
     * Add tracking info
     *
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true) {
        if (empty($this->_row)) {
            $this->flush();
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:' . $col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .= '<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Print the table end
     *
     * @return void
     */
    public function close() {
        $this->flush();
        echo '</table>';
    }


}