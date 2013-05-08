<?php
	class Filemaj extends Level {
		use Session;

        private $smarty; // instance of Smarty
        private $information; // information about the class from Builder
        private $settings; // settings for FileMAJ


        private $path; // initialized path to the folder, that will be parsed
		private $types = array();





        function __construct() {
            // initialize a properties
            $this->registry = Registry::instance();
            $this->smarty = $this->registry->get('smarty');
            $this->information = Builder::instance()->information(__CLASS__);
            $this->settings = $this->registry->get('settings');


            // set the list of types
            $this->types();


            // check permission in the Control Panel
            if (!Proxy::instance()->request('Control', 'permissions')) return "You have no permissions to work with FileMAJ.";
        }





        /**
         * General
         */





        /**
         * Saves path of the current folder in the right format.
         *
         * @param $path
         * The current path.
         */

		function path($path) {
			$this->path = realpath(ROOT . $path);
		}




        /**
         * Launches FileMAJ‘s work.
         *
         * @return string
         * Returns a template of the FileMAJ‘s block.
         */

        function launch() {
            // save in Smarty
            $this->smarty->append($this->information['name'], ['folder' => $this->parse($this->path)], true);

            // fetch a template
            return $this->smarty->fetch("{$this->information['folder']}/template.html");
        }




        /**
         * Initializes the list of types.
         */

        function types() {
            $this->types = array(
                'script' => array(
                    'extension' => array('php', 'js'),
                    'link' => false
                ),
                'template' => array(
                    'extension' => array('html', 'xhtml', 'css'),
                    'link' => false
                ),
                'archive' => array(
                    'extension' => array('zip', 'rar'),
                    'link' => true
                ),
                'text' => array(
                    'extension' => array('txt', 'doc'),
                    'link' => false
                ),
                'pdf' => array(
                    'extension' => array('pdf'),
                    'link' => true
                ),
                'jpg' => array(
                    'extension' => array('jpg', 'jpeg'),
                    'link' => true
                ),
                'gif' => array(
                    'extension' => array('gif'),
                    'link' => true
                ),
                'png' => array(
                    'extension' => array('png'),
                    'link' => true
                )
            );
        }




        /**
         * Parses the directory into the beautiful array.
         *
         * @param $path
         * The path in the right form.
         *
         * @return array
         * An array with all information about the files.
         */

        private function parse($path) {
			// scanning
			$content = scandir($path);
			
			
			// two arrays that will merge in the end
			$folders = [];
			$files = [];
			
			
			// parent folder
			if ($path != ROOT) {
				$parent = dirname($path);
				$folders[] = array(
					'type' => 'parent',
					'basename' => '..',
					'path' => array(
						'absolute' => $parent,
						'relative' => substr($parent, strlen(ROOT)) ? str_replace('\\', '/', substr($parent, strlen(ROOT))) : '/',
						'operations' => "/templates/{$this->information['folder']}/operations/parent/"
					),
					'logo' => "/templates/{$this->information['folder']}/operations/parent/parent.png",
					'operations' => false
				);
			};

			
			// first array is for folders, second array is for files
			for ($i = 2, $k = 0, $size = count($content); $i < $size; $i++) {
				$element = realpath("{$path}/{$content[$i]}");
				if (is_dir($element)) {
                    $folders[] = array_merge(
                        $this->folder(realpath("{$path}/{$content[$i]}")),
                        ['number' => ++$k]
                    );
                };
			};
			
			for ($i = 2, $k = 0, $size = count($content), $count = count($folders); $i < $size; $i++) {
				$element = realpath("{$path}/{$content[$i]}");
				if (!is_dir($element)) {
                    $files[] = array_merge(
                        $this->file(realpath("{$path}/{$content[$i]}")),
                        ['number' => ++$k + $count]
                    );
                };
			};
			
			return array_merge($folders, $files);
		}




        /**
         * Makes an array with the information about the file.
         *
         * @param $path
         * Path to the file in the correct form.
         *
         * @return array
         * Array with the information.
         */

        private function file($path) {
			// get base info
            $file = pathinfo($path);


			// finding an extension and a type
			$type = "other"; $link = true;
			foreach ($this->types as $key => $value) {
				if (in_array($file['extension'], $value['extension'])) {
					$type = $key;
					$link = $value['link'];
					break;
				};
			};


            // form an array
			$file = array_merge(
				array(
					'path' => array(
						'absolute' => $path,
						'relative' => str_replace('\\', '/', substr($path, strlen(ROOT))),
						'operations' => "/templates/{$this->information['folder']}/operations/$type/"
					),
					'type' => $type,
					'link' => $link,
					'logo' => "/templates/{$this->information['folder']}/operations/$type/$type.png"
				),
				$file
			);


			// add operation block (before I need to prepare Smarty codes)
			$this->smarty->assign("file", $file);
			$file['operations'] = $this->smarty->fetch("{$this->information['folder']}/operations/$type/template.html");
            $this->smarty->clearAssign("file");
			
			return $file;
		}




        /**
         * Makes an array with the information about the folder.
         *
         * @param $path
         * Path to the folder in the correct form.
         *
         * @return array
         * Array with the information.
         */

        private function folder($path) {
            // form an array
            $folder = array(
				'type' => 'folder',
				'basename' => basename($path),
				'path' => array(
					'absolute' => $path,
					'relative' => substr($path, strlen(ROOT)) ? str_replace('\\', '/', substr($path, strlen(ROOT))) : '/',
					'operations' => "/templates/{$this->information['folder']}/operations/folder/"
				),
				'logo' => "/templates/{$this->information['folder']}/operations/folder/folder.png"
			);
			
			// add operation block (before I need to prepare Smarty codes)
			$this->smarty->assign("folder", $folder);
			$folder['operations'] = $this->smarty->fetch("{$this->information['folder']}/operations/folder/template.html");
            $this->smarty->clearAssign("folder");
			
			return $folder;
		}





        /**
         * AJAX
         */





        /**
		 * Changes the directory.
		 *
		 * @param $data
		 *
		 * @return array
		 *
		 * @throws Exception
		 * 201 — No such directory.
		 */

		function cd($data) {
			$path = realpath(ROOT . $data->path);
			if (!is_dir($path))	throw new Exception("No such directory.", 201);


			// save in Smarty
			$this->smarty->append($this->information['name'], ['folder' => $this->parse($path)], true);

			// save current directory path in the session
			$_SESSION['control']['filemaj'] = ['folder' => $data->path];
			
			return array(
				'template' => $this->smarty->fetch("{$this->information['folder']}/template.html"),
				'message' => "FileMAJ changed directory. Path: $path.",
				'code' => 200
			);
		}



		/**
		 * Gets template with the source code and information about the file.
		 *
		 * @param $data
		 *
		 * @return array
		 *
		 * @throws Exception
		 * 202 — No such file.
		 */

		function getEditTemplate($data) {
			$path = realpath(ROOT . $data->path);
			if (!file_exists($path)) throw new Exception("No such file.", 202);


			// save in Smarty
			$this->smarty->assign(
				"file",
				array(
					'path' => array(
						'absolute' => $path,
						'relative' => str_replace('\\', '/', substr($path, strlen(ROOT)))
					),
					'source' => htmlspecialchars(file_get_contents($path))
				)
			);

			// fetch a template
			$template = $this->smarty->fetch("{$this->information['folder']}/views/edit.html");

			// delete an assignation
			$this->smarty->clearAssign("file");
			
			
			return array(
				'template' => $template,
				'message' => "File '$path' successfully opened for edit.",
				'code' => 200
			);
		}



		/**
		 * Saves edited source code.
		 *
		 * @param $data
		 *
		 * @return array
		 *
		 * @throws Exception
		 * 202 — No such file.
		 * 202 — Error with writing in the file.
		 */

		function saveSourceCode($data) {
			$path = realpath(ROOT . $data->path);
			if (!file_exists($path)) throw new Exception("No such file.", 202);
			
			
			// write in file
			if (file_put_contents($path, $data->source) === false) throw new Exception("Error with writing in the file.", 203);

			return array(
				'message' => "Source code has successfully saved.",
				'code' => 200
			);
		}
		
		
		
		// delete a file/directory (realy it moves into basket)
    	function delete($data) {
			if (!file_exists($path)) {
				exit(json_encode(array(
					'text' => "Удаляемого файла не существует.",
					'success' => 0
				)));
			};
		}
        
        // get basket page with all files in it
    	function getBasketTemplate($data) {
		}
		// --------------------------------------------------------------
	};
?>