<?php
	trait Gravatar {
		function gravatar($email, $size, $default = false, $rating = 'g') {
			if (!$default) {
				$images = array(
					'identicon', 'monsterid', 'wavatar', 'retro'	
				);
				
				// random key
				$key = array_rand($images);
				$default = $images[$key];
			};
			
			$email = md5(strtolower(trim($email)));
			return "http://www.gravatar.com/avatar/$email?s=$size&d=$default&r=$rating";
		}
	};
?>