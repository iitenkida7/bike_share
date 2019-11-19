<?php
require_once(__DIR__ . '/../vendor/autoload.php');
use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__ . '/../');
$dotenv->load();

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'db',
    'database'  =>  getenv('MYSQL_DATABASE'),
    'username'  =>  getenv('MYSQL_USER'),
    'password'  =>  getenv('MYSQL_ROOT_PASSWORD'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

