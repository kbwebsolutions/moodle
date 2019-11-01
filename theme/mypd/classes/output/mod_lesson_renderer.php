<?php


class theme_mypd_mod_lesson_renderer extends \mod_lesson_renderer
{

    /**
     * Returns the HTML for displaying the end of lesson page.
     *
     * @param  lesson $lesson lesson instance
     * @param  stdclass $data lesson data to be rendered
     * @return string         HTML contents
     */
    public function display_eol_page(lesson $lesson, $data) {

        $output = '';
        $canmanage = $lesson->can_manage();
        $course = $lesson->courserecord;

        if ($lesson->custom && !$canmanage && (($data->gradeinfo->nquestions < $lesson->minquestions))) {
            $output .= $this->box_start('generalbox boxaligncenter');
        }

        if ($data->gradelesson) {
            // We are using level 3 header because the page title is a sub-heading of lesson title (MDL-30911).
            $output .= $this->heading(get_string("congratulations", "lesson"), 3);
            $output .= $this->box_start('generalbox boxaligncenter');
        }

        if ($data->notenoughtimespent !== false) {
            $output .= $this->paragraph(get_string("notenoughtimespent", "lesson", $data->notenoughtimespent), 'center');
        }

        if ($data->numberofpagesviewed !== false) {
            $output .= $this->paragraph(get_string("numberofpagesviewed", "lesson", $data->numberofpagesviewed), 'center');
        }
        if ($data->youshouldview !== false) {
            $output .= $this->paragraph(get_string("youshouldview", "lesson", $data->youshouldview), 'center');
        }
        if ($data->numberofcorrectanswers !== false) {
            $output .= $this->paragraph(get_string("numberofcorrectanswers", "lesson", $data->numberofcorrectanswers), 'center');
        }

        if ($data->displayscorewithessays !== false) {
            $output .= $this->box(get_string("displayscorewithessays", "lesson", $data->displayscorewithessays), 'center');
        } else if ($data->displayscorewithoutessays !== false) {
            $output .= $this->box(get_string("displayscorewithoutessays", "lesson", $data->displayscorewithoutessays), 'center');
        }

        if ($data->yourcurrentgradeisoutof !== false) {
            $output .= $this->paragraph(get_string("yourcurrentgradeisoutof", "lesson", $data->yourcurrentgradeisoutof), 'center');
        }
        if ($data->eolstudentoutoftimenoanswers !== false) {
            $output .= $this->paragraph(get_string("eolstudentoutoftimenoanswers", "lesson"));
        }
        if ($data->welldone !== false) {
            $output .= $this->paragraph(get_string("welldone", "lesson"));
        }

        if ($data->progresscompleted !== false) {
            $output .= $this->progress_bar($lesson, $data->progresscompleted);
        }

        if ($data->displayofgrade !== false) {
            $output .= $this->paragraph(get_string("displayofgrade", "lesson"), 'center');
        }

        $output .= $this->box_end(); // End of Lesson button to Continue.

        if ($data->reviewlesson !== false) {
            $output .= html_writer::link($data->reviewlesson, get_string('reviewlesson', 'lesson'), array('class' => 'centerpadded lessonbutton standardbutton p-r-1'));
        }
        if ($data->modattemptsnoteacher !== false) {
            $output .= $this->paragraph(get_string("modattemptsnoteacher", "lesson"), 'centerpadded');
        }

        if ($data->activitylink !== false) {
            $output .= $data->activitylink;
        }
/* Link to course at end of hte Lesson
        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $output .= html_writer::link($url, get_string('returnto', 'lesson', format_string($course->fullname, true)),
            array('class' => 'centerpadded lessonbutton standardbutton p-r-1')); */

        if (has_capability('gradereport/user:view', context_course::instance($course->id))
            && $course->showgrades && $lesson->grade != 0 && !$lesson->practice) {
            $url = new moodle_url('/grade/index.php', array('id' => $course->id));
            $output .= html_writer::link($url, get_string('viewgrades', 'lesson'),
                array('class' => 'centerpadded lessonbutton standardbutton p-r-1'));
        }
        return $output;
    }


}