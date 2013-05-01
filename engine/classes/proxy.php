<?php
	/**
	 * Class „Proxy“ can receive and request data from one class for another class.
	 * It uses pattern „Singleton“.
	 */
	
	final class Proxy {
		private $memory = array(); // contains all previous successful requests
		private $builder;
		
		
		
		// Singleton ----------------------------------------------------
		static private $instance = NULL; // instance
		
		// create and receive instance
		static function instance() {
			if (self::$instance == NULL) {
				self::$instance = new Proxy();
			};
			
			return self::$instance;
		}
		
		// hide constructor and cloner
		private function __construct() {
			$this->builder = Builder::instance();
		}
		private function __clone() {}
		// --------------------------------------------------------------
		
		
		
		
		// public methods -----------------------------------------------
		// request information
		function request($class, $method) {
			// create and get
			$instance = $this->create($class, $method);
			$data = $instance->$method();
			
			
			// save in memory
			$this->memory[] = array(
				'class' => $instance,
				'method' => $method,
				'data' => $data
			);
			
			return $data;
		}
		
		// if class and methods are confirm the standarts, function will create an instance
		// else this function will make an error
		function create($class, $method) {
			// finding method in the list
			if (!in_array($method, $this->builder->information($class, "proxy"))) throw new Exception("Given method must be registered in database.");

			return $this->builder->build($class);
		}
		// --------------------------------------------------------------
	}
?>