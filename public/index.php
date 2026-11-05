<?php
/**
 * ResourceHub Front Controller
 * Apache DocumentRoot entry point.
 */

require_once __DIR__ . '/../includes/init.php';

(new Router())->dispatch();
