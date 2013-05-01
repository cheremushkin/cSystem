<?php
    /**
     * Blog
     *
     * Your own diary.
     *
     * @author Ilya Cheremushkin
     * @version 1.1
     */
	
	class Blog extends Level {
		use Identification, Title;

        private $registry; // instance of “Registry“
        private $builder; // instance of “Builder“
        private $smarty; // instance of “Smarty“
        private $database; // instance of database handler
        private $information; // information about class from Builder
        private $settings; // all settings
        private $url; // parsed URL
        private $client; // information about the client
		private $template; // path to the page‘s template

		
		
		
		
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
         * Launches Blog‘s work.
         *
         * @return mixed
         * Returns a template of Blog‘s page.
         */

        function launch() {
            // push an element of the title
            $this->title[] = $this->settings['classes'][$this->information['name']]['titles']['home'];


            // save global blocks in Smarty
			$this->smarty->assign(
				'global',
				array(
					'header' => $this->smarty->fetch('global/header.html'),
					'content' => $this->parse(),
					'scripts' => $this->smarty->fetch('global/scripts.html')
				)
			);


            // create a title
            $this->title();


            // return a compiled template
			return $this->smarty->fetch($this->template);
		}




        /**
         * Checks if the article with given ID exists.
         *
         * @param $id
         * ID of the article.
         *
         * @return bool
         * Returns ‘true‘ if articles exists and ‘false‘ if not.
         */

        function existence($id) {
            // prepare a PDOStatement, execute and fetch
            $statement = $this->database->prepare("SELECT COUNT(*) FROM {$this->information['table']} WHERE id = :id");
            $statement->bindValue(":id", $id); $statement->execute();
            return $statement->fetchColumn() ? true : false;
        }




        /**
         * Gathers information about an article from database.
         *
         * @param $id
         * ID of the article.
         *
         * @return array
         * An array with information.
         */

        function article($id) {
            // prepare a PDOStatement, execute and fetch
			$statement = $this->database->prepare("SELECT * FROM {$this->information['table']} WHERE id = :id");
			$statement->bindValue(":id", $id); $statement->execute(); $article = $statement->fetch(PDO::FETCH_ASSOC);
            if (empty($article)) return false;


            // article‘s date
            $article['date'] = date('F j, Y', strtotime($article['date']));


		    // extract a short version of text (and delete previous index)
			$article['texts'] = explode("<cut>", $article['text'], 2);
            unset($article['text']);
			
			if (!empty($article['texts'][1])) {
				$article['texts'] = array(
					'short' => $article['texts'][0],
					'full' => $article['texts'][0] . $article['texts'][1]
				);
				$article['cut'] = true;
			} else {
				$article['texts'] = array(
					'short' => $article['texts'][0],
					'full' => $article['texts'][0]
				);
				$article['cut'] = false;
			};


			// article‘s url and class
            $article['url'] = "/{$this->information['name']}/article/{$article['url']}/";
            $article['class'] = array(
                'id' => $this->information['id'],
                'name' => $this->information['name']
            );


            // article‘s rating
            $this->smarty->assign(
                'article',
                array(
                    'id' => $article['id'],
                    'class' => $article['class'],
                    'rating' => $article['rating']
                )
            );

            $article['rating'] = array(
				'number' => $article['rating'],
                'form' => $this->smarty->fetch("{$this->information['folder']}/views/rating.html")
            );


            // delete all assigned information from Smarty
			$this->smarty->clearAssign('article');


            // and return an array
			return $article;
		}




        /**
         * Parses a URL.
         *
         * @return string
         * Returns a template of the content.
         *
         * @throws Exception
         * 404 — Not Found
         */

        private function parse() {
			// get URL from Registry
			$this->url = $this->registry->get('url');


			// check URL‘s value (more that 3 elements will not be used)
			$page = array(
				$this->url[0],
				isset($this->url[1]) ? $this->url[1] : NULL,
                isset($this->url[2]) ? $this->url[2] : NULL,
                isset($this->url[3]) ? $this->url[3] : NULL
			);


            // switch first URL‘s element
			switch ($page[0]) {
				case 'main':
					$this->template = "{$this->information['folder']}/main.html";


					// if there is a directive below, return 404
					if ($page[1]) throw new Exception(false, 404);


                    // return the template of the content
					return $this->articles();


				default:
					$this->template = "{$this->information['folder']}/page.html";


					// switch second URL‘s element
					switch ($page[1]) {
                        // single article
                        case 'article':
                            // there must not be any params after
                            if ($page[3]) throw new Exception(false, 404);


                            // find ID of the article using his URL title
                            $statement = $this->database->prepare("
                                SELECT id
                                FROM {$this->information['table']}
                                WHERE url = :url
                            ");
                            $statement->bindValue(":url", $page[2]); $statement->execute();
                            $id = $statement->fetchColumn();


                            // change number of views
                            $this->views($id);


                            // article‘s information
                            $article = $this->article($id);
                            if (!$article) throw new Exception(false, 404);


                            // push title
                            $this->title[] = $article['title'];


                            // add to Smarty
                            $this->smarty->append('blog', array('article' => $article), true);


                            // return a template of the content
                            return $this->smarty->fetch("{$this->information['folder']}/views/article.html");


						default:
                            // there mustn‘t be any params after
						    if ($page[1]) throw new Exception(false, 404);


                            // return a template the content
							return $this->articles();
					};
			};
		}




        /**
         * Forms the articles. First 5 (changes in database) will be shown in preview mode. Latest — only dates.
         *
         * @return string
         * Return a template of the content.
         */

        private function articles() {
            // prepare PDOStatement, bind a value and execute
            $statement = $this->database->prepare("
                SELECT id FROM {$this->information['table']}
                ORDER BY date DESC
            ");
            $statement->execute();


			// save all articles in Smarty
			$articles = array();
            while ($article = $statement->fetchColumn()) {
                $articles[] = $this->article($article, false);
			};


            // save Smarty codes
            $this->smarty->append(
                'blog',
                array(
                    'articles' => array_merge($articles, array('size' => count($articles))),
                    'preview' => array(
                        'limit' => count($articles) > $this->settings['classes'][$this->information['name']]['articles']['show'] ?
            $this->settings['classes'][$this->information['name']]['articles']['show'] : count($articles)
                    )
                ),
                true
            );


            // return a hole template
			return $this->smarty->fetch("{$this->information['folder']}/views/articles.html");
		}




        /**
         * Changes the number of views for the article.
         *
         * @param $id
         *
         * @return bool
         * Returns ‘true‘ if the number of views has been changed and ‘false‘ if not.
         */

        private function views($id) {
			// check existence
            if (!$this->existence($id)) return false;


			// the time now and after a day
			$today = time();
			$tomorrow = time() + 86400;


			// clear strings where the meaning of timer is bigger than now
			$this->database->query("
			    DELETE FROM {$this->information['table']}_views
			    WHERE timer < $today
			");


			// prepare PDOStatement for checking if this client has already changed the number of views
			$statement = $this->database->prepare("
                  SELECT COUNT(*)
                  FROM {$this->information['table']}_views
                  WHERE id = :id AND ip = :ip
            ");
			$statement->execute(
                array(
                    ':id' => $id,
                    ':ip' => $this->client['ip']['num']
                )
            );
            if ($statement->fetchColumn()) return false;


			// prepare PDOStatement for receiving the number of views (plus 1 for this view)
			$statement = $this->database->prepare("
			    SELECT views
			    FROM {$this->information['table']}
			    WHERE id = :id
			");
            $statement->bindValue(":id", $id); $statement->execute();
            $views = $statement->fetchColumn() + 1;
			

			// changing
			$statement = $this->database->prepare("
			    INSERT INTO {$this->information['table']}_views (id, ip, timer)
				VALUES (:id, :ip, :timer)
			");
            $statement->execute(
                array(
                    ':id' => $id,
                    ':ip' => $this->client['ip']['num'],
                    ':timer' => $tomorrow
                )
            );

            $statement = $this->database->prepare("
                UPDATE {$this->information['table']}
                SET views = :views
                WHERE id = :id
            ");
            $statement->execute(
                array(
                    ':views' => $views,
                    ':id' => $id
                )
            );


            // return success
			return true;
		}





        /*
         * AJAX
         */





        /**
         * Changes article‘s rating.
         */

        function vote($data) {
			// initialize ID
			$id = !empty($data->id) ? Math\id($data->id) : NULL;
			if (!$id) throw new Exception("Wrong article‘s ID given.", 201);


			// get information about the article
			$article = $this->article($id);
            if (!$article) throw new Exception("Article does not exist.", 202);


			// now and tomorrow
			$time = time();
			$tomorrow = time() + 86400;


			// delete strings where timer has been ended
            $this->database->query("
                DELETE FROM {$this->information['table']}_ratings
                WHERE timer < $time
            ");


			// find string with the same ID and IP
			$statement = $this->database->prepare("
				SELECT *
				FROM {$this->information['table']}_ratings
				WHERE id = :id AND ip = :ip
			");
            $statement->execute(array(':id' => $id, ':ip' => $this->client['ip']['num']));
            $note = $statement->fetch(PDO::FETCH_ASSOC);
            if (!empty($note)) throw new Exception("You have already voted for this article.", 251);


            // define new rating
			switch ($data->way) {
				case 'plus': $rating = $article['rating']['number'] + 1; break;
				case 'minus': $rating = $article['rating']['number'] - 1; break;
				default:
					throw new Exception("Rating param has been given incorrect.", 203);
			};


			// change information in database
			$statement = $this->database->prepare("
				INSERT INTO {$this->information['table']}_ratings (id, ip, timer)
				VALUES (:id, :ip, $tomorrow)
			");
            $statement->execute(array(':id' => $id, ':ip' => $this->client['ip']['num']));

			$statement = $this->database->prepare("
			    UPDATE {$this->information['table']}
			    SET rating = :rating
			    WHERE id = :id
			");
            $statement->execute(array(':rating' => $rating, ':id' => $id));


			// return an answer
			return array(
				'message' => "You have successfully voted.",
				'success' => 1,
				'rating' => $rating,
                'log' => false
            );
		}
	};
?>