<?php

### Base URL
if (getenv('AMAZEEIO_BASE_URL')) {
  $options['uri'] = getenv('AMAZEEIO_BASE_URL');
}