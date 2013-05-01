<?php
	/**
	 * Trait with functions for identify clients.
	 */
	
	trait Identification {
		// information about client
		private function client() {
			// initialization Smarty
			$registry = Registry::instance();
			$smarty = $registry->get('smarty');
			
			
			// creating standart guest information
			$client = array(
				'ip' => array(
                    'char' => $_SERVER['REMOTE_ADDR'],
                    'num' => ip2long($_SERVER['REMOTE_ADDR'])
                )
			);
			
			$smarty->assign('client', $client);
			return $client;
		}
	};
?>