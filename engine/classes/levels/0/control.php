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
				$this->title[] = $this->settings['classes'][$this->information['name']]['titles']['authorization'];
                $this->title();

                return $this->smarty->fetch("{$this->information['folder']}/login.html");
			};


            // save blocks in Smarty
            $this->smarty->assign("template", array('content' => $this->content()));


			// prepare title
			$this->title();
			
			return $this->smarty->fetch("{$this->information['folder']}/page.html");
		}




        /**
         * Parses the page and generates the content.
         */

        private function content() {
            // create list of classes in Smarty
            $this->smarty->append($this->information['name'], ['classes' => $this->classes()], true);


            // FileMAJ block will be used everywhere
            //$this->filemaj();


            // in case of the home page we should prepare the title
            if (empty($this->url[1])) return $this->smarty->fetch("{$this->information['folder']}/content/home.html");


            // in other cases
            switch($this->url[1]) {
                case "informers":
                    // append title and parse informers page
                    $this->title[] = $this->settings['features']['informers']['titles']['home'];
                    return $this->informers();
                    break;

                default: break;
            };
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
         * Finds information about the user from database.
         *
         * @param $id
         * @return array
         */

        function user($id) {
            $statement = $this->database->prepare("
                SELECT id, nickname, email, counter, timer
                FROM {$this->information['table']}
                WHERE id = :id
            ");
            $statement->bindValue(":id", $id); $statement->execute();

            return $statement->fetch(PDO::FETCH_ASSOC);
        }




        /**
         * Information about client's permissions.
         *
         * @return bool
         * Return true if client has admin's permissions and false if not.
         */

        function permissions() {
            return !empty($_SESSION['control']['logged']) && $_SESSION['control']['logged'] === true && $this->activity() ? true : false;
        }




        /**
         * Finds if inactivity timer is ends.
         *
         * @return bool
         */

        private function activity() {
			// if inactivity time has ended, person will be logout
			if (time() - $_SESSION['control']['time'] > $this->settings['classes'][$this->information['name']]['security']['inactivity']) {
				unset($_SESSION['control']);
				$this->sessionStop();
				return false;
			};
			
			// else last activity time in session will be changed to now 
			$_SESSION['control']['time'] = time();
			return true;
		}




        /**
         * Crypts a password with Blowfish algorithm.
         *
         * @param $id
         * @param $password
         *
         * @return string
         */

        private function blowfish($id, $password) {
			return crypt($password, '$2a$10$7fmnbAs5Bap3' . substr($id, 0, 2) . 'JZaAqOI6r$');
		}




        /**
         * Finds all classes from database except this one.
         *
         * @return array
         * Assoc array with classes.
         */

        private function classes() {
            // prepare PDOStatement, bind, execute
            $statement = $this->database->prepare("SELECT * FROM system_classes WHERE name != :name && level != 0");
            $statement->bindValue(":name", $this->information['name']); $statement->execute();

            while ($class = $statement->fetchAll(PDO::FETCH_ASSOC)) {

            };
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
					// check informers
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
         * 704 — You have an error in your email syntax.
         * 705 — This user does not exist.
         * 706 — Entrance into this account is blocked now.
         *
         * @other
         * 707 — You have an error in the password.
         */

        function login($data) {
            if ($this->permissions()) throw new Exception("You have already logged in control panel.", 701);
			
			
			// check captcha
			$captcha = empty($data->captcha) || empty($_SESSION['captcha'][$data->captcha->id]) || $_SESSION['captcha'][$data->captcha->id] != $data->captcha->value ? false : true;
			unset($_SESSION['captcha'][$data->captcha->id]);
			if (!$captcha) {
				$this->sessionStop();
				throw new Exception("The wrong security code has been given.", 702);
			};
			
			
			// check email and password
			$email = empty($data->email) ? NULL : $data->email;
			$password = empty($data->password) ? NULL : $data->password;
			if (!$email || !$password) {
				$this->sessionStop();
				throw new Exception("Not all fields are filled.", 703);
			};

            if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email)) {
                $this->sessionStop();
                throw new Exception("You have an error in your email syntax.", 704);
            };
			
			
			// authentication
            $statement = $this->database->prepare("
			    SELECT id, counter, timer
			    FROM {$this->information['table']}
			    WHERE email = :email
			");
            $statement->bindValue(":email", $email); $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$user['id']) throw new Exception("This user does not exist.", 705);


            // check if entrance is not available
            if (!$user['counter'] && strtotime($user['timer']) > time()) throw new Exception("Entrance into this account is blocked now.", 706);


            // check password using a PDOStatement and if the password do not match, decrement the counter of tries
            $password = $this->blowfish($user['id'], $password);
            $statement = $this->database->prepare("
                SELECT COUNT(*)
                FROM {$this->information['table']}
                WHERE id = :id && password = :password
            ");
            $statement->bindValue(":id", $user['id']); $statement->bindValue(":password", $password); $statement->execute();
            if (!$statement->fetchColumn()) {
                // change the counter and the timer in case of the timer is end
                // or change only the timer in case if it is the first mistake
                if (strtotime($user['timer']) < time() || $user['counter'] == $this->settings['classes'][$this->information['name']]['security']['counter']) {
                    if (strtotime($user['timer']) < time()) $user['counter'] = $this->settings['classes'][$this->information['name']]['security']['counter'];
                    $user['timer'] = date('Y-m-d H:i:s', time() + $this->settings['classes'][$this->information['name']]['security']['timer']);
                };


                // make changes
                $statement = $this->database->prepare("
                    UPDATE {$this->information['table']}
                    SET counter = :counter, timer = :timer
                    WHERE id = {$user['id']}
                ");
                $statement->execute(
                    array(
                        ':counter' => --$user['counter'],
                        ':timer' => $user['timer']
                    )
                );

                return array(
                    'message' => "You have an error in the password.",
                    'code' => 707,
                    'counter' => $user['counter']
                );
            };


            // get information about the user
			$user = $this->user($user['id']);
			
			
			// set information about this client in the session
			$_SESSION['control'] = array(
				'id' => $user['id'],
                'nickname' => $user['nickname'],
				'logged' => true,
				'ip' => $_SERVER['REMOTE_ADDR'],
				'time' => time()
			);


            // restore the counter and the timer
            $statement = $this->database->prepare("
                UPDATE {$this->information['table']}
                SET counter = :counter, timer = :timer
                WHERE id = {$user['id']}
            ");
            $statement->execute(
                array(
                    ':counter' => $this->settings['classes'][$this->information['name']]['security']['counter'],
                    ':timer' => date('Y-m-d H:i:s', time() + $this->settings['classes'][$this->information['name']]['security']['timer'])
                )
            );

			return array(
				'message' => "User successfully logged in. Nickname: {$user['nickname']}.",
				'code' => 200,
                'log' => true
			);
		}



		/**
         * Logs out the user from the system.
         *
         * @param $data
         *
         * @return array
         * Data array for JSON-encoding.
         *
         * @throws Exception
         * 705 — You have not logged in.
         */

        function logout($data) {
			if (!$this->permissions()) throw new Exception("You have not logged in.", 705);
			
			
			// delete information about the user
			$nickname = $_SESSION['control']['nickname'];
			unset($_SESSION['control']);
			
			
			return array(
				'message' => "User successfully logged out. Nickname: $nickname.",
				'code' => 200
			);
		}
	};
?>