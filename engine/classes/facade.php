<?php
	/**
	 * Facade
     *
	 * Initializes all main points in system.
     * Loads traits, informers and launches a working class.
     *
     * Class protected from recreating or copying by using pattern “Singleton“.
     * Also, every method protected from recalling by fatal errors.
	 */
	
	final class Facade {
		private $registry; // instance of “Registry“
		private $builder;  // instance of “Builder“
		private $smarty;  // instance of “Smarty“
		private $database;  // instance of database handler
		private $url;  // parsed URL
        private $settings = array(); // parsed settings from DB




		function __construct() {
			static $created = false;
			if ($created) $this->errors('create');
			$created = true;
		
			
			$this->registry = Registry::instance();
            $this->builder = Builder::instance();
		}
		private function __clone() {}




        /**
         * Creates database handler.
         *
         * Using PDO functional.
         * Default characterset is UTF-8.
         */

        function database() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;
			
			
			// load settings, make a connection and set a charset
			include(ROOT . '/database.php');
			$database = new PDO("mysql:host=$host;dbname=$database", $user, $password);
            $database->query("SET NAMES UTF8");


            // error mode
            $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);


            // save
			$this->database = $database;
			$this->registry->set('database', $database, 'protected');
		}




		/**
         * Creates Smarty instance.
		 */

        function smarty() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;


			// установка пути по умолчанию до Smarty
			define('SMARTY_DIR', ROOT . '/engine/smarty/libs/');

			// подключение класса Smarty
			include(SMARTY_DIR . 'Smarty.class.php');

			// новый экземпляр объекта Smarty
			$smarty = new Smarty();

			// установка дерикторий Smarty
			$smarty->template_dir = realpath(TEMPLATES);
			$smarty->compile_dir = realpath(ENGINE . '/smarty/compile/');
			$smarty->config_dir = realpath(ENGINE . '/smarty/configs/');
			$smarty->cache_dir = realpath(ENGINE . '/smarty/cache/');

			// сохранение
			$this->smarty = $smarty;
			$this->registry->set('smarty', $smarty, 'protected');
		}




        /**
         * Loads namespaces which are registered in DB.
         */

        function namespaces() {
            static $used = false;
            if ($used) $this->errors('use');
            $used = true;


            $result = $this->database->query("SELECT name FROM system_namespaces ORDER BY id");

            while ($namespace = $result->fetchColumn()) {
                if (file_exists(ENGINE . "/namespaces/$namespace.php")) include(ENGINE . "/namespaces/$namespace.php");
            };
        }




        /**
         * Loads traits which are registered in DB.
         */

        function traits() {
            static $used = false;
            if ($used) $this->errors('use');
            $used = true;


            $result = $this->database->query("SELECT name FROM system_traits ORDER BY id");

            while ($trait = $result->fetchColumn()) {
                if (file_exists(ENGINE . "/traits/$trait.php")) include(ENGINE . "/traits/$trait.php");
            };
        }




        /**
         * Launches a session if a session cookie was created.
         */

        function session() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;
			
			
			if (!empty($_COOKIE[session_name()])) session_start();
		}
		
		
		

        /**
         * Prepare main Smarty codes.
         */

		function codes() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;
			
			
			$this->smarty->assign(
				array(
					'uri' => $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
					'url' => $_SERVER['SERVER_NAME']
				)
			);
		}
		
		


		/**
		 * Parse and decode URL into an array.
		 */

		function url() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;

			
			// divide URL from ?... и #... and split it into an array
			$url = preg_split('/[?#]/', $_SERVER['REQUEST_URI']); $url = $url[0];
			$url = preg_split('/\//', $url, NULL, PREG_SPLIT_NO_EMPTY);


            // do-while block for extra-break
            do {
                // if this is a home page
                if (!$url) {
                    $url = array('main');

                    $this->url = $url;
                    $this->registry->set('url', $url, 'protected');

                    break;
                };


                // if there is something else after “/“
                array_walk($url, function(&$element) {
                    $element = strtolower(urldecode($element));
                });


                // save in the property and Registy
                $this->url = $url;
                $this->registry->set('url', $url, 'protected');
            } while (false);
		}




        /**
         * Loads informers from DB.
         */

        function informers() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;
			
			
			// receive a PDOStatement object with data
			$result = $this->database->query("SELECT * FROM feature_informers ORDER BY id");
			
			// final array for informers
			$informers = array();


			// a loop for every informer from DB
			while ($information = $result->fetch(PDO::FETCH_ASSOC)) {
				$informer = array(
                    'name' => array(
                        'low' => strtolower($information['name']),
                        'ucfirst' => ucfirst(strtolower($information['name']))
                    ),
                    'description' => $information['description']
				);


				// load a script
				include(realpath(ENGINE . "/informers/{$informer['name']['low']}/script.php"));


				$instance = new $informer['name']['ucfirst'];
				$informer['code'] = $instance->launch();


				$informers[$informer['name']['low']] = $informer;
			};


            // save
			$this->smarty->assign('informers', $informers);
			$this->registry->set('informers', $informers, 'protected');
		}




        /**
         * Loads initialization setting from DB on each class and feature.
         */

        function settings() {
            // array that contains all .ini-settings
            $settings = array(
                'classes' => array(),
                'features' => array()
            );


            // there will be a lot of PDOStatements
            $statements = array();


            // get all classes
            $statements['classes'] = $this->database->query("
                SELECT id, name
                FROM system_classes
                ORDER BY id
            ");
            $classes = $statements['classes']->fetchAll(PDO::FETCH_ASSOC);


            // prepare PDOStatement with sections
            $statements['sections'] = $this->database->prepare("
                SELECT DISTINCT section
                FROM system_classes_settings
                WHERE class = :class
            ");


            // do a loop for create an array
            foreach ($classes as $class) {
                // create an array for this class
                $settings['classes'][$class['name']] = array();


                // bind a value, execute and prepare a PDOStatement for settings
                $statements['sections']->bindValue(":class", $class['id']); $statements['sections']->execute();
                $statements['settings'] = $this->database->prepare("
                    SELECT field, value
                    FROM system_classes_settings
                    WHERE class = :class AND section = :section
                "); $statements['settings']->bindValue(":class", $class['id']);


                while ($section = $statements['sections']->fetchColumn()) {
                    // prepare an array for values
                    $settings['classes'][$class['name']][$section] = array();


                    // bind value, execute and fill an array using a loop
                    $statements['settings']->bindValue(":section", $section); $statements['settings']->execute();

                    while ($setting = $statements['settings']->fetch(PDO::FETCH_ASSOC)) {
                        $settings['classes'][$class['name']][$section][$setting['field']] = $setting['value'];
                    };
                };
            };



            // get all features
            $statements['features'] = $this->database->query("
                SELECT id, name
                FROM system_features
                ORDER BY id
            ");
            $features = $statements['features']->fetchAll(PDO::FETCH_ASSOC);


            // prepare PDOStatement with sections
            $statements['sections'] = $this->database->prepare("
                SELECT DISTINCT section
                FROM system_features_settings
                WHERE feature = :feature
            ");


            // do a loop for create an array
            foreach ($features as $feature) {
                // create an array for this feature
                $settings['features'][$feature['name']] = array();


                // bind a value, execute and prepare a PDOStatement for settings
                $statements['sections']->bindValue(":feature", $feature['id']); $statements['sections']->execute();
                $statements['settings'] = $this->database->prepare("
                    SELECT field, value
                    FROM system_features_settings
                    WHERE feature = :feature AND section = :section
                "); $statements['settings']->bindValue(":feature", $feature['id']);


                while ($section = $statements['sections']->fetchColumn()) {
                    // prepare an array for values
                    $settings['features'][$feature['name']][$section] = array();


                    // bind value, execute and fill an array using a loop
                    $statements['settings']->bindValue(":section", $section); $statements['settings']->execute();

                    while ($setting = $statements['settings']->fetch(PDO::FETCH_ASSOC)) {
                        $settings['features'][$feature['name']][$section][$setting['field']] = $setting['value'];
                    };
                };
            };


            // save
            $this->settings = $settings;
            $this->smarty->assign('settings', $settings);
            $this->registry->set('settings', $settings, 'protected');
        }
		
		
		
		/**
         * Controller for index.php.
         */

		function index() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;

			
			// there are all pages keeping in DB like '/blog' or '/news'
            // and everyone has its own class that is kep in the next field
			$statement = $this->database->prepare("
			    SELECT system_classes.name FROM system_classes, system_urls
			    WHERE system_urls.url = :url AND system_classes.id = system_urls.class AND (system_classes.level = 0 OR system_classes
			    .level = 1)
			");
            $statement->bindValue(":url", $this->url[0]); $statement->execute();
			$class = ucfirst(strtolower($statement->fetchColumn()));


			// launch (with exception handler)
			try {
                // in case of undefined class
                if (!$class) throw new Exception(false, 404);


                // get an instance and information about module
                $instance = $this->builder->build($class);

                // receive a template
				$template = $instance->launch();
			} catch (Exception $exception) {
				$instance = $this->builder->build('Errors');
				$template = $instance->launch($exception->getCode(), $exception->getMessage());
			};
			
			
			echo $template;
		}



        /**
         * Controller for ajax.php.
         */

		function ajax() {
			static $used = false;
			if ($used) $this->errors('use');
			$used = true;
			
			
			// JSON decoding
			$data = json_decode($_POST['data']);
			
			
			// initialization a class and a method
			$data->class = empty($data->class) ? NULL : ucfirst(strtolower($data->class));
			$data->method = empty($data->method) ? NULL : lcfirst($data->method);
			if (!$data->class || !$data->method)
                exit(
                    json_encode(
                        array(
                            'message' => "Data has been given incorrectly.",
                            'code' => "1001"
                        )
                    )
                );
			
			
			// build an instance
			$instance = $this->builder->build($data->class);
	
			
			// finding method in list
			$methods = $this->builder->information($data->class)['ajax'];
			if (!in_array($data->method, $methods))
                exit(
                    json_encode(
                        array(
                            'message' => "Given method must be registered in the database.",
                            'code' => "1002"
                        )
                    )
                );

			
			// launch (with exception handler)
			try {
                $method = $data->method;
				$answer = $instance->$method($data);
			} catch (Exception $exception) {
				$answer = array(
					'message' => $exception->getMessage(),
					'code' => $exception->getCode(),
                    'log' => false
				);
			};
			
			// write a log and delete an index
			if ($answer['log']) {
                $statement = $this->database->prepare("
			        INSERT INTO system_logs (place, code, message)
			        VALUES (:place, :code, :message)
			    ");
                $statement->bindValue(":place", "{$data->class}::{$data->method}");
                $statement->bindValue(":code", $answer['code']);
                $statement->bindValue(":message", $answer['message']);
                $statement->execute();
            };
            unset($answer['log']);


			exit(json_encode($answer));
		}

		
		
		
		
		/**
         * Fatal errors for methods.
         */

		private function errors($type) {
			$call = debug_backtrace()[1];
			
			$errors = array(
				'create' => "instance of class <i>" . __CLASS__ . "</i> has already been created and you can`t do it again",
				'use' => "method <i>" . $call['class'] . "::" . $call['function'] . "()</i> has already been used, called",
			);
			
			trigger_error($errors[$type] . " in <b>" . $call['file'] . "</b> on line <b>" . $call['line'] . "</b>, defined", E_USER_ERROR);
		}
		// --------------------------------------------------------------
    };
?>