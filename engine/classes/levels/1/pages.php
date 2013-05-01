<?php
    /**
     * Pages
     *
     * Manage single pages.
     *
     * @author Ilya Cheremushkin
     * @version 2.0
     */

    class Pages extends Level {
        use Identification, Title;

        private $registry; // instance of “Registry“
        private $builder; // instance of “Builder“
        private $smarty; // instance of “Smarty“
        private $database; // instance of database handler
        private $information; // information about class from Builder
        private $settings; // all settings
        private $client; // information about the client





        function __construct() {
            // initialize a properties
            $this->registry = Registry::instance();
            $this->builder = Builder::instance();
            $this->smarty = $this->registry->get('smarty');
            $this->database = $this->registry->get('database');
            $this->information = $this->builder->information(__CLASS__);
            $this->settings = $this->registry->get('settings');
            $this->client = $this->client();
        }





        /*
         * General
         */





        /**
         * Launches Page‘s work.
         *
         * @return mixed
         * Returns a template of the page.
         *
         * @throws Exception
         * 404 — Not Found
         */

        function launch() {
            // get URL from Registry and check that there is only one directive in it
			$url = $this->registry->get('url');
            if (count($url) > 1) throw new Exception(false, 404);


            // prepare a PDOStatement and get an ID of the requested page
			$statement = $this->database->prepare("
			    SELECT id
			    FROM {$this->information['table']}
			    WHERE url = :url
			");
            $statement->bindValue(":url", $url[0]); $statement->execute();


			// receive information about the page
			$page = $this->page($statement->fetchColumn());
            if (!$page) throw new Exception(false, 404);


            // create a title for the page
            array_push($this->title, $this->settings['classes'][$this->information['name']]['titles']['home'], $page['title']);
            $this->title();


			// save parts of the template in Smarty
			$this->smarty->append(
				'global',
				array(
					'header' => $this->smarty->fetch('global/header.html'),
					'content' => $page['content'],
					'scripts' => $this->smarty->fetch('global/scripts.html')
				),
				true
			);


			// definition a path to the page
			$template = "{$page['path']}/{$page['template']}";


			// return a compiled a template
			return $this->smarty->fetch($template);
		}




        /**
         * Gathers information about a page from database.
         *
         * @param $id
         * ID of the article.
         *
         * @return array
         * An array with information.
         */

        function page($id) {
            // prepare a PDOStatement, execute and fetch
			$statement = $this->database->prepare("
			    SELECT *
			    FROM {$this->information['table']}
			    WHERE id = :id
			");
            $statement->bindValue(":id", $id); $statement->execute();
            $page = $statement->fetch(PDO::FETCH_ASSOC);


            // check existance
			if (!$page) return false;


            // return information
			return $page;
		}
	};
?>