<?php
    class Video extends Feathers implements Feather {
        public function __init() {
            $maximum = Config::current()->uploads_limit;

            $this->setField(array("attr" => "title",
                                  "type" => "text",
                                  "label" => __("Title", "video"),
                                  "optional" => true));

            $this->setField(array("attr" => "video",
                                  "type" => "file",
                                  "label" => __("Video File", "video"),
                                  "multiple" => false,
                                  "accept" => ".".implode(",.", self::video_extensions()),
                                  "note" => _f("(Max. file size: %d Megabytes)", $maximum, "video")));

            $this->setField(array("attr" => "description",
                                  "type" => "text_block",
                                  "label" => __("Description", "video"),
                                  "optional" => true,
                                  "preview" => true));

            $this->setFilter("title", array("markup_post_title", "markup_title"));
            $this->setFilter("description", array("markup_post_text", "markup_text"));

            $this->respondTo("delete_post", "delete_file");
            $this->respondTo("feed_item", "enclose_video");
            $this->respondTo("filter_post", "filter_post");
            $this->respondTo("metaWeblog_getPost", "metaWeblog_getValues");
            $this->respondTo("metaWeblog_editValues", "metaWeblog_setValues");
        }

        public function submit() {
            if (isset($_FILES['video']) and upload_tester($_FILES['video']))
                $filename = upload($_FILES['video'], self::video_extensions());

            if (!isset($filename))
                error(__("Error"), __("You did not select a video to upload.", "video"), null, 422);

            fallback($_POST['title'], "");
            fallback($_POST['description'], "");
            fallback($_POST['slug'], $_POST['title']);

            return Post::add(array("title" => $_POST['title'],
                                   "filename" => $filename,
                                   "description" => $_POST['description']));
        }

        public function update($post) {
            if (isset($_FILES['video']) and upload_tester($_FILES['video'])) {
                $this->delete_file($post);
                $filename = upload($_FILES['video'], self::video_extensions());
            } else {
                $filename = $post->filename;
            }

            fallback($_POST['title'], "");
            fallback($_POST['description'], "");

            return $post->update(array("title" => $_POST['title'],
                                       "filename" => $filename,
                                       "description" => $_POST['description']));
        }

        public function title($post) {
            return oneof($post->title, $post->title_from_excerpt());
        }

        public function excerpt($post) {
            return $post->description;
        }

        public function feed_content($post) {
            return $post->description;
        }

        public function enclose_video($post, $feed) {
            $config = Config::current();
            $filepath = uploaded($post->filename, false);

            if ($post->feather != "video" or !file_exists($filepath))
                return;

            $feed->enclosure(uploaded($post->filename),
                             filesize($filepath),
                             self::video_type($post->filename));
        }

        public function delete_file($post) {
            if ($post->feather != "video")
                return;

            $trigger = Trigger::current();
            $filepath = uploaded($post->filename, false);

            if (file_exists($filepath)) {
                $trigger->call("delete_upload", $post->filename);
                unlink($filepath);
            }
        }

        public function filter_post($post) {
            if ($post->feather != "video")
                return;

            $post->video_player = self::video_player($post);
        }

        public function metaWeblog_getValues($struct, $post) {
            if ($post->feather != "audio")
                return;

            $struct["title"] = $post->title;
            $struct["description"] = $post->description;

            return $struct;
        }

        public function metaWeblog_setValues($values, $args, $post) {
            if ($post->feather != "audio")
                return;

            $values["title"] = $args["title"];
            $values["description"] = $args["description"];

            return $values;
        }

        private function video_player($post) {
            $trigger = Trigger::current();

            if ($trigger->exists("video_player"))
                return $trigger->call("video_player", $post);

            return '<video controls>'."\n".
                   __("Your web browser does not support the <code>video</code> element.", "video")."\n".
                   '<source src="'.uploaded($post->filename).'" type="'.self::video_type($post->filename).
                   '">'."\n".'</video>'."\n";
        }

        private function video_type($filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            switch($extension) {
                case "mp4":
                    return "video/mp4";
                case "ogv":
                    return "video/ogg";
                case "webm":
                    return "video/webm";
                case "3gp":
                    return "video/3gpp";
                case "mkv":
                    return "video/x-matroska";
                case "mov":
                    return "video/quicktime";
                default:
                    return "application/octet-stream";
            }
        }

        private function video_extensions() {
            return array("mp4", "ogv", "webm", "3gp", "mkv", "mov");
        }
    }
