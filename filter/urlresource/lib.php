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
 * @package    filter
 * @subpackage urlresource
 * @copyright  2014 Andreas Wagner, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('FILTER_URLRESOURCE_MINIMGWIDTH', 30);

class filter_url_resource_helper {

    protected $urlresources = array();
    protected $webpage = array();
    protected $curlinfo = array();

    protected function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /** create instance as a singleton */
    public static function instance($courseid) {
        static $notification;

        if (isset($urlresourcehelper)) {
            return $urlresourcehelper;
        }

        $urlresourcehelper = new filter_url_resource_helper($courseid);
        return $urlresourcehelper;
    }

    /** get (and request cache) all entries from the database table for this course
     * 
     * @global object $DB
     * @return array
     */
    protected function get_url_resources() {
        global $DB;

        $courseid = $this->courseid;

        if (isset($this->urlresources[$courseid])) {
            return $this->urlresources[$courseid];
        }

        $params = array($courseid);

        $sql = "SELECT url, fr.* FROM {filter_urlresource} fr WHERE courseid = ?";

        if (!$urlresources = $DB->get_records_sql($sql, $params)) {
            return array();
        }

        $this->urlresources[$courseid] = $urlresources;
        return $this->urlresources[$courseid];
    }

    /** get one entry of the database table using the url as a key
     *  Note that this retirieves only data for the current course. Used to get
     *  replace data for the filter.
     * 
     * @param type $url
     * @return boolean
     */
    public function get_filterdata($url) {
        $replacedata = $this->get_url_resources();
        if (!isset($replacedata[$url])) {
            return false;
        }
        return $replacedata[$url];
    }

    /** get data to setup the urlreplace edit form
     * 
     * @global object $OUTPUT
     * @param string $url, referencing url (i. e. url of module)
     * @param string $externalurl, webpage to grab title and images
     * @return \stdClass 
     */
    public function get_urlreplacedata($url, $externalurl) {
        global $OUTPUT;

        $replacedata = $this->get_url_resources();

        // There is no entry in the database so try to grab infos live.
        if (!isset($replacedata[$url])) {

            $replacedata[$url] = new stdClass();
            $replacedata[$url]->url = $url;
            $replacedata[$url]->title = $this->get_title_from_webpage($externalurl);
            $replacedata[$url]->offset = 0;

            $result = $this->get_firstimage_from_webpage($externalurl);

            if ($result['error'] == 0) {
                // ...take the first image.
                $replacedata[$url]->imgurl = $result['imgurl'];
                $replacedata[$url]->offset = $result['offset'];
            }
        } else {
            $replacedata[$url]->offset = 0;
        }

        // ... if there is choosen not to show a image or no image found, thow "noimage" - Pic.
        if (empty($replacedata[$url]->imgurl)) {
            $replacedata[$url]->imgurl = $OUTPUT->pix_url('noimage', 'filter_urlresource');
        }
        return $replacedata[$url];
    }

    /** try to retrieve the first image from the webpage
     * 
     * @global record $CFG
     * @param string $url
     * @param int $minwidth
     * @return array of result
     */
    protected function get_firstimage_from_webpage($url,
                                                   $minwidth = FILTER_URLRESOURCE_MINIMGWIDTH) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/filelib.php');

        $imgurls = $this->grab_imageurls_from_webpage($url);

        $c = new curl();

        $offset = 0;
        foreach ($imgurls as $imgurl) {
            if ($img = $c->post($imgurl, array())) {

                if ($imageinfo = getimagesizefromstring($img)) {

                    if (isset($imageinfo[0]) and ($imageinfo[0] > $minwidth)) {
                        return array('error' => 0, 'imgurl' => $imgurl, 'offset' => $offset);
                    }
                }
                $offset++;
            }
        }
        return array('error' => '1', 'message' => 'noimagefound', 'imgurl' => '');
    }

    /** get and (request cache) webpage to url */
    protected function get_webpage($url) {
        global $CFG;

        if (isset($this->webpage[$url])) {
            return $this->webpage[$url];
        }

        require_once($CFG->dirroot . '/lib/filelib.php');

        $c = new curl();
        $this->webpage[$url] = $c->post($url, array());
        $this->curlinfo = $c->info;

        return $this->webpage[$url];
    }

    /** retrieve title from webpabe
     * 
     * @param String $url, the url to retrieve the title
     * @return array|string
     */
    protected function get_title_from_webpage($url) {

        if (!$content = $this->get_webpage($url)) {
            return false;
        };
        $search = '/<title.*>(.*)<\/title>/U';
        $matches = array();
        if (preg_match($search, $content, $matches)) {
            return $matches[1];
        }
        return get_string('unknowntitle', 'filter_urlresource');
    }

    /** grab all the images from the webpage like facebook do:
     *  1: via meta property <meta property="og:image" content="[imgurl]">
     *  2: via link rel <link rel="image_src" href="[imgurl]">
     *  3: via img tag <img src="[imgurl]" ...>
     * 
     * @global type $OUTPUT
     * @param type $url
     * @return boolean|array of (full) urls to images
     */
    public function grab_imageurls_from_webpage($url) {
        global $OUTPUT, $CFG;

        if (!$content = $this->get_webpage($url)) {
            return false;
        }

        if (isset($this->imgfullurls[$url])) {
            return $this->imgfullurls[$url];
        }

        $imgurls = array();
        $matches = array();

        // ...method 1: grab images via meta property.
        $search = '/<meta[^>]*?property=(\'|")(og:image|og:url:image)(\'|")[^>]*?>/i';

        if (preg_match_all($search, $content, $matches)) {

            foreach ($matches[0] as $metatag) {
                $match = array();
                if (preg_match_all('/content=(\'|")(.*?)(\'|")/', $metatag, $match)) {
                    $imgurls = array_merge($imgurls, $match[2]);
                }
            }
        }

        $matches = array();
        // ... second method grab image via link rel.
        $search = '/<link[^>]*?rel=(\'|")(image_src)(\'|")[^>]*?>/i';

        if (preg_match_all($search, $content, $matches)) {

            foreach ($matches[0] as $metatag) {
                $match = array();
                if (preg_match_all('/href=(\'|")(.*?)(\'|")/', $metatag, $match)) {
                    $imgurls = array_merge($imgurls, $match[2]);
                }
            }
        }

        // ... grab images from content.
        $matches = array();
        $search = '/<img[^>]*?src=(\'|")(.*?)(\'|")[^>]*?>/i';

        if (preg_match_all($search, $content, $matches)) {

            $imgurls = array_merge($imgurls, $matches[2]);
        }

        // Try to find baseurl, if webpage was redirected use curl url.
        $baseurl = (!empty($this->curlinfo['url'])) ? $this->curlinfo['url'] : $url;

        // ... fix slashes and cut off subdirs.
        $parsedata = parse_url($baseurl);
        $baseurl = $parsedata['scheme'] . '://' . $parsedata['host'];
        $baseurl = trim($baseurl, '/');

        // ... fix relative urls.
        $imgfullurls = array();

        foreach ($imgurls as $key => $imgurl) {
            if (strpos($imgurl, 's.ytimg.com/yts/img/pixel-vfl3z5WfW.gif')) {
                continue;
            }
            if (strpos($imgurl, 'http') === false) {

                $imgurl = trim($imgurl, '/');
                $imgfullurls[] = $baseurl . '/' . $imgurl;
            } else {
                $imgfullurls[] = $imgurl;
            }
        }

        $imgfullurls[] = $OUTPUT->pix_url('noimage', 'filter_urlresource')->out();

        $this->imgfullurls[$url] = $imgfullurls;

        return $this->imgfullurls[$url];
    }

    public function load_loadnewimages($externalurl) {

        if (!$title = $this->get_title_from_webpage($externalurl)) {
            return array('error' => '1');
        }

        if (!$imgurls = $this->grab_imageurls_from_webpage($externalurl)) {
            return array('error' => '1');
        }

        return array('error' => '0', 'title' => $title, 'imgurls' => $imgurls);
    }

    public static function add_postformfields(&$mform, $courseid) {
        global $PAGE;

        $coursecontext = context_course::instance($courseid);

        $editurlreplace = (has_capability('filter/urlresource:loadnewimage', $coursecontext) ||
                has_capability('filter/urlresource:editallurls', $coursecontext));

        if ($editurlreplace) {

            $config = get_config('filter_urlresource');

            $imgparams = array(
                'src' => '', 'id' => 'imgpreview',
                'style' => 'max-width:' . $config->imgmaxwidth . 'px'
            );
            $content = html_writer::tag('div', html_writer::empty_tag('img', $imgparams));
            $mform->addElement('static', '', html_writer::tag('button', get_string('refreshimages', 'filter_urlresource'), array('id' => 'refreshimages')));
            
            $mform->addElement('static', 'img', get_string('image', 'filter_urlresource') .
                    html_writer::tag('button', '<', array('id' => 'previmage')) .
                    html_writer::tag('button', '>', array('id' => 'nextimage')), $content);

            $mform->addElement('hidden', 'desc', '', html_writer::tag('div', '', array('id' => 'imgsrc')));
            $mform->setType('desc', PARAM_TEXT);

            $mform->addElement('text', 'title', get_string('title', 'filter_urlresource'), array('size' => '70'));
            $mform->setType('title', PARAM_TEXT);
            $mform->setDefault('title', '');

            // URL to retrieve.
            $mform->addElement('hidden', 'imgurl', '', array('id' => 'imgurl'));
            $mform->setType('imgurl', PARAM_URL);
            $mform->setDefault('imgurl', '');

            $args = array();
            $args['imgurl'] = '';
            $args['offset'] = -1;
            $args['courseid'] = $courseid;
            $args['urlimgs'] = array();
            $args['minwidth'] = FILTER_URLRESOURCE_MINIMGWIDTH;

            $PAGE->requires->strings_for_js(array('noimgavailable'), 'filter_urlresource');
            $PAGE->requires->yui_module(
                    'moodle-filter_urlresource-urlreplaceform', 'M.filter_urlresource.urlreplaceforminit', array($args), null, true);
        }
    }

    /** save the url replace data in the database
     * 
     * @global object $DB
     * @param record $data, the submitted data.
     * @param record $course, the current course 
     * @return array result.
     */
    public function save_urlreplace($data) {
        global $DB;

        // ...check if entry for this module exists.
        $sql = "SELECT * FROM {filter_urlresource} WHERE coursemoduleid = ?";

        $urlreplace = new stdClass();
        $urlreplace->courseid = $data->courseid;
        $urlreplace->coursemoduleid = $data->cmid;
        $urlreplace->url = $data->url;
        $urlreplace->externalurl = $data->externalurl;
        $urlreplace->title = $data->title;

        if (strpos($data->imgurl, 'noimage') !== false) {
            $urlreplace->imgurl = '';
        } else {
            $urlreplace->imgurl = $data->imgurl;
        }
        $urlreplace->timecreated = time();

        if ($exists = $DB->get_record_sql($sql, array($data->cmid))) {

            if (isset($data->deletebutton)) {
                $DB->execute("DELETE FROM {filter_urlresource} WHERE id = ?", array($exists->id));
                return array('error' => 0, 'message' => 'urlreplacedeleted');
            }

            $urlreplace->id = $exists->id;
            $DB->update_record('filter_urlresource', $urlreplace);
        } else {
            $DB->insert_record('filter_urlresource', $urlreplace);
        }
        return array('error' => 0, 'message' => 'urlreplacesaved');
    }

    public static function save_externalurl($data, $cmid) {

        if (empty($data->imgurl)) {
            return false;
        }

        $urlreplace = new stdClass();
        $urlreplace->courseid = $data->courseid;
        $urlreplace->cmid = $cmid;
        $url = new moodle_url('/mod/url/view.php', array('id' => $cmid));
        $urlreplace->url = $url->out();
        $urlreplace->externalurl = $data->externalurl;
        $urlreplace->imgurl = $data->imgurl;
        $urlreplace->title = $data->title;

        $helper = self::instance($urlreplace->courseid);
        $helper->save_urlreplace($urlreplace);

        return true;
    }

    public static function clean_url($url) {
        if (strpos($url, 'youtube.com/v/') > 0) {
            $url = array_shift(explode('#', $url));
            $parts = explode('/', $url);
            $url = $parts[0] . '//youtube.com/watch?v=' . $parts[4];
        }

        return $url;
    }

}