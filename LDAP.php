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
	public $version = '0.0.3';

	/**
	 * The plugin's gateway into codebase. Place plugin hook attachments here.
	 */
	public function load()
	{
		// Nothing to load.
	}

	/**
	 * Performs install operations for plugin. Called when user clicks install
	 * plugin in admin panel.
	 *
	 */
	public function install()
	{
		// Add default directory fields to Settings.
		$attributes = array('homeDirectory', 'givenName', 'sn', 'ou', 'labeledURI', 'mail', 'eduPersonPrimaryAffiliation');

		Settings::set('ldap_attributes', json_encode($attributes));
		Settings::set('ldap_filter_prefix', 'netid');
		Settings::set('ldap_uri', 'ldaps://ldap.uvm.edu');
		Settings::set('ldap_dn_string', 'dc=uvm,dc=edu');
	}
	/**
	 * Performs uninstall operations for plugin. Called when user clicks
	 * uninstall plugin in admin panel and prior to files being removed.
	 *
	 */
	public function uninstall()
	{
		Settings::remove('ldap_attributes');
		Settings::remove('ldap_filter_prefix');
		Settings::remove('ldap_uri');
		Settings::remove('ldap_dn_string');
	}


	/**
	 * Lookup a user in the directory.
	 * 
	 * @param string $username
	 * @return array 
	 */
	public function get_entry($username)
	{
		return LDAP::flatten_ldap_arrays(LDAP::get_ldap_entry($username));
	}

	/**
	 * Flatten/join arrays for entries with multiple items.
	 * 
	 * @param array $ldap_array
	 * @return array
	 */
	public function flatten_ldap_arrays($ldap_array)
	{
		foreach ($ldap_array as &$item) {
			if (is_array($item)) {
				$item = implode(";", $item);
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
	public function get_ldap_entry($username)
	{
		$prefix = Settings::get('ldap_filter_prefix');
		$filter = $prefix . "=" . $username;

		// Connection and Base DN string configuration.
		$ldapserver = Settings::get('ldap_uri');
		$dnstring = Settings::get('ldap_dn_string');

		$ds = ldap_connect($ldapserver);

		if ($ds) {
			// Bind (anonymously, no auth) and search
			$r = ldap_bind($ds);
			$sr = ldap_search($ds, $dnstring, $filter);

			// Kick out on a failed search.
			if (!ldap_count_entries($ds, $sr))
				return false;

			// Retrieve records found by the search
			$info = ldap_get_entries($ds, $sr);
			$entry = ldap_first_entry($ds, $sr);
			$attrs = ldap_get_attributes($ds, $entry);

			// Close the door on the way out.
			ldap_close($ds);

			//return $attrs;
			return LDAP::cleanUpEntry($attrs);
		}
	}

	/**
	 * Clean up and flatten an LDAP entry array.  
	 * https://secure.php.net/manual/en/function.ldap-get-entries.php#89508
	 *
	 * @param array $entry
	 * @return array
	 */
	public function cleanUpEntry($entry)
	{
		$retEntry = array();
		for ($i = 0; $i < $entry['count']; $i++) {
			if (is_array($entry[$i])) {
				$subtree = $entry[$i];
				if (!empty($subtree['dn']) and !isset($retEntry[$subtree['dn']])) {
					$retEntry[$subtree['dn']] = cleanUpEntry($subtree);
				} else {
					$retEntry[] = cleanUpEntry($subtree);
				}
			} else {
				$attribute = $entry[$i];
				if ($entry[$attribute]['count'] == 1) {
					$retEntry[$attribute] = $entry[$attribute][0];
				} else {
					for ($j = 0; $j < $entry[$attribute]['count']; $j++) {
						$retEntry[$attribute][] = $entry[$attribute][$j];
					}
				}
			}
		}
		return $retEntry;
	}
}
