<?php
//ROTAS PARA OS USUARIOS DA ADMIN
use \Hcode\PageAdmin;
use \Hcode\Model\User;

//rota para aletrar a senha pelo admin
$app->get("/admin/users/:iduser/password", function ($iduser) {
	//verificação do login
	// User::verifyLogin();
	//novo usuário
	$user = new User();
	//
	$user->get((int)$iduser);

	$page = new PageAdmin();
	//gera o template
	$page->setTpl("users-password", [
		"user" => $user->getValues(),
		"msgError" => User::getError(),
		"msgSuccess" => User::getSuccess()
	]);
});

//carrega via post
$app->post("/admin/users/:iduser/password", function ($iduser) {

	// User::verifyLogin();
	//verificação
	if (!isset($_POST['despassword']) || $_POST['despassword'] === '') {

		User::setError("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	//verificação
	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '') {

		User::setError("Preencha a confirmação da nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	//verificação
	if ($_POST['despassword'] !== $_POST['despassword-confirm']) {

		User::setError("Confirme corretamente as senhas.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	//novo usuário 
	$user = new User();
	//pega o id
	$user->get((int)$iduser);
	//update no BD
	$user->setPassword(User::getPasswordHash($_POST['despassword']));
	//mensagem 
	User::setSuccess("Senha alterada com sucesso.");
	//redireciona
	header("Location: /admin/users/$iduser/password");
	exit;
});

//rota para o usuário/admin
$app->get("/admin/users", function () {

	// User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	//verificações 
	if ($search != '') {

		$pagination = User::getPageSearch($search, $page);
	} else {

		$pagination = User::getPage($page);
	}

	$pages = [];
	//para adcionar os elementos na pagina
	for ($x = 0; $x < $pagination['pages']; $x++) {

		array_push($pages, [
			'href' => '/admin/users?' . http_build_query([
				'page' => $x + 1,
				'search' => $search
			]),
			'text' => $x + 1
		]);
	}

	$page = new PageAdmin();

	$page->setTpl("users", array(
		"users" => $pagination['data'],
		"search" => $search,
		"pages" => $pages
	));
});

$app->get("/admin/users/create", function () {

	// User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");
});

$app->get("/admin/users/:iduser/delete", function ($iduser) {

	// User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;
});

$app->get("/admin/users/:iduser", function ($iduser) {

	// User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user" => $user->getValues()
	));
});

$app->post("/admin/users/create", function () {

	// User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

	$_POST['despassword'] = User::getPasswordHash($_POST['despassword']);

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;
});

$app->post("/admin/users/:iduser", function ($iduser) {

	// User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
	exit;
});
