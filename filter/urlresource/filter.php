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
 * This filter displays a image and a description substituting the
 * normal display of a url resource activity (like facebook does).
 * 
 * If you use the course format socialwall and filter is active, 
 * 
 * 1. students may select an image and enter a title for substition, when posting a link (via the posting area of the format)
 * 2. teacher may select an image and edit a title for any existing mod url.
 * 
 * @package    filter
 * @subpackage urlresource
 * @copyright  2014 Andreas Wagner, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/filter/urlresource/lib.php');

class filter_urlresource extends moodle_text_filter {

    protected static $globalconfig;

    public function filter($text, array $options = array()) {

        // Search all activity links.
        $search = '/(<a[^>]*?href="([^"]*\/mod\/url\/view.php\?id=(.*?))">)(<img[^>]*?>(.*?))<\/a>/i';
        $text = preg_replace_callback($search, array($this, 'filter_urlresource_img_callback'), $text);

        return $text;
    }

    public function filter_urlresource_img_callback($link) {
        global $OUTPUT;

        $modlink = $link[0]; // Whole module link.
        $modurl = $link[2];
        $cmid = $link[3];

        $urlresourcehelper = filter_url_resource_helper::instance($this->context->instanceid);

        if ($replacedata = $urlresourcehelper->get_filterdata($modurl)) {

            $img = '';
            if (!empty($replacedata->imgurl)) {
                $img = html_writer::empty_tag('img', array('src' => $replacedata->imgurl, 'class' => 'fur-teaserimage'));
            }

            $linktext = $link[4];
            $modlink = $img . str_replace($linktext, html_writer::tag('span', $replacedata->title), $modlink);
        }

        $params = array('url' => $modurl, 'cmid' => $cmid);
        $urlreplaceedit = new moodle_url('/filter/urlresource/pages/urlreplace.php', $params);

        if (has_capability('filter/urlresource:editallurls', $this->context)) {
            $modlink .= ' ' . html_writer::link($urlreplaceedit, $OUTPUT->pix_icon('editimage2', 'edit', 'filter_urlresource'));
        }

        return $modlink;
    }

}

