<?php
	class Errors extends Level {
        use Title;

        private $registry; // instance of “Registry“
        private $smarty; // instance of “Smarty“
        private $information; // information about class from Builder





        function __construct() {
            // initialize a properties
            $this->registry = Registry::instance();
            $this->smarty = $this->registry->get('smarty');
            $this->information = Builder::instance()->information(__CLASS__);
        }





        /*
         * General
         */





        /**
         * Launches Error‘s work.
         *
         * @return mixed
         * Returns a template with an error.
         */

        function launch() {
            // save global blocks in Smarty
            $this->smarty->assign(
                'global',
                array(
                    'header' => $this->smarty->fetch('global/header.html'),
                    'error' => $this->find(func_get_arg(0), func_get_arg(1)),
                    'scripts' => $this->smarty->fetch('global/scripts.html')
                )
            );


            // create a title
            $this->title();


            // return a compiled template
            return $this->smarty->fetch("{$this->information['folder']}/template.html");
		}




        /**
         * Finds an error in methods or files.
         *
         * @param $code
         * A code of the error.
         * @param $message
         * The error‘s text.
         *
         * @return mixed
         * A compiled template with the error.
         */

        function find($code, $message) {
            // set default values
            if (!$code) $code = 404;


            if (method_exists($this, $code)) return $this->$code();
            else {
                // 404 header
                header("HTTP/1.0 404 Not Found");


                // add new title element
                $this->title[] = "404 Not Found";


                // if file with error code exists, fetch him
                // else, fetch a general error file with given message and code
                if (file_exists(realpath(TEMPLATES . "/{$this->information['folder']}/$code.html"))) return $this->smarty->fetch("{$this->information['folder']}/$code.html");
                else {
                    // save in Smarty
                    $this->smarty->assign(
                        "errors",
                        array(
                            'error' => array('code' => $code, 'message' => $message)
                        )
                    );


                    // fetch a template
                    return $this->smarty->fetch("{$this->information['folder']}/general.html");
                };
            };
        }
	};
?>