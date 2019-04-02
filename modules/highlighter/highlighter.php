<?php
    class Highlighter extends Modules {
        public function scripts($scripts) {
            $scripts[] = Config::current()->chyrp_url."/modules/highlighter/highlight.min.js";
            return $scripts;
        }

        public function javascript() {
            include MODULES_DIR.DIR."highlighter".DIR."javascript.php";
        }

        public function stylesheets($stylesheets) {
            $stylesheets[] = Config::current()->chyrp_url."/modules/highlighter/highlight.min.css";
            return $stylesheets;
        }
    }
