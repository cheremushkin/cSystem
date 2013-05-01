<?php
	/**
	 * Трейт, предоставляющий 2 функции для работы с Session: запуск и остановка.
	 */
	
	trait Session {
		// форсированный запуск Session, если она уже не запущена
		private function sessionStart() {
			if (empty($_COOKIE[session_name()])) {
				session_start();
			};
		}
	
		// session отключается только в том случае, если она пуста или, если
		// выклучение форсированно (при условии, что она еще существует)
		private function sessionStop($forced = false) {
			if (empty($_SESSION) || ($forced && !empty($_COOKIE[session_name()]))) {
				// если session хранится в cookie
				if (ini_get("session.use_cookies")) {
					// получение параметров
					$params = session_get_cookie_params();
					
					// уничтожение cookie
					setcookie(
						session_name(),
						'',
						time() - 60 * 60 * 24 * 7,
						$params['path'],
						$params['domain'],
						$params['secure'],
						$params['httponly']
					);
				};
				
				session_destroy();
			};
		}
	};
?>