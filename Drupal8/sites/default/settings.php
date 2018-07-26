<?php

/**
 * @file
 * amazee.io Drupal 8 configuration file.
 *
 * You should not edit this file, please use environment specific files!
 * They are loaded in this order:
 * - all.settings.php
 *   For settings that should be applied to all environments (dev, prod, staging, docker, etc).
 * - all.services.yml
 *   For services that should be applied to all environments (dev, prod, staging, docker, etc).
 * - production.settings.php
 *   For settings only for the production environment.
 * - production.services.yml
 *   For services only for the production environment.
 * - development.settings.php
 *   For settings only for the development environment (devevlopment sites, docker).
 * - development.services.yml
 *   For services only for the development environment (devevlopment sites, docker).
 * - settings.local.php
 *   For settings only for the local environment, this file will not be commited in GIT!
 * - services.local.yml
 *   For services only for the local environment, this file will not be commited in GIT!
 *
 */

### amazee.io Database connection
if(getenv('AMAZEEIO_SITENAME')){
  $databases['default']['default'] = array(
    'driver' => 'mysql',
    'database' => getenv('AMAZEEIO_SITENAME'),
    'username' => getenv('AMAZEEIO_DB_USERNAME'),
    'password' => getenv('AMAZEEIO_DB_PASSWORD'),
    'host' => getenv('AMAZEEIO_DB_HOST'),
    'port' => getenv('AMAZEEIO_DB_PORT'),
    'prefix' => '',
  );
}

### amazee.io Solr connection
// WARNING: you have to create a search_api server having "solr" machine name at
// /admin/config/search/search-api/add-server to make this work.
if (getenv('AMAZEEIO_SOLR_HOST') && getenv('AMAZEEIO_SOLR_PORT')) {
  $config['search_api.server.solr']['backend_config']['connector_config']['host'] = getenv('AMAZEEIO_SOLR_HOST');
  $config['search_api.server.solr']['backend_config']['connector_config']['path'] = '/solr/';
  $config['search_api.server.solr']['backend_config']['connector_config']['core'] = getenv('AMAZEEIO_SOLR_CORE') ?: getenv('AMAZEEIO_SITENAME');
  $config['search_api.server.solr']['backend_config']['connector_config']['port'] = getenv('AMAZEEIO_SOLR_PORT');
  $config['search_api.server.solr']['backend_config']['connector_config']['http_user'] = (getenv('AMAZEEIO_SOLR_USER') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http']['http_user'] = (getenv('AMAZEEIO_SOLR_USER') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http_pass'] = (getenv('AMAZEEIO_SOLR_PASSWORD') ?: '');
  $config['search_api.server.solr']['backend_config']['connector_config']['http']['http_pass'] = (getenv('AMAZEEIO_SOLR_PASSWORD') ?: '');
  $config['search_api.server.solr']['name'] = 'AmazeeIO Solr - Environment: ' . getenv('AMAZEEIO_SITE_ENVIRONMENT');
}

### amazee.io Varnish & Reverse proxy settings
if (getenv('AMAZEEIO_VARNISH_HOSTS') && getenv('AMAZEEIO_VARNISH_SECRET')) {
  $varnish_hosts = explode(',', getenv('AMAZEEIO_VARNISH_HOSTS'));
  array_walk($varnish_hosts, function(&$value, $key) { $value .= ':6082'; });

  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = array_merge(explode(',', getenv('AMAZEEIO_VARNISH_HOSTS')), array('127.0.0.1'));

  $config['varnish.settings']['varnish_control_terminal'] = implode($varnish_hosts, " ");
  $config['varnish.settings']['varnish_control_key'] = getenv('AMAZEEIO_VARNISH_SECRET');
  $config['varnish.settings']['varnish_version'] = 4;
}

### amazee.io Redis connection if Drupal Redis module exists and PHP Redis Module is loaded
if (getenv('AMAZEEIO_REDIS_HOST') && file_exists($app_root . '/modules/contrib/redis') && extension_loaded('redis')){
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('AMAZEEIO_REDIS_HOST');
  $settings['redis.connection']['port'] = getenv('AMAZEEIO_REDIS_PORT');
  $settings['cache_prefix']['default'] = getenv('AMAZEEIO_SITENAME');
  # Do not set the cache during installations of Drupal
  if (!drupal_installation_attempted()) {
    $settings['cache']['default'] = 'cache.backend.redis';
    // Include the default example.services.yml from the module, which will
    // replace all supported backend services (that currently includes the cache tags
    // checksum service and the lock backends, check the file for the current list)
    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
    // Allow the services to work before the Redis module itself is enabled.
    $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
    // Manually add the classloader path, this is required for the container cache bin definition below
    // and allows to use it without the redis module being enabled.
    $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');
    // Use redis for container cache.
    // The container cache is used to load the container definition itself, and
    // thus any configuration stored in the container itself is not available
    // yet. These lines force the container cache to use Redis rather than the
    // default SQL cache.
    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => 'Drupal\redis\ClientFactory',
        ],
        'cache.backend.redis' => [
          'class' => 'Drupal\redis\Cache\CacheBackendFactory',
          'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
        ],
        'cache.container' => [
          'class' => '\Drupal\redis\Cache\PhpRedis',
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => 'Drupal\Component\Serialization\PhpSerialize',
        ],
      ],
    ];
  }
}

### Trusted Host Patterns, see https://www.drupal.org/node/2410395 for more information.
### If your site runs on multiple domains, you need to add these domains here
$settings['trusted_host_patterns'] = array(
  '^' . str_replace('.', '\.', getenv('AMAZEEIO_SITE_URL')) . '$',
);

### Temp directory
if (getenv('AMAZEEIO_TMP_PATH')) {
  $config['system.file']['path']['temporary'] = getenv('AMAZEEIO_TMP_PATH');
}

### Hash Salt
if (getenv('AMAZEEIO_HASH_SALT')) {
  $settings['hash_salt'] = getenv('AMAZEEIO_HASH_SALT');
}

// Settings for all environments
if (file_exists(__DIR__ . '/all.settings.php')) {
  include __DIR__ . '/all.settings.php';
}

// Services for all environments
if (file_exists(__DIR__ . '/all.services.yml')) {
  $settings['container_yamls'][] = __DIR__ . '/all.services.yml';
}

if(getenv('AMAZEEIO_SITE_ENVIRONMENT')){
  // Environment specific settings files.
  if (file_exists(__DIR__ . '/' . getenv('AMAZEEIO_SITE_ENVIRONMENT') . '.settings.php')) {
    include __DIR__ . '/' . getenv('AMAZEEIO_SITE_ENVIRONMENT') . '.settings.php';
  }

  // Environment specific services files.
  if (file_exists(__DIR__ . '/' . getenv('AMAZEEIO_SITE_ENVIRONMENT') . '.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/' . getenv('AMAZEEIO_SITE_ENVIRONMENT') . '.services.yml';
  }
}

// Last: this servers specific settings files.
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
// Last: This server specific services file.
if (file_exists(__DIR__ . '/services.local.yml')) {
  $settings['container_yamls'][] = __DIR__ . '/services.local.yml';
}
