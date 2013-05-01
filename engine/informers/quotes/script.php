<?php
    /**
     * Random quote from database.
     *
     * @author: Ilya Cheremushkin
     * @version 1.0
     */

    final class Quotes implements Informer {
        private $registry;
        private $builder;
        private $smarty;
        private $database;



        function __construct() {
            $this->registry = Registry::instance();
            $this->builder = Builder::instance();
            $this->smarty = $this->registry->get('smarty');
            $this->database = $this->registry->get('database');
        }



        function launch() {
            // prepare a PDOStatement
            $result = $this->database->query("
                SELECT *
                FROM feature_informers_quotes
            ");
            $quotes = $result->fetchAll(PDO::FETCH_ASSOC);


            // save random quote in Smarty
            $key = array_rand($quotes);
            $this->smarty->assign("quote", $quotes[$key]);


            // fetch a template
            $template = $this->smarty->fetch(__DIR__ . "/quote.html");


            // clear assignation
            $this->smarty->clearAssign("quote");


            // return a template
            return $template;
        }
    };
?>