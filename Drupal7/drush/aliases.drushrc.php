<?php

$sitename = '';   // use the username on our servers like testsite_ch
$options['deploy-repository'] = ''; // git repository that should be deployed from, like git@github.com:org/site.git

// Don't change anything here, it's magic!
global $aliases_stub;
if (empty($aliases_stub)) {
  $aliases_stub = file_get_contents('https://raw.githubusercontent.com/amazeeio/drush-aliases/master/aliases.drushrc.php.stub?' . rand(0, 99999999999));
}
eval($aliases_stub);
