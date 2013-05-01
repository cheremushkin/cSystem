<?php
	/**
	 * Модуль, отвечающий за работу с клиентом и пользователем.
	 * Клиент — компьютер, который в данный момент работает с сервером.
	 * Пользователь — зарегистрированный член сайта.
     */
	
	class Users implements Module {
		use Information, Session;
		
		private $registry; // реестр данных
		private $smarty; // инстанс Smarty
		private $mysqli; // инстанс MySQLi
		private $client; // информация о клиенте
		
		
		
		
		function __construct() {
			// инициализация свойств
			$this->registry = Registry::instance();
			$this->smarty = $this->registry->get('smarty');
			$this->mysqli = $this->registry->get('mysqli');
			
			$this->set();
		}
		
		
		
		
		// запуск работы и получение шаблона ---------------------------
		// т.к. модуль не используется полностью,
		// то эта функция пуста, но когда-нибудь...
		function launch() {}
		// --------------------------------------------------------------
		
		
		
		
		// основные функции работы модуля -------------------------------
		// получение информации о конкретном пользователе
		function user($id) {
			// получение безопасного ID пользователя
			$user['id'] = Math::id($id);

			// формирование запроса
			$result = $this->mysqli->query("SELECT * FROM " . $this->information['table'] . " WHERE id = $user[id]");
			$rows = $result->num_rows;

			// если нашелся пользователь
			if ($rows) {
				// получение массива
				$user = $result->fetch_assoc();
				
				// формирование полного адреса аватара
				$user['avatar'] = "/images/avatars/" . $user['id'] . "/" . $user['avatar'];
				
				// настройка аватара с использование Gravatar
				if (!file_exists(realpath(ROOT . $user['avatar']))) {
					$user['avatar'] = '/imgs/avatar.jpg'; // Engine::gravatar('no', 32);
				};
			} else {
				// создание стандартной информации о госте-пользователе
				$user = array(
					'id' => 0,
					'login' => 'Anonymous',
					'post' => 'guest',
					'ip' => $_SERVER['REMOTE_ADDR']
				);
			}
			
			return $user;
		}
		
		// получение информации о клиенте
		function client() {
			// получение ID клиента, если он установлен
			$client = array(
				'id' => isset($_SESSION['user']['id']) ? Math::id($_SESSION['user']['id']) : NULL
			);

			// получение информации о пользователе
			$client = $this->user($client['id']);

			// если в Session установлен ID — значит
			// этот клиент, возможно, пользователь
			if ($client['id']) {
				// получение IP пользователя в данный момент
				$client['dip'] = $_SERVER['REMOTE_ADDR'];
				
				// получение hash из session
				$client['shash'] = isset($_SESSION['user']['hash']) ? $_SESSION['user']['hash'] : NULL;

				// если hash и IP из Session и hash и IP
				// из базы данных совпадают — значит,
				// клиент является пользователем
				if ($client['shash'] == $client['hash'] && $client['dip'] == $client['ip']) {
					// настройка кодов Smarty
					$this->smarty->assign(
						'client',
						array(
							'id' => $client['id'],
							'login' => $client['login'],
							'email' => $client['email'],
							'sex' => $client['sex'],
							'post' => $client['post'],
							'avatar' => $client['avatar'],
							'status' => $client['status'],
							'ip' => $client['ip']
						)
					);
					
					// сохранение в свойстве
					$this->client = $client;
					
					return $client;
				};
				
				// т.к. в session хранится ID
				// но hash или IP не действителен,
				// то происходит удаление ID и hash из
				// session, выключение session и изменение БД
				unset($_SESSION['user']);
				$this->sessionStop(true);
					
				// изменение базы данных
				$this->mysqli->query("UPDATE users SET status = 'offline', hash = 0 WHERE id = " . $client['id']);
			};
			
			// если из функции не вышли,
			// значит клиент не пользователь
			// настройка кодов Smarty
			$this->smarty->assign(
				'client',
				array(
					'id' => $client['id'],
					'login' => $client['login'],
					'post' => $client['post'],
					'ip' => $client['ip']
				)
			);
			
			// сохранение в свойстве
			$this->client = $client;
			
			return $client;
		}
		
		// ошибки, которые выдаются пользователю
		function errors($type) {
			// типы ошибок
			$errors = array(
				'permission' => array(
					'title' => 'Permission Error',
					'text' => 'К сожалению, Вы не имеете прав для доступа к этой странице.'
				),
				
				'content' => array(
					'title' => 'Content Error',
					'text' => 'К сожалению, в нашей базе данных не нашлось ни одного подходящего материала.'
				)
			);
			
			// сохранение в кодах Smarty
			$this->smarty->assign(
				'error',
				array(
					'title' => $errors[$type]['title'],
					'text' => $errors[$type]['text']
				)
			);
			
			// возвращение шаблона ошибки
			return $this->smarty->fetch('global/error.html');
		}
		
		// получение аватара с сервиса Gravatar
		function gravatar($email, $size, $default = false, $rating = 'g') {
			// генирация случайного аватара
			if (!$default) {
				$images = array(
					'identicon', 'monsterid', 'wavatar', 'retro'	
				);
				
				// случайный ключ
				$key = array_rand($images);
				
				// случайный элемент массива
				$default = $images[$key];
			};
			
			return 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?s=$size&d=$default&r=$rating';
		}
		// --------------------------------------------------------------
		
		
		
		
		// проверки -----------------------------------------------------
		// клиента на администратора
		function isAdmin() {
			if (isset($this->client['email']) && $this->client['email'] == 'ilya.cheremushkin@gmail.com' &&
				isset($this->client['post']) && $this->client['post'] == 'admin') {
				return true;
			} else {
				return false;
			};
		}
		
		// клиента на пользователя
		function isUser() {
			if ($this->client['post'] != 'guest') {
				return true;
			} else {
				return false;
			};
		}
		// --------------------------------------------------------------
		
		
		
		
		// AJAX методы --------------------------------------------------
		// авторизация
		function login() {
			if ($this->isUser()) {
				exit("answer = {
					text: 'Вы уже вошли в свой профиль.',
					success: 0
				};");
			};
			
			// получение данных из формы
			$user = array(
				'email' => isset($_POST['email']) ? $this->mysqli->real_escape_string(
					strtolower(
						trim(
							$_POST['email']
						)
					)
				) : NULL,
				'password' => isset($_POST['password']) ? md5(trim($_POST['password'])) : NULL
			);

			// проверка данных из формы
			if (!$user['email'] || !$user['password']) {
				exit("answer = {
					text: 'E-mail и/или пароль не переданы или равны пустой строке.',
					success: 0
				};");
			};
		
			// запрос для получения ID пользователя
			$result = $this->mysqli->query("SELECT id FROM users WHERE email = '" . $user['email'] . "' AND password = '" . $user['password'] . "'");
			if (!$result->num_rows) {
				exit("answer = {
					text: 'E-mail и/или пароль введены не верно.',
					success: 0
				};");
			};
			
			// ID и IP пользователя
			$user['id'] = $result->fetch_row()[0];
			$user['ip'] = $this->client['ip'];
			
			// создание уникального hash
			$user['hash'] = md5('s' . time() . 'al' . rand() . 't' . $user['id'] . $user['email']);
			
			// вход выполнен, открывается Session и сохраняется ID и hash
			$this->sessionStart();
			$_SESSION['user'] = array(
				'id' => $user['id'],
				'hash' => $user['hash']
			);
			
			// запись уникального hash, изменение статуса и IP в БД
			$this->mysqli->query("UPDATE users SET status = 'online', ip = '" . $user['ip'] . "', hash = '" . $user['hash'] . "' WHERE id = " . $user['id']);
			
			// вывод в JSON
			exit("answer = {
				success: 1
			};");
		}
		
		// выход
		function logout() {
			if (!$this->isUser()) {
				exit("answer = {
					text: 'Вы уже вышли из своего профиля.',
					success: 0
				};");
			};
			
			// ID пользователя
			$user = array(
				'id' => $this->client['id']
			);
			
			// выход выполнен, закрывается Session и удаляется ID и hash
			unset($_SESSION['user']);
			$this->sessionStop(true);
			
			// изменение базы данных
			$this->mysqli->query("UPDATE users SET status = 'offline', hash = 0 WHERE id = " . $user['id']);
			
			// вывод в JSON
			exit("answer = {
				success: 1
			};");
		}
		// --------------------------------------------------------------
	};
?>