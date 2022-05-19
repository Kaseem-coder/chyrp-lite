<?php
    class ReadMore extends Modules {
        static function __install(): void {
            Config::current()->set("module_read_more", array("apply_to_feeds" => false));
        }

        static function __uninstall(): void {
            Config::current()->remove("module_read_more");
        }

        public function __init(): void {
            # Truncate in "markup_post_text" before Markdown filtering in "markup_text".
            $this->setPriority("markup_post_text", 1);
        }

        public function admin_read_more_settings($admin): void {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"),
                         __("You do not have sufficient privileges to change settings."));
    
            if (empty($_POST)) {
                $admin->display("pages".DIR."read_more_settings");
                return;
            }
    
            if (!isset($_POST['hash']) or !authenticate($_POST['hash']))
                show_403(__("Access Denied"), __("Invalid authentication token."));
    
            Config::current()->set("module_read_more",
                                   array("apply_to_feeds" => isset($_POST['apply_to_feeds'])));

            Flash::notice(__("Settings updated."), "read_more_settings");
        }

        public function settings_nav($navs): array {
            if (Visitor::current()->group->can("change_settings"))
                $navs["read_more_settings"] = array("title" => __("Read More", "read_more"));

            return $navs;
        }

        public function markup_post_text($text, $post = null): string {
            if (!is_string($text) or !preg_match("/<!-- *more([^>]*)?-->/i", $text, $matches))
                return $text;

            $route = Route::current();
            $array = Config::current()->module_read_more;

            if (!isset($post) or !$this->eligible())
                return preg_replace("/<!-- *more([^>]*)?-->/i", "", $text);

            $more = oneof(trim(fallback($matches[1])), __("&hellip;more", "read_more"));
            $url = (!$post->no_results) ? $post->url() : "#" ;
            $split = preg_split("/<!-- *more([^>]*)?-->/i", $text, -1, PREG_SPLIT_NO_EMPTY);

            return $split[0].'<a class="read_more" href="'.$url.'">'.fix($more).'</a>';
        }

        public function title_from_excerpt($text): string {
            $split = preg_split('/<a class="read_more"/', $text);
            return $split[0];
        }

        private function eligible(): bool {
            $route = Route::current();
            $array = Config::current()->module_read_more;

            if (!isset($route))
                return false;

            if (!$route->controller instanceof MainController)
                return false;

            if ($route->action == "view")
                return false;

            if ($route->controller->feed and !$array["apply_to_feeds"])
                return false;

            return true;
        }
    }
