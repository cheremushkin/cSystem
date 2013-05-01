<?php
	class Filemaj extends Level {
		use Session;

        private $smarty; // global Smarty instance from Registry
        private $information; // information about class from Builder
		private $types = array(
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
        ); // list of file types
		
		private $path; // initialized path to folder, that will be parse
		
		const operations = "filemaj/operations"; // path to the folder with operations relative to /templates folder
		const basket = "filemaj/basket"; // basket's folder relative to /templates folder
		
		
		
		
		// методы интерфейса --------------------------------------------
		function __construct() {
			// initialization properties
			$this->smarty = Registry::instance()->get('smarty');
            $this->information = Builder::instance()->information(__CLASS__);


            // check permission from Control class
            $permissions = Proxy::instance()->request('Control', 'permissions');
            if (!$permissions) return "You have no permissions to work with FileMAJ.";
		}
		
		
		
		function init($params) {
			foreach ($params as $key => $value) {
				$this->$key = $value;
			};
		}
		
		
		
		function launch() {
			$this->smarty->assign(
				'filemaj',
				array(
					'content' => $this->parse(realpath(ROOT . $this->path))
				)
			);
			return $this->smarty->fetch("filemaj/template.html");
		}
		// --------------------------------------------------------------
		
		
		
		
		// main functions -----------------------------------------------
		// receiving information about file
		private function parse($path) {
			// scanning
			$content = scandir($path);
			
			
			// two arrays, that will merge in the end
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
						'operations' => "/templates/" . self::operations . "/parent/"
					),
					'logo' => "/templates/" . self::operations . "/parent/parent.png",
					'operations' => false
				);
			};
			
			// folders, two arrays are for normal counting
			for ($i = 2, $k = 0, $size = count($content); $i < $size; $i++) {
				$element = realpath("{$path}/{$content[$i]}");
				if (is_dir($element)) $folders[] = $this->folder(realpath("{$path}/{$content[$i]}"), ++$k);
			};
			
			for ($i = 2, $k = 0, $size = count($content), $count = count($folders); $i < $size; $i++) {
				$element = realpath("{$path}/{$content[$i]}");
				if (!is_dir($element)) $files[] = $this->file(realpath("{$path}/{$content[$i]}"), ++$k + $count);
			};
			
			return array_merge($folders, $files);
		}
		
		// receiving information about file
		private function file($path, $number) {
			$file = pathinfo($path);

			// finding extension and type
			$type = 'other'; $link = true;
			foreach ($this->types as $key => $value) {
				if (in_array($file['extension'], $value['extension'])) {
					$type = $key;
					$link = $value['link'];
					break;
				};
			};
			
			$file = array_merge(
				array(
					'number' => $number,
					'path' => array(
						'absolute' => $path,
						'relative' => str_replace('\\', '/', substr($path, strlen(ROOT))),
						'operations' => "/templates/" . self::operations . "/$type/"
					),
					'type' => $type,
					'link' => $link,
					'logo' => "/templates/" . self::operations . "/$type/$type.png"
				),
				$file
			);
			
			// add operation block (before I need to prepare Smarty codes)
			$this->smarty->assign('file', $file);
			$file['operations'] = $this->smarty->fetch(self::operations . "/$type/template.html");
			$this->smarty->clearAssign('file');
			
			return $file;
		}
		
		// receiving information about folder
		private function folder($path, $number) {
			$folder = array(
				'number' => $number,
				'type' => 'folder',
				'basename' => basename($path),
				'path' => array(
					'absolute' => $path,
					'relative' => substr($path, strlen(ROOT)) ? str_replace('\\', '/', substr($path, strlen(ROOT))) : '/',
					'operations' => "/templates/" . self::operations . "/folder/"
				),
				'logo' => "/templates/" . self::operations . "/folder/folder.png"
			);
			
			// add operation block (before I need to prepare Smarty codes)
			$this->smarty->assign('folder', $folder);
			$folder['operations'] = $this->smarty->fetch(self::operations . "/folder/template.html");
			$this->smarty->clearAssign('folder');
			
			return $folder;
		}
		// --------------------------------------------------------------
		
		
		
		
		// AJAX methods -------------------------------------------------
		// change directory
		function cd($data) {
			$path = realpath(ROOT . $data->path);
			if (!is_dir($path))	throw new Exception("No such directtory.", 0);
			
			
			// saving Smarty codes
			$this->smarty->assign(
				'filemaj',
				array(
					'content' => $this->parse($path)
				)
			);
			
			
			// save current directory path to Session
			$_SESSION['filemaj'] = array(
				'folder' => $data->path
			);
			
			
			return array(
				'template' => $this->smarty->fetch("filemaj/template.html"),
				'message' => "FileMAJ changed directory to $path.",
				'code' => 200
			);
		}
		
		
		
		// get template with source code and information about file
		function getEditTemplate($data) {
			$path = realpath(ROOT . $data->path);
			if (!file_exists($path)) throw new Exception("No such file.");
			
			
			// saving Smarty codes
			$this->smarty->assign(
				'file',
				array(
					'path' => array(
						'absolute' => $path,
						'relative' => str_replace('\\', '/', substr($path, strlen(ROOT)))
					),
					'filesize' => number_format(filesize($path) / 1024, 2, '.', ''),
					'filemtime' => date("F j, Y \i\\n G:i:s", filemtime($path)),
					'source' => htmlspecialchars(file_get_contents($path))
				)
			);
			
			
			return array(
				'template' => $this->smarty->fetch('filemaj/views/edit.html'),
				'message' => "File '$path' successfully opened for edit.",
				'code' => 200
			);
		}
		
		
		
		// save source code
		function saveSourceCode($data) {
			$path = realpath(ROOT . $data->path);
			if (!file_exists($path)) throw new Exception("No such file ('$path').");
			
			
			// write in file
			if (file_put_contents($path, $data->source) === false) throw new Exception("Error with writing in the file ('$path').");
			
			
			return array(
				'message' => "Changes has been successfully made in file ('$path').",
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