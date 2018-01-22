<?php

/**
 * @file
 * Lagoon Drupal 7 configuration file.
 *
 * You should not edit this file, please use environment specific files!
 * They are loaded in this order:
 * - settings.all.php
 *   For settings that should be applied to all environments (dev, prod, staging, docker, etc).
 * - settings.production.php
 *   For settings only for the production environment.
 * - settings.development.php
 *   For settings only for the development environment (dev servers, docker).
 * - settings.local.php
 *   For settings only for the local environment, this file will not be commited in GIT!
 *
 */

 ### Lagoon Database connection
 if(getenv('LAGOON')){
   $mariadb_port = preg_replace('/.*:(\d{2,5})$/', '$1', getenv('MARIADB_PORT') ?: '3306'); // Kubernetes/OpenShift sets `*_PORT` by default as tcp://172.30.221.159:8983, extract the port from it
   $databases['default']['default'] = array(
     'driver' => 'mysql',
     'database' => getenv('MARIADB_DATABASE') ?: 'drupal',
     'username' => getenv('MARIADB_USERNAME') ?: 'drupal',
     'password' => getenv('MARIADB_PASSWORD') ?: 'drupal',
     'host' => getenv('MARIADB_HOST') ?: 'mariadb',
     'port' => $mariadb_port,
     'prefix' => '',
   );
 }


### amazee.io solr connection (will only be loaded if solr is enabled)
if (getenv('LAGOON')) {
  $solr_port = preg_replace('/.*:(\d{2,5})$/', '$1', getenv('SOLR_PORT') ?: '8983') ;
  // Override search API server settings fetched from default configuration.
  $conf['search_api_override_mode'] = 'load';
  $conf['search_api_override_servers'] = array(
    'solr' => array(
      'name' => 'amazee.io Solr - Environment:' . getenv('LAGOON_PROJECT'),
      'options' => array(
        'host' => getenv('SOLR_HOST'),
        'port' => getenv('SOLR_PORT'),
        'path' => '/solr/' . getenv('SOLR_CORE') ?: 'drupal' . '/',
        'http_user' => (getenv('SOLR_USER') ?: ''),
        'http_pass' => (getenv('SOLR_PASSWORD') ?: ''),
        'excerpt' => 0,
        'retrieve_data' => 0,
        'highlight_data' => 0,
        'http_method' => 'POST',
      ),
    ),
  );
}

### amazee.io Varnish & reverse proxy settings
if (getenv('LAGOON')) {
  $varnish_control_port = getenv('VARNISH_CONTROL_PORT') ?: '6082';
  $varnish_hosts = explode(',', getenv('VARNISH_HOSTS'));
  array_walk($varnish_hosts, function(&$value, $key) { $value .= ":$varnish_control_port"; });

  $conf['reverse_proxy'] = TRUE;
  $conf['reverse_proxy_addresses'] = array_merge(explode(',', getenv('AMAZEEIO_VARNISH_HOSTS')), array('127.0.0.1'));
  $conf['varnish_control_terminal'] = implode($varnish_hosts, " ");
  $conf['varnish_control_key'] = getenv('VARNISH_SECRET') ?: 'lagoon_default_secret';
  $conf['varnish_version'] = 4;
}

### Temp directory
if (getenv('TMP')) {
  $config['system.file']['path']['temporary'] = getenv('TMP');
}

### Hash Salt
if (getenv('LAGOON')) {
  $settings['hash_salt'] = hash('sha256', getenv('LAGOON_PROJECT'));
}

// Loading settings for all environment types.
if (file_exists(__DIR__ . '/all.settings.php')) {
  include __DIR__ . '/all.settings.php';
}

if(getenv('LAGOON_ENVIRONMENT_TYPE')){
  // Environment specific settings files.
  if (file_exists(__DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php')) {
    include __DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.settings.php';
  }

  // Environment specific services files.
  if (file_exists(__DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/' . getenv('LAGOON_ENVIRONMENT_TYPE') . '.services.yml';
  }
}

// Last: this servers specific settings files.
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
