# LDAP for CumulusClips

Connects CumulusClips to an LDAP directory service, allowing user metadata to be stored automatically. This plugin does not handle authentication via LDAP.  

## Default User Meta

Directory information includes the following, by default (and where available):

* homedirectory - a user's default home directory path, such as /users/j/s/jsmith.
* ou - the user's organization/unit/department/etc.
* title - primary title
* primaryAffiliation - user's affiliaton


