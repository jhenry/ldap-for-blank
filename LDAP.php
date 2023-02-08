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
    Settings::set('ldap_uri', 'ldaps://ldap.your.edu');
    Settings::set('ldap_dn_string', 'dc=your,dc=edu');
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
   * Lookup a user in the directory and return all of their attributes.
   * 
   * @param string $username ID of user to query in the directory
   */
  public static function get_all($username)
  {
    return LDAP::flatten_ldap_arrays(LDAP::directory_query($username));
  }

  /**
   * Perform user lookup, filtering results based on default attributes from settings.
   * 
   * @param array $ldap_array
   * @return array
   */
  public static function get($username)
  { 
    $entry = LDAP::flatten_ldap_arrays(LDAP::directory_query($username));
      $attributes = json_decode(Settings::get('ldap_attributes'));
      foreach( $attributes as $attribute ) {
	if (array_key_exists($attribute, $entry)) {
	  $filtered_entry[$attribute] = $entry[$attribute];
	}
      }

      return $filtered_entry;
  }

  /**
   * Flatten/join arrays for entries with multiple items for easier traversing.
   * 
   * @param array $ldap_array raw directory query result
   * @return array $ldap_array "flattened" array.
   */
  public static function flatten_ldap_arrays($ldap_array)
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
  public static function directory_query($username)
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
  public static function cleanUpEntry($entry)
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

  /**
   * Outputs the settings page HTML and handles form posts on the plugin's
   * settings page.
   */
  public function settings()
  {
    $data = array();
    $errors = array();
    $message = null;

    // Retrieve settings from database
    $attributes = json_decode(Settings::get('ldap_attributes'));
    $data['ldap_attributes'] = implode(',', $attributes);
    $data['ldap_filter_prefix'] = Settings::get('ldap_filter_prefix');
    $data['ldap_uri'] = Settings::get('ldap_uri');
    $data['ldap_dn_string'] = Settings::get('ldap_dn_string');

    // Handle form if submitted
    if (isset($_POST['submitted'])) {
      // Validate form nonce token and submission speed
      $is_valid_form = LDAP::_validate_form_nonce();

      if ($is_valid_form) {
        // Validate attributes
        if (!empty($_POST['ldap_attributes'])) {
          $atts = explode(',', $_POST['ldap_attributes']);
          $data['ldap_attributes'] = json_encode($atts);
        } else {
          $errors['ldap_attributes'] = 'LDAP search attributes must be a comma separated list of valid directory attributes.';
        }
        // Validate filter prefix
        if (!empty($_POST['ldap_filter_prefix'])) {
          $data['ldap_filter_prefix'] = trim($_POST['ldap_filter_prefix']);
        } else {
          $errors['ldap_filter_prefix'] = 'Invalid/missing LDAP filter prefix. ';
        }
        // Validate LDAP URI
        if (!empty($_POST['ldap_uri'])) {
          $data['ldap_uri'] = trim($_POST['ldap_uri']);
        } else {
          $errors['ldap_uri'] = 'Invalid/missing LDAP URI string. ';
        }

        // Validate LDAP dn string 
        if (!empty($_POST['ldap_dn_string'])) {
          $data['ldap_dn_string'] = trim($_POST['ldap_dn_string']);
        } else {
          $errors['ldap_dn_string'] = 'Invalid/missing LDAP dn string. ';
        }
      } else {
        $errors['session'] = 'Expired or invalid session';
      }

      // Error check and update data
      LDAP::_handle_settings_form($data, $errors);

      // Set attribute form data back to csv for display/editing
      $data['ldap_attributes'] = trim($_POST['ldap_attributes']);
    }
    // Generate new form nonce
    $formNonce = md5(uniqid(rand(), true));
    $_SESSION['formNonce'] = $formNonce;
    $_SESSION['formTime'] = time();

    // Display form
    include(dirname(__FILE__) . '/settings_form.php');
  }

  /**
   * Check for form errors and save settings
   * 
   */
  private function _handle_settings_form($data, $errors)
  {
    if (empty($errors)) {
      foreach ($data as $key => $value) {
        Settings::set($key, $value);
      }
      $message = 'Settings have been updated.';
      $message_type = 'alert-success';
    } else {
      $message = 'The following errors were found. Please correct them and try again.';
      $message .= '<br /><br /> - ' . implode('<br /> - ', $errors);
      $message_type = 'alert-danger';
    }
  }

  /**
   * Validate settings form nonce token and submission speed
   * 
   */
  private function _validate_form_nonce()
  {
    if (
      !empty($_POST['nonce'])
      && !empty($_SESSION['formNonce'])
      && !empty($_SESSION['formTime'])
      && $_POST['nonce'] == $_SESSION['formNonce']
      && time() - $_SESSION['formTime'] >= 2
    ) {
      return true;
    } else {
      return false;
    }
  }



}
