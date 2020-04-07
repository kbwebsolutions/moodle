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
namespace local_commentbank\services;

defined('MOODLE_INTERNAL') || die;

use stdClass;

use local_tlcore\datatable\query;
use local_tlcore\datatable\column;
use local_commentbank\lib\comment_lib;

class commentbank {

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var integer
     */
    protected $count = 0;

    /**
     * @var array
     */
    protected $data = [];

    public function __construct() {
        $this->set_columns();
    }


    protected function set_columns() {

        $this->columns = [
            new column(get_string('id', 'local_commentbank'), 'id', true, 'thFilter', '', 'width: 10%'),
            new column('', 'edit', false, '', 'tdHTML', 'width: 1px'),
            new column('', 'delete', false, '', 'tdHTML', 'width: 1%'),
            new column(get_string('context', 'role'), 'contextlevel', true, '', '', 'width: 20%'),
            new column(get_string('instance', 'local_commentbank'), 'instanceid', true, '', 'tdHTML', 'width: 20%'),
            new column(get_string('authoredby', 'local_commentbank'), 'authoredbyname', true, 'thFilter', '', 'width: 20%'),
            new column(get_string('commenttext', 'local_commentbank'), 'commenttext', true, 'thFilter', 'tdHTML', 'width: 30%')
        ];
    }

    /**
     * Run query.
     *
     * @param query $query
     * @return array
     */
    public function run_query(query $query) {

        $this->count = $this->get_data_count($query);
        $this->data  = $this->get_data($query);

        return [
            'columns' => $this->columns,
            'data'    => $this->data,
            'total'   => $this->count,
            'query'   => $query
        ];
    }

    /**
     * Convert an array of select fields to a map whereby aliases can be resolved to verbose db field - i.e table.field
     * @param array $selectfields
     * @return array
     */
    private function convert_selectfields_to_filter_map(array $selectfields) {
        $map = [];
        foreach ($selectfields as $selectfield) {
            if (stripos($selectfield, ' as ') !== false) {
                $arr = preg_split("/ as /i", $selectfield);
                $map[$arr[1]] = $arr[0];
            } else if (stripos($selectfield, '.') !== false) {
                $arr = explode('.', $selectfield);
                $map[$arr[1]] = $selectfield;
            }
        }
        return $map;
    }

    /**
     * @param array $selectfields
     * @param query $query
     * @return array
     */
    private function get_filter_sql_param(array $selectfields, query $query) {
        global $DB;

        static $f = 0;
        $f ++;

        $filtersql = null;
        $filterparam = null;
        if (empty($query->filter)) {
            return [$filtersql, $filterparam];
        }
        $filtermap = $this->convert_selectfields_to_filter_map($selectfields);
        $filterarr = explode('~', $query->filter);
        $field = $filterarr[0];
        if (isset($filtermap[$field])) {
            $field = $filtermap[$field]; // Convert alias back to verbose table name so we can use it in WHERE clause.
        }
        $term = $filterarr[1];
        $pname = 'filt'.$f;
        $filtersql = $DB->sql_like($field, ':' . $pname, false);
        $filterparam = '%'.$DB->sql_like_escape($term).'%';
        return [$filtersql, $pname, $filterparam];
    }

    /**
     * Commentbank SQL and params.
     *
     * @param query $query
     * @return array (sql, params)
     */
    private function sql_params(query $query) {
        global $DB;

        $params = [];

        $sqlauthoredbyname = $DB->sql_concat_join("' '", ['ua.firstname', 'ua.lastname']);

        $selectfields = [
            'cb.id',
            'cb.commenttext',
            'cb.contextlevel',
            'cb.instanceid',
            "$sqlauthoredbyname as authoredbyname"
        ];

        $sqlselectfields = implode(', ', $selectfields);

        $sqlstring = "SELECT $sqlselectfields
                        FROM {local_commentbank} cb
                   LEFT JOIN {user} ua ON ua.id = cb.authoredby";

        $filtersql = '';

        if (!empty($query->filter)) {
            list ($filtersql, $filterpname, $filterparam) = $this->get_filter_sql_param($selectfields, $query);
        }

        if (!empty($filtersql)) {
            $sqlstring .= ' WHERE ' . $filtersql;
            $params[$filterpname] = $filterparam;
        }

        if (!empty($query->sort)) {
            $sort = $query->sort;
            $sqlstring .= " ORDER BY $sort {$query->order}";
        } else {
            $sqlstring .= " ORDER BY cb.timemodified desc ";
        }
        return [$sqlstring, $params];
    }

    /**
     * Get comment bank data.
     * @param query $query
     * @return void
     */
    private function get_data_count(query $query) {
        /**@var \moodle_database $DB*/
        global $DB;

        list ($sqlstring, $params) = $this->sql_params($query);
        if (empty($sqlstring)) {
            return 0;
        }
        $sqlstring = "SELECT count(1) AS total FROM ($sqlstring) subqry";
        return $DB->count_records_sql($sqlstring, $params);
    }

    /**
     * @param query $query
     * @return array
     * @throws \dml_exception
     */
    private function get_data(query $query) {
        global $DB;

        list ($sqlstring, $params) = $this->sql_params($query);
        $rs = $DB->get_recordset_sql($sqlstring, $params, $query->offset, $query->limit);

        $return = [];
        foreach ($rs as $row) {
            $this->transform_row($row);
            $return[] = $row;
        }
        return $return;
    }

    /**
     * Transform row fields.
     *
     * @param stdClass $row
     * @return void
     */
    private function transform_row(stdClass $row) {
        global $DB, $CFG;
        $row->edit = '<a href=index.php?rowid='.$row->id.'&action=edit><i class="icon fa fa-cog"></i></a>';
        $row->delete = '<a href=index.php?rowid='.$row->id.'&action=delete><i class="icon fa fa-trash"></i></a>';
        $row->commenttext = '<a href=index.php?rowid='.$row->id.'&action=edit>'.$row->commenttext.'</a>';

        switch ($row->contextlevel) {
            case CONTEXT_SYSTEM:
                $row->contextlevel = get_string('coresystem');
                $row->instanceid = '';
                break;
            case CONTEXT_COURSE:
                $row->contextlevel = get_string('course');
                $course = get_course($row->instanceid);
                $row->instanceid = $course->fullname;
                break;
            case CONTEXT_COURSECAT:
                $row->contextlevel = get_string('coursecategory');
                $row->instanceid =  $DB->get_record('course_categories', ['id'=>$row->instanceid])->name;
                break;
            case CONTEXT_MODULE:
              $row->contextlevel = get_string('assignment', 'local_commentbank');
              $result =  comment_lib::get_assignment($row->instanceid);
              $html = '';
              if ($result) {
                $html = '<a href='.$CFG->wwwroot.'/course/view.php?id='.$result->courseid.'>';
                $html .= $result->shortname.':'.$result->assignmentname.'</a>';
              }
              $row->instanceid = $html;
            break;
        }
        return $row;
    }


}
