<?php
    require_once "model".DIR."Pingback.php";

    class Pingable extends Modules {
        # Array: $caches
        # Query caches for methods.
        private $caches = array();

        static function __install(): void {
            Pingback::install();

            Group::add_permission("edit_pingback", "Edit Pingbacks");
            Group::add_permission("delete_pingback", "Delete Pingbacks");
        }

        static function __uninstall($confirm): void {
            if ($confirm)
                Pingback::uninstall();

            Group::remove_permission("edit_pingback");
            Group::remove_permission("delete_pingback");
        }

        public function list_permissions($names = array()): array {
            $names["edit_pingback"] = __("Edit Pingbacks", "pingable");
            $names["delete_pingback"] = __("Delete Pingbacks", "pingable");
            return $names;
        }

        public function pingback($post, $to, $from, $title, $excerpt): string|IXR_Error {
            $count = SQL::current()->count(
                tables:"pingbacks",
                conds:array(
                    "post_id" => $post->id,
                    "source" => $from
                )
            );

            if (!empty($count))
                return new IXR_Error(
                    48,
                    __("A ping from your URL is already registered.", "pingable")
                );

            if (strlen($from) > 2048)
                return new IXR_Error(
                    0,
                    __("Your URL is too long to be stored in our database.", "pingable")
                );

            Pingback::add(post_id:$post->id, source:$from, title:$title);

            return __("Pingback registered!", "pingable");
        }

        public function webmention($post, $from, $to): void {
            $count = SQL::current()->count(
                tables:"pingbacks",
                conds:array(
                    "post_id" => $post->id,
                    "source" => $from
                )
            );

            if (!empty($count))
                error(
                    __("Error"),
                    __("A ping from your URL is already registered.", "pingable"),
                    code:422
                );

            if (strlen($from) > 2048)
                error(
                    __("Error"),
                    __("Your URL is too long to be stored in our database.", "pingable"),
                    code:413
                );

            Pingback::add(
                post_id:$post->id,
                source:$from,
                title:preg_replace("~(https?://|^)([^/:]+).*~", "$2", $from)
            );
        }

        public function admin_edit_pingback($admin): void {
            if (empty($_GET['id']) or !is_numeric($_GET['id']))
                error(
                    __("No ID Specified"),
                    __("An ID is required to edit a pingback.", "pingable"),
                    code:400
                );

            $pingback = new Pingback($_GET['id']);

            if ($pingback->no_results)
                Flash::warning(
                    __("Pingback not found.", "pingable"),
                    "manage_pingbacks"
                );

            if (!$pingback->editable())
                show_403(
                    __("Access Denied"),
                    __("You do not have sufficient privileges to edit this pingback.", "pingable")
                );

            $admin->display(
                "pages".DIR."edit_pingback",
                array("pingback" => $pingback)
            );
        }

        public function admin_update_pingback($admin)/*: never */{
            if (!isset($_POST['hash']) or !authenticate($_POST['hash']))
                show_403(
                    __("Access Denied"),
                    __("Invalid authentication token.")
                );

            if (empty($_POST['id']) or !is_numeric($_POST['id']))
                error(
                    __("No ID Specified"),
                    __("An ID is required to update a pingback.", "pingable"),
                    code:400
                );

            if (empty($_POST['title']))
                error(
                    __("No Title Specified", "pingable"),
                    __("A title is required to update a pingback.", "pingable"),
                    code:400
                );

            $pingback = new Pingback($_POST['id']);

            if ($pingback->no_results)
                show_404(
                    __("Not Found"),
                    __("Pingback not found.", "pingable")
                );

            if (!$pingback->editable())
                show_403(
                    __("Access Denied"),
                    __("You do not have sufficient privileges to edit this pingback.", "pingable")
                );

            $pingback = $pingback->update($_POST['title']);

            Flash::notice(
                __("Pingback updated.", "pingable"),
                "manage_pingbacks"
            );
        }

        public function admin_delete_pingback($admin): void {
            if (empty($_GET['id']) or !is_numeric($_GET['id']))
                error(
                    __("No ID Specified"),
                    __("An ID is required to delete a pingback.", "pingable"),
                    code:400
                );

            $pingback = new Pingback($_GET['id']);

            if ($pingback->no_results)
                Flash::warning(
                    __("Pingback not found.", "pingable"),
                    "manage_pingbacks"
                );

            if (!$pingback->deletable())
                show_403(
                    __("Access Denied"),
                    __("You do not have sufficient privileges to delete this pingback.", "pingable")
                );

            $admin->display(
                "pages".DIR."delete_pingback",
                array("pingback" => $pingback)
            );
        }

        public function admin_destroy_pingback()/*: never */{
            if (!isset($_POST['hash']) or !authenticate($_POST['hash']))
                show_403(
                    __("Access Denied"),
                    __("Invalid authentication token.")
                );

            if (empty($_POST['id']) or !is_numeric($_POST['id']))
                error(
                    __("No ID Specified"),
                    __("An ID is required to delete a pingback.", "pingable"),
                    code:400
                );

            if (!isset($_POST['destroy']) or $_POST['destroy'] != "indubitably")
                redirect("manage_pingbacks");

            $pingback = new Pingback($_POST['id']);

            if ($pingback->no_results)
                show_404(
                    __("Not Found"),
                    __("Pingback not found.", "pingable")
                );

            if (!$pingback->deletable())
                show_403(
                    __("Access Denied"),
                    __("You do not have sufficient privileges to delete this pingback.", "pingable")
                );

            Pingback::delete($pingback->id);

            Flash::notice(
                __("Pingback deleted.", "pingable"),
                "manage_pingbacks"
            );
        }

        public function admin_manage_pingbacks($admin): void {
            if (!Visitor::current()->group->can("edit_pingback", "delete_pingback"))
                show_403(
                    __("Access Denied"),
                    __("You do not have sufficient privileges to manage pingbacks.", "pingable")
                );

            # Redirect searches to a clean URL or dirty GET depending on configuration.
            if (isset($_POST['query']))
                redirect(
                    "manage_pingbacks/query/".
                    str_ireplace("%2F", "", urlencode($_POST['query'])).
                    "/"
                );

            fallback($_GET['query'], "");
            list($where, $params) = keywords(
                $_GET['query'],
                "title LIKE :query",
                "pingbacks"
            );

            $admin->display(
                "pages".DIR."manage_pingbacks",
                array(
                    "pingbacks" => new Paginator(
                        Pingback::find(
                            array(
                                "placeholders" => true,
                                "where" => $where,
                                "params" => $params
                            )
                        ),
                        $admin->post_limit
                    )
                )
            );
        }

        public function manage_nav($navs): array {
            if (Visitor::current()->group->can("edit_pingback", "delete_pingback"))
                $navs["manage_pingbacks"] = array(
                    "title" => __("Pingbacks", "pingable"),
                    "selected" => array(
                        "edit_pingback",
                        "delete_pingback"
                    )
                );

            return $navs;
        }

        public function admin_determine_action($action): ?string {
            $visitor = Visitor::current();

            if ($action == "manage" and $visitor->group->can("edit_pingback", "delete_pingback"))
                return "manage_pingbacks";

            return null;
        }

        public function manage_posts_column_header(): void {
            echo '<th class="post_pingbacks value">'.
                 __("Pingbacks", "pingable").
                 '</th>';
        }

        public function manage_posts_column($post): void {
            echo '<td class="post_pingbacks value"><a href="'.
                 $post->url().
                 '#pingbacks">'.
                 $post->pingback_count.
                 '</a></td>';
        }

        public function post($post): void {
            $post->has_many[] = "pingbacks";
        }

        static function delete_post($post): void {
            SQL::current()->delete(
                table:"pingbacks",
                conds:array("post_id" => $post->id)
            );
        }

        private function get_post_pingback_count($post_id): int {
            if (!isset($this->caches["post_pingback_counts"])) {
                $counts = SQL::current()->select(
                    tables:"pingbacks",
                    fields:array("COUNT(post_id) AS total", "post_id AS post_id"),
                    group:"post_id"
                )->fetchAll();

                $this->caches["post_pingback_counts"] = array();

                foreach ($counts as $count) {
                    $id = $count["post_id"];
                    $total = (int) $count["total"];
                    $this->caches["post_pingback_counts"][$id] = $total;
                }
            }

            return fallback($this->caches["post_pingback_counts"][$post_id], 0);
        }

        public function post_pingback_count_attr($attr, $post): int {
            if ($post->no_results)
                return 0;

            return $this->get_post_pingback_count($post->id);
        }

        public function import_chyrp_post($entry, $post): void {
            $chyrp = $entry->children(
                "http://chyrp.net/export/1.0/"
            );

            if (!isset($chyrp->pingback))
                return;

            foreach ($chyrp->pingback as $pingback) {
                $title = $pingback->children(
                    "http://www.w3.org/2005/Atom"
                )->title;
                $source = $pingback->children(
                    "http://www.w3.org/2005/Atom"
                )->link["href"];
                $created_at = $pingback->children(
                    "http://www.w3.org/2005/Atom"
                )->published;

                Pingback::add(
                    post_id:$post->id,
                    source:unfix((string) $source),
                    title:unfix((string) $title),
                    created_at:datetime((string) $created_at)
                );
            }
        }

        public function posts_export($atom, $post): string {
            $pingbacks = SQL::current()->select(
                tables:"pingbacks",
                conds:array("post_id" => $post->id)
            )->fetchAll();

            foreach ($pingbacks as $pingback) {
                $atom.= '<chyrp:pingback>'."\n".
                    '<title type="html">'.
                    fix($pingback["title"], false, true).
                    '</title>'."\n".
                    '<link rel="via" href="'.
                    fix($pingback["source"], true).
                    '" />'."\n".
                    '<published>'.
                    when("c", $pingback["created_at"]).
                    '</published>'."\n".
                    '</chyrp:pingback>'."\n";
            }

            return $atom;
        }
    }
