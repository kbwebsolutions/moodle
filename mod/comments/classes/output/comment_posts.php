<?php


namespace mod_comments\output;


use renderer_base;
use renderable;
use templatable;

class comment_posts implements renderable, templatable {

    /**
     * @var array
     */
    protected $comments;

    /**
     * comments_posts constructor.
     * @param array $posts
     */
    public function __construct(array $posts = []) {
        global $DB;

        $this->comments = [];

        foreach ($posts as $key => $message) {
            $this->comments[] = $message;
        }
    }


    public function export_for_template(renderer_base $output)
    {
        global $DB, $USER;

        $data = ['messages' => []];
        foreach ($this->comments as $key => $comment) {
            $data['messages'][$key] = $comment;
            $user = $DB->get_record('user', array('id' => $comment->userid));
            $userpix = $output->user_picture($user);
            $data['messages'][$key]->userpix = $userpix;
            if ($USER->id == $comment->userid) {
                $data['messages'][$key]->delete = "delete";
            }




        }

        return $data;
    }

}