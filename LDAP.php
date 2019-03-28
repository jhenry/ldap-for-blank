<?php

class LDAP extends PluginAbstract
{
	/**
	* @var string Name of plugin
	*/
	public $name = 'LDAP';

	/**
	* @var string Description of plugin
	*/
	public $description = 'Provides directory lookup and query tools for LDAP.';

	/**
	* @var string Name of plugin author
	*/
	public $author = 'Justin Henry';

	/**
	* @var string URL to plugin's website
	*/
	public $url = 'https://uvm.edu/~jhenry/';

	/**
	* @var string Current version of plugin
	*/
	public $version = '0.0.1';

	/**
	 * Flatten/join arrays for entries with multiple items.
	 * 
	 * @param array $ldap_array
	 * @return array
	 */
	function flatten_ldap_arrays($ldap_array) {
		foreach ( $ldap_array as &$item ) {
			if ( is_array($item)) {
				$item = implode( ";", $item);
			}
		}
		unset($item);
		return $ldap_array;
	}

		

	/**
	 * Get an entry in LDAP for the specified username.
	 * 
	 * @param int $username
	 * @return array
	 */
	public function get_ldap_entry($username) {

		$filter = "netid=" . $username;
			
		// Connection and Base DN string configuration.
		$ldapserver = "ldaps://ldap.uvm.edu";
		$dnstring = "dc=uvm,dc=edu";
		
		$ds = ldap_connect($ldapserver);
		
		if ($ds) {
			// Bind (anonymously, no auth) and search
			$r = ldap_bind($ds);
			$sr = ldap_search($ds, $dnstring, $filter);

			// Kick out on a failed search.
			if(!ldap_count_entries($ds, $sr))
				return false;

			// Retrieve records found by the search
			$info = ldap_get_entries($ds, $sr);
			$entry = ldap_first_entry($ds, $sr);
			$attrs = ldap_get_attributes($ds, $entry);

			// Close the door on the way out.
			ldap_close($ds);

			//return $attrs;
			return cleanUpEntry($attrs);
		}

	}

	/**
	 * Clean up and flatten an LDAP entry array.  
	 * https://secure.php.net/manual/en/function.ldap-get-entries.php#89508
	 *
	 * @param array $entry
	 * @return array
	 */
	public function cleanUpEntry( $entry ) {
		$retEntry = array();
		for ( $i = 0; $i < $entry['count']; $i++ ) {
			if (is_array($entry[$i])) {
				$subtree = $entry[$i];
				if ( ! empty($subtree['dn']) and ! isset($retEntry[$subtree['dn']])) {
					$retEntry[$subtree['dn']] = cleanUpEntry($subtree);
				} else {
					$retEntry[] = cleanUpEntry($subtree);
				}
			} else {
				$attribute = $entry[$i];
				if ( $entry[$attribute]['count'] == 1 ) {
					$retEntry[$attribute] = $entry[$attribute][0];
				} else {
					for ( $j = 0; $j < $entry[$attribute]['count']; $j++ ) {
					  $retEntry[$attribute][] = $entry[$attribute][$j];
					}
				}
			}
		}
		return $retEntry;
	}
	

	/**
	* Pretty print vars for more convenient debugging.
	*
	* @var object/array to print
	*
	*/
	private function tracer($var) {
		echo " ============================= ";
		echo "<pre>";
		var_dump($var); 
		echo "</pre>";
	}
	
	
	/**
	* Handle and display an error in the proper context.
	*
	* @var string Error message to display.
	*
	*/
	private function do_exception($errorMessage)
	{
		//TODO: Hook into filter for system error view 
		echo "Error:" . $errorMessage;
		exit;
	}
}

