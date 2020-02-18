# LDAP for CumulusClips

Provides access to an LDAP directory service for other plugins.    This plugin does not handle authentication via LDAP.  

## Usage 

```php
  if( class_exists('LDAP') ) {
    // Get directory entry for this user
		$directoryEntry = LDAP::get_entry($userName);
  }
```

