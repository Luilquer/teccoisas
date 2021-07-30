<?php

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new Slim();

$app->config('debug', true);

// require_once("functions.php");
require_once("site.php");
require_once("admin.php");
require_once("admin-users.php");
require_once("admin-categories.php");
require_once("admin-products.php");
// require_once("admin-orders.php");

$app->run();

?>




<!-- <?php 
// session_start();
// require_once("vendor/autoload.php");

// use \Slim\Slim;
// use \Hcode\Page;
// use \Hcode\PageAdmin;
// use \Hcode\Model\User;
// use \Hcode\Model\Category;

// $app = new Slim();

// $app->config('debug', true);

// //rota para a pagina home
// $app->get('/', function () {

// 	$page = new Page();

// 	$page->setTpl("index");
// });

// //rota para a pagina do admin
// $app->get('/admin', function () {

// 	//método estático para verificar se está logado ou não 
// 	User::verifyLogin();

// 	$page = new PageAdmin();

// 	$page->setTpl("index");
// });

// //rota para a pagina login
// $app->get('/admin/login', function () {

// 	$page = new PageAdmin([
// 		"header" => false,
// 		"footer" => false
// 	]);

// 	$page->setTpl("login");
// });


// $app->post('/admin/login', function () {

// 	User::login($_POST["login"], $_POST["password"]);

// 	header("Location: /admin");
// 	exit;
// });

// //rota para o logaut
// $app->get('/admin/logout', function () {

// 	User::logout();

// 	header("Location: /admin/login");

// 	exit;
// });


// //**Rota que peguei do bloco de notas */
// // $app->post("/admin/users/create", function () {

// // 	User::verifyLogin();

// // 	$user = new User();

// // 	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

// // 	$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [

// // 		"cost" => 12

// // 	]);

// // 	$user->setData($_POST);

// // 	$user->save();

// // 	header("Location: /admin/users");
// // 	exit;
// // });

// //rota u
// $app->get("/admin/users", function () {

// 	User::verifyLogin();

// 	$users = User::listAll();

// 	$page = new PageAdmin();

// 	$page->setTpl("users", array(
// 		"users" => $users
// 	));
// });


// $app->get("/admin/users/create", function () {

// 	User::verifyLogin();

// 	$page = new PageAdmin();

// 	$page->setTpl("users-create");
// });

// //rota para deletar
// $app->get("/admin/users/:iduser/delete", function ($iduser) {

// 	User::verifyLogin();

// 	$user = new User();

// 	$user->get((int)$iduser);

// 	$user->delete();

// 	header("Location: /admin/users");
// 	exit;
// });


// $app->get("/admin/users/:iduser", function ($iduser) {

// 	User::verifyLogin();

// 	$user = new User();

// 	$user->get((int)$iduser);

// 	$page = new PageAdmin();

// 	$page->setTpl("users-update", array(
// 		"user" => $user->getValues()
// 	));
// });

// $app->post("/admin/users/create", function () {

// 	User::verifyLogin();

// 	$user = new User();

// 	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

// 	$user->save();

// 	header("Location: /admin/users");
// 	exit;
// });

// $app->post("/admin/users/:iduser", function ($iduser) {

// 	User::verifyLogin();

// 	$user = new User();

// 	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

// 	$user->get((int)$iduser);

// 	$user->setData($_POST);

// 	$user->update();

// 	header("Location: /admin/users");
// 	exit;
// });

// // ROTAS PARA REDEFINIR SENHA 
// $app->get("/admin/forgot", function () {

// 	$page = new PageAdmin([
// 		"header" => false,
// 		"footer" => false
// 	]);

// 	$page->setTpl("forgot");
// });

// $app->post("/admin/forgot", function () {


// 	$user = User::getForgot($_POST["email"]);

// 	header("Location: /admin/forgot/sent");
// 	exit;
// });

// $app->get("/admin/forgot/sent", function () {

// 	$page = new PageAdmin([
// 		"header" => false,
// 		"footer" => false
// 	]);

// 	$page->setTpl("forgot-sent");
// });


// $app->get("/admin/forgot/reset", function () {

// 	$user = User::validForgotDecrypt($_GET["code"]);

// 	$page = new PageAdmin([
// 		"header" => false,
// 		"footer" => false
// 	]);

// 	$page->setTpl("forgot-reset", array(
// 		"name" => $user["desperson"],
// 		"code" => $_GET["code"]
// 	));
// });

// $app->post("/admin/forgot/reset", function () {

// 	$forgot = User::validForgotDecrypt($$_POST["code"]);

// 	User::setFogotUsed($forgot["idrecovery"]);

// 	$user = new User();

// 	$user->get((int)$forgot["iduser"]);

// 	//criptrografia da senha 
// 	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
// 		"cost" => 12
// 	]);

// 	$user->setPassword($password);

// 	$page = new PageAdmin([
// 		"header" => false,
// 		"footer" => false
// 	]);

// 	$page->setTpl("forgot-reset-success");
// });
// //********* */

// //ROTAS PARA A TABELA CATEGORIAS

// $app->get("/admin/categories", function () {

// 	User::verifyLogin();

// 	$categories = Category::listAll();

// 	$page = new PageAdmin();
// 	//chama o template
// 	$page->setTpl("categories", [
// 		'categories' => $categories
// 	]);
// });


// $app->get("/admin/categories/create", function () {

// 	User::verifyLogin();

// 	$page = new PageAdmin();
// 	//chama o template
// 	$page->setTpl("categories-create");
// });

// $app->post("/admin/categories/create", function () {

// 	User::verifyLogin();

// 	$category = new Category();
// 	//chama o template
// 	$category->setData($_POST);

// 	$category->save();

// 	header('Location: /admin/categories');
// 	exit;
// });

// $app->get("/admin/categories/:idcategory/delete", function ($idcategory) {

// 	User::verifyLogin();

// 	$category = new Category();
// 	//cahama o método para listar 
// 	$category->get((int)$idcategory);
// 	//método para deletar
// 	$category->delete();
// 	//redireciona
// 	header('Location: /admin/categories');
// 	exit;
// });

// $app->get("/admin/categories/:idcategory", function ($idcategory) {

// 	User::verifyLogin();

// 	$category = new Category();

// 	$category->get((int)$idcategory);

// 	$page = new PageAdmin();
// 	//chama o template
// 	$page->setTpl("categories-update", [
// 		'category' => $category->getValues()
// 	]);
// });

// $app->post("/admin/categories/:idcategory", function ($idcategory) {

// 	User::verifyLogin();

// 	$category = new Category();

// 	$category->get((int)$idcategory);
// 	//carrega os dados recebidos no formulario
// 	$category->setData($_POST);
// 	//salva os dados no banco de dados 
// 	$category->save();

// 	//redireciona
// 	header('Location: /admin/categories');
// 	exit;
// });

// //ROTAS PARA EXIBIR NO BANCO DE DADOS 

// $app->get("/categories/:idcategory", function ($idcategory) {

// 	$category = new Category();

// 	$category->get((int)$idcategory);

// 	$page = new Page();

// 	$page->setTpl("category", [

// 		'category' => $category->getValues(),
// 		'products' =>[]

// 	]);
// });


// $app->run(); -->
