<?php

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
