<?php

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;

$app = new Slim();

$app->config('debug', true);

//rota para a pagina home
$app->get('/', function () {

	$page = new Page();

	$page->setTpl("index");
});

//rota para a pagina do admin
$app->get('/admin', function () {

	$page = new PageAdmin();

	$page->setTpl("index");
});

$app->run();
