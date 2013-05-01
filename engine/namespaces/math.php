<?php
	namespace Math;


    /**
     * Checks if given data is a string that has got only numeric symbols.
     * Symbol ‘e‘ is not a numeric, so string '5e4' will be converted into the 0.
     *
     * @param $data
     *
     * @return int
     * Returns conversation of the string into the number or 0.
     */

    function id($data) {
		$pattern = '/[^0-9]+/';
		$result = !preg_match($pattern, $data) ? intval($data) : 0;

		return $result;
	};
?>