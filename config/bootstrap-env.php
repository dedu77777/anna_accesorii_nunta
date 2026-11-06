<?php

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createUnsafeImmutable($root);
    $dotenv->safeLoad();
}