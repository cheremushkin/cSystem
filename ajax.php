<?php
    /**
     * ajax.php
     *
     * One of the main system pages.
     * Every AJAX request should be sent here.
     */




    /**
     * Constants.
     */

    define('ROOT', __DIR__);
    define('ENGINE', realpath(__DIR__ . "/engine"));
    define('TEMPLATES', realpath(__DIR__ . "/templates"));




    /**
     * Class loader and a handler-definer for him.
     *
     * @param $class
     */

    function autoload($class) {
        // имена файлов классов должны быть в нижнем регистре
        $class = strtolower($class);

        // возможный путь к классам ядра
        $path = realpath(ENGINE . "/classes/$class.php");

        // подключение в случае, если существует класс ядра
        // иначе выполняется поиск по уровням
        if (file_exists($path)) include($path);
        else {
            for ($i = 0; $i < 3; $i++) {
                $path = realpath(ENGINE . "/classes/levels/$i/$class.php");
                if (file_exists($path)) {
                    include($path);
                    break;
                };
            };
        }
    };

    spl_autoload_register('autoload');




    /**
     * Abstract class for Levels.
     */

    abstract class Level {
        // init properties (for example, Smarty, Registry)
        abstract function __construct();

        // launch a program and receive a template
        abstract function launch();

        // local AJAX controller
        function ajax($data) {
            $method = $data->method;
            return $this->$method($data);
        }
    };

    /**
     * Interface Informer for informers.
     */

    interface Informer {
        // инициализация свойств (например, Smarty, Registry)
        function __construct();

        // запуск работы информера получение окончательного шаблона
        function launch();
    };




    /**
     * Make changes in php.ini.
     */

    $ini = array(
        'date.timezone' => 'Europe/Moscow',
        'session.name' => 'cSystemSID',
        'session.cookie_lifetime' => 60 * 60 * 24 * 7,
        'error_reporting' => E_ALL,
        'precision' => 32
    );

    foreach ($ini as $key => $value) {
        ini_set($key, $value);
    };




    /**
     * Instance the Facade.
     */

    $facade = new Facade();






    /**
     * Call Facade‘s methods and launch a working class.
     */

    $facade->database();
    $facade->smarty();
    $facade->namespaces();
    $facade->traits();
    $facade->session();
    $facade->codes();
    $facade->settings();
    $facade->ajax();
?>