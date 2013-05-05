<?php
    /**
     * Trait for forming a title of the page and saving it in the Smarty.
     *
     * @property title
     * An array for pushing elements of the title.
     */

    trait Title {
        private $title = array();




        /**
         * Converts title array into the correct string for views.
         * Saves in ‘global‘ array in Smarty.
         */

        function title() {
            $this->smarty->append(
                'template',
                array('title' => implode(" — ", array_reverse($this->title))),
                true
            );
        }
    }
?>