<?php
	/**
	 * Class „Builder“ can instance and hold information about classes.
	 * It uses pattern „Singleton“.
	 */
	
	final class Builder {
		private $classes = array(); // contains all information about classes
		private $registry;
		private $database;
		
		
		
		
		// Singleton ----------------------------------------------------
		static private $instance = NULL; // instance
		
		// create and receive instance
		static function instance() {
			if (self::$instance == NULL) {
				self::$instance = new Builder();
			};
			
			return self::$instance;
		}
		
		// hide constructor and cloner
		private function __construct() {
			$this->registry = Registry::instance();
		}
		private function __clone() {}
		// --------------------------------------------------------------





        /**
         * Loads FileMAJ and makes a Smarty code of it.
         */

        function build($class) {
            // receive information about class from DB and save it
            $name = ucfirst(strtolower($class));
            $information = $this->information($name);


            // get instance and check
            $instance = new $name();
            if (!$instance instanceof Level) throw new Exception("Given class must implement interface “Level“.");


            // also create an array in Smarty for this class
            $this->registry->get("smarty")->assign(strtolower($name), ['information' => $information]);

            return $instance;
        }


        /**
         * Returns an array with information about class.
         * If you want, it can return only one field from array with information.
         * $class — class name, $field — field name in case if you want it
         */

        function information($class, $field = false) {
            // if our history contains information about such class, return it
            $name = strtolower($class);
            if (!empty($this->classes[$name])) return $field && !empty($this->classes[$name][$field]) ? $this->classes[$name][$field] : $this->classes[$name];


            // get DB handler
            $this->database = $this->registry->get('database');


            // get information from database and check registration
            $statement = $this->database->prepare("SELECT * FROM system_classes WHERE name = :name");
            $statement->bindValue(":name", $name); $statement->execute();
            $class = $statement->fetch(PDO::FETCH_ASSOC);
            if (empty($class)) throw new Exception("Given class must be registered in database.");

			
			// methods for Proxy
			$class['proxy'] = explode(',', $class['proxy']);
			array_walk($class['proxy'], function(&$method) {
				$method = trim($method);
			});
			
			
			// methods for AJAX
			$class['ajax'] = explode(',', $class['ajax']);
			array_walk($class['ajax'], function(&$method) {
				$method = trim($method);
			});
			
			
			// name in database and in file system
			$class['table'] = "class_$name";
			$class['folder'] = $name; // relative to /templates/


            // save in Builder's history
            $this->classes[$name] = $class;

			return $field && !empty($class[$field]) ? $class[$field] : $class;
		}
	}
?>