<?php
	/**
	 * Класс «Register» хранит глобавльные переменные и должен быть передан во все объекты.
	 * Класс защищен от повторного создания с помощью шаблона проектирования «Singleton».
	 */
	
	final class Registry {
		private $vars = array(); // массив переменных
		
		
		
		
		// Singleton ----------------------------------------------------
		static private $instance = NULL; // инстанс класса
		
		// создание и получение инстанса
		static function instance() {
			if (self::$instance == NULL) {
				self::$instance = new Registry();
			};
			
			return self::$instance;
		}
		
		// скрытие конструктора и клонирования
		private function __construct() {}
		private function __clone() {}
		// --------------------------------------------------------------
		
		
		
		
		// публичные функции --------------------------------------------
		// сохранение
		function set($name, $value = NULL, $type = 'simple') {
			if (!is_string($name)) {
				$this->errors('arg', 1, __METHOD__, 'string', gettype($name));
				return false;
			};
			
			// установка переменной
			switch ($type) {
				case 'protected':
					if (!isset($this->vars[$name]) || (isset($this->vars[$name]) && $this->vars[$name]['type'] == 'simple')) {
						$this->vars[$name] = array(
							'value' => $value,
							'type' => $type
						);
						return true;
					} else {
						$this->errors('var', 0, $name, __METHOD__);
						return false;
					};
					
				case 'simple':
					if (isset($this->vars[$name]) && $this->vars[$name]['type'] == 'protected') {
						$this->errors('var', 0, $name, __METHOD__);
					} else {
						$this->vars[$name] = array(
							'value' => $value,
							'type' => $type
						);
						return true;
					};
									
				default:
					$this->errors('var', 2, $name, __METHOD__);
					return false;
			};
		}
		
		// получение
		function get($name) {
			if (!is_string($name)) {
				$this->errors('arg', 1, __METHOD__, 'string', gettype($name));
				return false;
			};
			
			if (isset($this->vars[$name])) {
				return $this->vars[$name]['value'];
			} else {
				$this->errors('var', 1, $name, __METHOD__);
				return false;
			}
		}
		// --------------------------------------------------------------
		
		
		
		
		// ошибки (приватная функция) -----------------------------------
		private function errors($type) {
			switch ($type) {
				case 'arg':
					// инициализация аргументов
					$number = func_get_arg(1);
					$method = func_get_arg(2);
					$must = func_get_arg(3);
					$given = func_get_arg(4);
					
					$call = debug_backtrace()[1];
					trigger_error("argument <i>$number</i> passed to <i>$method()</i> must be an instance of <u>$must</u>, <u>$given</u> given, called in <b>" . $call['file'] . "</b> on line <b>" . $call['line'] . "</b> and defined", E_USER_WARNING);
					break;
					
				case 'var':
					// инициализация аргументов
					$error = func_get_arg(1);
					$name = func_get_arg(2);
					$method = func_get_arg(3);
					
					// ошибки
					$errors = array(
						array(
							'value' => "protected variable <i>$name</i> passed to <i>$method()</i> has already been registered",
							'type' => E_USER_WARNING
						),
						array(
							'value' => "variable <i>$name</i> passed to <i>$method()</i> has not been registered",
							'type' => E_USER_WARNING
						),
						array(
							'value' => "type of variable <i>$name</i> passed to <i>$method()</i> must be <u>simple</u> or <u>protected</u>",
							'type' => E_USER_WARNING
						)
					);
					
					$call = debug_backtrace()[1];
					trigger_error($errors[$error]['value'] . ", called in <b>" . $call['file'] . "</b> on line <b>" . $call['line'] . "</b> and defined", $errors[$error]['type']);
					break;
			};
		}
		// --------------------------------------------------------------
	}
?>