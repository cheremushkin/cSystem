<?php
	/**
	 * CAPTCHA creates security code.
	 */
	
	class Captcha extends Level {
		use Session;

        private $information; // information about class from Builder
		
		
		
		
		function __construct() {
			// initialize properties
			$this->information = Builder::instance()->information(__CLASS__);
		}
		
		
		
		
		function launch() {
            $id = empty($_GET['id']) ? NULL : $_GET['id'];
            if (!$id) throw new Exception("Security code’s ID has given incorrectly.", 101);

            $length = empty($_GET['length']) ? 4 : $_GET['length'];
			$width = empty($_GET['width']) ? 125 : $_GET['width'];
			$height = empty($_GET['height']) ? 40 : $_GET['height'];
			$start = empty($_GET['start']) ? 10 : $_GET['start'];
			$interval = 30;
			$chars = str_split('1234567890');
			$string = '';
			
			$font = array(
				'min' => empty($_GET['min']) ? 20 : $_GET['min'],
				'max' => empty($_GET['max']) ? 30 : $_GET['max'],
				'file' => realpath(TEMPLATES . "/{$this->information['folder']}/font.ttf"),
				'angle' => array(
					'min' => -5,
					'max' => 5
				),
				'shadow' => 5
			);
			
			
			// count align
			$font['align'] = $font['max'] + ($font['max'] - $font['min']) / 2;
			
			
			// creating an image
			$image = imagecreatetruecolor($width, $height);
			
			
			// background and font colors
			$background = empty($_GET['background']) ? [255, 255, 255] : explode(',', $_GET['background']);
			$color = empty($_GET['color']) ? [0, 0, 0] : explode(',', $_GET['color']);
			
			$background = imagecolorallocate($image, $background[0], $background[1], $background[2]);
			$color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
			
			
			// fill background
			imagefill($image, 0, 0, $background);
			
			
			// drawing
			for ($i = 0; $i < $length; $i++) {
				$char = $chars[array_rand($chars)];
				
				$size = rand($font['min'], $font['max']);
				$angle = rand($font['angle']['min'], $font['angle']['max']);
				
				imagettftext($image, $size, $angle, $start, $font['align'], $color, $font['file'], $char);
				imagettftext($image, $size, $angle + $font['shadow'] * (rand(0, 1) * 2 - 1), $start, $font['align'], $background, $font['file'], $char);
				
				$start += $interval;
				
				$string .= $char;
			};
			
			// save in Session
			$this->sessionStart();
			$_SESSION['captcha'] = array_merge(
                is_array($_SESSION['captcha']) ? $_SESSION['captcha'] : array(),
                array($id => $string)
            );
			
			header('Content-type: image/png');
			imagepng($image);
			
			imagedestroy($image);
		}
	};
?>