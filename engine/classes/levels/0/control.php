<?php
	/**
	 * Control Panel
     *
     * Creates a good interface for control the system.
     * Every control panel (for every class or feature) you should write by yourself.
     * Control Panel will just load it for you.
     *
     * @author Ilya Cheremushkin
     * @version 1.0
	 */
	
	class Control extends Level {
		use Session, Title;
		
		private $registry; // instance of “Registry“
		private $builder; // instance of “Builder“
		private $smarty; // instance of “Smarty“
		private $database; // instance of database handler
        private $information; // information about class from Builder
		private $settings; // all settings
		private $url; // parsed URL
		
		
		


		function __construct() {
			// initialize a properties
			$this->registry = Registry::instance();
			$this->builder = Builder::instance();
			$this->smarty = $this->registry->get('smarty');
			$this->database = $this->registry->get('database');
            $this->information = $this->builder->information(__CLASS__);
			$this->settings = $this->registry->get('settings');
		}





        /*
         * General
         */





        /**
         * Launches Control Panel‘s work.
         *
         * @return mixed
         * Returns a template of Control Panel‘s page.
         */

        function launch() {
            // receive parsed URL
			$this->url = $this->registry->get('url');		
			
			
			// first page title
			$this->title[] = $this->settings['classes'][$this->information['name']]['titles']['home'];
			
			
			// firstly, check client's authorization
			if (!$this->permissions()) {
                // authorization title and finally implode titles
				$this->title[] = $this->settings['classes'][$this->information['name']]['titles']['authorization']; $this->title();

    			return $this->smarty->fetch("{$this->information['folder']}/login.html");
			};
			
			
			// list of classes that will be used everywhere
			$statement = $this->database->prepare("SELECT * FROM system_classes WHERE name != :name");
            $statement->bindValue(":name", $this->information['name']); $statement->execute();
            $classes = $statement->fetchAll(PDO::FETCH_ASSOC);
			$this->smarty->assign($this->information['name'], ['classes' => $classes]);
			
			
			// FileMAJ block will be used everywhere
			$this->filemaj();
			
			
			// in case of the main page we should prepare title
			if (empty($this->url[1])) {
				$this->title();
				return $this->smarty->fetch("{$this->information['folder']}/main.html");
			};
			
			
			// in other cases
			switch($this->url[1]) {
				case "informers":
					// append title and parse informers page
					$this->title[] = $this->settings['features']['informers']['titles']['home'];
					$this->smarty->append($this->information['name'], ['content' => $this->informers()], true);
					break;
				
				default: break;
			};
			
			
			// prepare title
			$this->title();
			
			
			return $this->smarty->fetch("{$this->information['folder']}/pages.html");
		}




        /**
		 * Loads FileMAJ and makes a Smarty code of it.
		 */
		
		private function filemaj() {
			$filemaj = $this->builder->build('Filemaj');
			$filemaj->init(['path' => empty($_SESSION['filemaj']['folder']) ? "/" : $_SESSION['filemaj']['folder']]);
			$this->smarty->append($this->information['name'], ['filemaj' => $filemaj->launch()], true);
		}




        /**
         * Information about client's permissions.
         *
         * @return bool
         * Return true if client has admin's permissions and false if not.
         */

        function permissions() {
			return !empty($_SESSION['admin']['logged']) && $_SESSION['admin']['logged'] === true && $this->activity() ? true : false;
		}




        /**
         * Finds if inactivity timer is ends.
         *
         * @return bool
         */

        private function activity() {
			// if inactivity time has ended, person will be logout
			if (time() - $_SESSION['admin']['time'] > $this->settings['classes'][$this->information['name']]['settings']['inactivity']) {
				unset($_SESSION['admin']);
				$this->sessionStop();
				return false;
			};
			
			// else last activity time in session will be changed to now 
			$_SESSION['admin']['time'] = time();
			return true;
		}




        /**
         * Crypts a password with Blofish algorithm.
         *
         * @param $nickname
         * @param $password
         *
         * @return string
         */

        private function blowfish($nickname, $password) {
			return crypt($password, '$2a$10$7fmnb3' . substr($nickname, 0, 6) . 'JZaAqOI6r$');
		}




        /**
         * Checks nickname and password.
         *
         * @param $nickname
         * @param $password
         *
         * @return mixed
         * Returns user ID.
         *
         * @throws Exception
         * 21 — This user does not exist.
         * 22 — You have an error in the password.
         */

        private function authentication($nickname, $password) {
			// get ID using a PDOStatement
			$statement = $this->database->prepare("SELECT id FROM {$this->information['table']} WHERE nickname = :nickname");
            $statement->bindValue(":nickname", $nickname); $statement->execute();
            $id = $statement->fetchColumn();
			if ($id) throw new Exception("This user does not exist.", 21);
			
			
			// check password using a PDOStatement
			$password = $this->blowfish($nickname, $password);
			$result = $this->database->query("SELECT * FROM {$this->information['table']} WHERE id = $id && password = '$password'");
			if (!$result->num_rows) throw new Exception("You have an error in the password.", 22);

            $statement = $this->database->prepare("SELECT COUNT(*) FROM {$this->information['table']} WHERE id = $id && password = :password");
            $statement->bindValue(":password", $password); $statement->execute();
            if ($statement->fetchColumn()) throw new Exception("This user does not exist.", 21);
			
			return $id;
		}




        /**
         * Controller for informers. Generates content for pages.
         *
         * @return mixed
         * Returns a template with requested content.
         *
         * @throws Exception
         * 404 — Not Found
         */

        private function informers() {
			if (empty($this->url[2])) {
				// all information about informers has been already received
				return $this->smarty->fetch('admin/informers/main.html');
			};
			
			
			// parse 3rd part of the URL
			$this->url[2] = empty($this->url[2]) ? NULL : $this->url[2];
			switch ($this->url[2]) {
				case 'create':
					return $this->smarty->fetch('admin/informers/create.html');
				
				default:
					// check informerv
					if (empty($this->registry->get('informers')[$this->url[2]])) throw new Exception(false, 404);
					
					
					// parse 4th part of the URL
					$this->url[3] = empty($this->url[3]) ? NULL : $this->url[3];
					switch ($this->url[3]) {
						case 'edit':
							return $this->smarty->fetch('admin/informers/edit.html');
						
						default:
							return $this->smarty->fetch('admin/informers/home.html');
					};
			};
		}





		/*
		 * AJAX
		 */





        /**
         * Transitional AJAX controller.
         * Requests a methods from other classes (made by yourself).
         *
         * @param $data
         *
         * @throws Exception
         * 100 — Data for loading in Control Panel has been given incorrectly.
         * 101 — Given class for loading in Control Panel must be registered in database.
         */

        function transit($data) {
			// class construction and method and transfer to the correct case
			$class = empty($data->load->class) ? NULL : ucfirst(strtolower($data->load->class));
			$method = empty($data->load->method) ? NULL : $data->load->method;
			if (!$class || !$method) throw new Exception("Data for loading in Control Panel has been given incorrectly.", 100);
			
			
			// check registration
			$result = $this->database->query("SELECT * FROM classes WHERE name = '$class'");
			if (!$result->num_rows) throw new Exception("Given class for loading in Control Panel must be registered in database.", 101);
			
			
			// get instance, check and receive a list of AJAX methods
			if (!(($instance = new $class()) instanceof Module)) throw new Exception("Given class must implement interface „Module“ or interface „Plugin“.");
			$methods = $instance->get('ajax');
			
			
			// finding method in list
			if (!in_array($method, $methods)) throw new Exception("Given method for load in „Admsettingsstrator Panel“ must be registered in database.");
			
			
			// launch
			$instance->$method($data);
		}
		
		
		
		/**
         * Make checks and try to log in a user.
         *
         * @param $data
         *
         * @return array
         * Data array for JSON-encoding.
         *
         * @throws Exception
         * 701 — You have already logged in control panel.
         * 702 — The wrong security code has been given.
         * 703 — Not all fields are filled.
         */

        function login($data) {
            if ($this->permissions()) throw new Exception("You have already logged in control panel.", 701);
			
			
			// check captcha
			$captcha = empty($data->captcha) || empty($_SESSION['captcha']) || $_SESSION['captcha'] != $data->captcha ? false : true;
			unset($_SESSION['captcha']);
			if (!$captcha) {
				//$this->sessionStop();
				//throw new Exception("The wrong security code has been given.", 702);
			};
			
			
			// check nickname and password
			$nickname = empty($data->nickname) ? NULL : $data->nickname;
			$password = empty($data->password) ? NULL : $data->password;
			if (!$nickname || !$password) {
				$this->sessionStop();
				throw new Exception("Not all fields are filled.", 703);
			};
			
			
			// in case of error will generate an exception
			$id = $this->authentication($nickname, $password);
			
			
			// in case of success, authenticationsuccess will return user's ID
			// else it will throw an exception
			$_SESSION['admin'] = array(
				'id' => $id,
				'logged' => true,
				'nickname' => $nickname,
				'ip' => $_SERVER['REMOTE_ADDR'],
				'time' => time()
			);
			
			return array(
				'message' => "User $nickname successfully logged in.",
				'code' => 200,
                'log' => true
			);
		}
		
		
		
		// log out from admin panel
		function logout($data) {
			if (!$this->permissions()) throw new Exception("You have not logged in.", 0);
			
			
			// success, so delete information about user
			$nickname = $_SESSION['admin']['nickname'];
			unset($_SESSION['admin']);
			
			
			return array(
				'message' => "User $nickname successfuly logged out.",
				'code' => 200
			);
		}
		// --------------------------------------------------------------
	};
?>