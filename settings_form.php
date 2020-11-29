<h1>LDAP Settings</h1>

<?php if ($message): ?>
<div class="alert <?=$message_type?>"><?=$message?></div>
<?php endif; ?>

<form method="post">

    <div class="form-group <?=(isset ($errors['ldap_attributes'])) ? 'has-error' : ''?>">
        <label class="control-label">LDAP Attributes:</label>
        <p>This should be a comma separated list of attributes to retrieve from search queries, i.e. <code>homeDirectory, givenName, sn, ou, labeledURI, mail, eduPersonPrimaryAffiliation</code></p>
        <input class="form-control" type="text" name="ldap_attributes" value="<?=$data['ldap_attributes']?>" placeholder="homeDirectory,givenName,sn,ou,labeledURI,mail,eduPersonPrimaryAffiliation" />
    </div>
    <div class="form-group <?=(isset ($errors['ldap_filter_prefix'])) ? 'has-error' : ''?>">
        <label class="control-label">LDAP Filter Prefix:</label>
        <input class="form-control" type="text" name="ldap_filter_prefix" value="<?=$data['ldap_filter_prefix']?>" placeholder="netid" />
    </div>
    <div class="form-group <?=(isset ($errors['ldap_uri'])) ? 'has-error' : ''?>">
        <label class="control-label">LDAP URI:</label>
        <input class="form-control" type="text" name="ldap_uri" value="<?=$data['ldap_uri']?>" placeholder="ldaps://ldap.your.edu" />
    </div>
    <div class="form-group <?=(isset ($errors['ldap_dn_string'])) ? 'has-error' : ''?>">
        <label class="control-label">LDAP dn string:</label>
        <input class="form-control" type="text" name="ldap_dn_string" value="<?=$data['ldap_dn_string']?>" placeholder="dc=your,dc=edu" />
    </div>
    <input type="hidden" value="yes" name="submitted" />
    <input type="hidden" name="nonce" value="<?=$formNonce?>" />
    <input type="submit" class="button" value="Update Settings" />

</form>
