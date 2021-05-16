<?php
declare(strict_types=1);
require_once(dirname(dirname(($p = realpath(__FILE__)) === false ? __FILE__ : $p)) . '/bootstrap.php');

/* Run application */
(new \ix\Application\Application())->create_app()->run();
