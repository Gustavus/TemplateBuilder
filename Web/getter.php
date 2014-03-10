<?php
/**
 * @package TemplateBuilder
 * @author  Billy Visto
 */

use Gustavus\TemplateBuilder\Getter;

if (isset($_POST['templateProperties'])) {
  $properties = json_decode(rawurldecode($_POST['templateProperties']), true);
} else if (isset($_GET['templateProperties'])) {
  $properties = json_decode(rawurldecode($_GET['templateProperties']), true);
} else {
  $properties = array();
}

echo Getter::render($properties);