<?php
//namespace's
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

//rota para rota status
$app->get("/admin/orders/:idorder/status", function ($idorder) {
	//verifica o login
	User::verifyLogin();
	//templamnte order
	$order = new Order();

	$order->get((int)$idorder);

	$page = new PageAdmin();
	//gera o template 
	$page->setTpl("order-status", [
		'order' => $order->getValues(),
		'status' => OrderStatus::listAll(),
		'msgSuccess' => Order::getSuccess(),
		'msgError' => Order::getError()
	]);
});

//rota para o status do pedido 
$app->post("/admin/orders/:idorder/status", function ($idorder) {
	// verificação do login
	User::verifyLogin();

	//Verificação do status ou se for vazio
	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		Order::setError("Informe o status atual.");
		//redireciona
		header("Location: /admin/orders/" . $idorder . "/status");
		exit;
	}

	$order = new Order();

	$order->get((int)$idorder);

	$order->setidstatus((int)$_POST['idstatus']);
	//salva
	$order->save();
	//mensagem 
	Order::setSuccess("Status atualizado.");
	//redireciona
	header("Location: /admin/orders/" . $idorder . "/status");
	exit;
});

//rota para deletar o pedido 
$app->get("/admin/orders/:idorder/delete", function ($idorder) {

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->delete();
	//redireciona
	header("Location: /admin/orders");
	exit;
});

//rota para o pedido de acorod com o id
$app->get("/admin/orders/:idorder", function ($idorder) {

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();

	$page = new PageAdmin();
	//gera o template 
	$page->setTpl("order", [
		'order' => $order->getValues(),
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts()
	]);
});

//rota para pedidos 
$app->get("/admin/orders", function () {

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {

		$pagination = Order::getPageSearch($search, $page);
	} else {

		$pagination = Order::getPage($page);
	}

	$pages = [];

	for ($x = 0; $x < $pagination['pages']; $x++) {

		array_push($pages, [
			'href' => '/admin/orders?' . http_build_query([
				'page' => $x + 1,
				'search' => $search
			]),
			'text' => $x + 1
		]);
	}

	$page = new PageAdmin();
	//template 
	$page->setTpl("orders", [
		"orders" => $pagination['data'],
		"search" => $search,
		"pages" => $pages
	]);
});
