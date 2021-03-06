<?php
//ROTAS PARA O SITE
use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

//lista todos os produtos que estão no banco
$app->get('/', function () {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
		'products' => Product::checkList($products)
	]);
});

//exibe todos os produtos cadastrados no banco de dados 
$app->get("/categories/:idcategory", function ($idcategory) {

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i = 1; $i <= $pagination['pages']; $i++) {
		//adiciona os dados relacionados a paginação 
		array_push($pages, [
			'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
			'page' => $i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category' => $category->getValues(),
		'products' => $pagination["data"],
		'pages' => $pages
	]);
});

//rota para exibir detalhes dos produtos 
$app->get("/products/:desurl", function ($desurl) {

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product' => $product->getValues(),
		'categories' => $product->getCategories()
	]);
});

//rota para o carrinho 
$app->get("/cart", function () {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts(),
		'error' => Cart::getMsgError()
	]);
});

//adicionar produtos no carrinho 
$app->get("/cart/:idproduct/add", function ($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);
	//recuperar o carrinho ou cria um novo 
	$cart = Cart::getFromSession();
	//verifica se foi informado a quantidade de produtos 
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	//for para adcionar quantas vezes for necessário 
	for ($i = 0; $i < $qtd; $i++) {

		$cart->addProduct($product);
	}
	//redireciona para o carrinho 
	header("Location: /cart");
	exit;
});

//remove apenas um produto do carrinho 
$app->get("/cart/:idproduct/minus", function ($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	//remove apenas um 
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});

//remove todos 
$app->get("/cart/:idproduct/remove", function ($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	//remove todos 
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});

//rota para cálculo do frete 
$app->post("/cart/freight", function () {
	//
	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

//rota para logar apos a compra
$app->get("/checkout", function () {
	//validação do login
	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();
	//verificação do cep
	if (!isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();
	}

	if (isset($_GET['zipcode'])) {

		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal();
	}
	//validação dos campos 
	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdesnumber()) $address->setdesnumber('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [
		'cart' => $cart->getValues(),
		'address' => $address->getValues(),
		'products' => $cart->getProducts(),
		'error' => Address::getMsgError()
	]);
});


//
$app->post("/checkout", function () {

	User::verifyLogin(false);

	//verificação dos campos, cep
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");
		header('Location: /checkout');
		exit;
	}
	//verificação endereço
	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;
	}
	//verifica bairro
	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;
	}
	//cidade
	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;
	}
	//UF
	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;
	}
	//País
	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;
	}

	//pega o usuario logaod 
	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();
	//seta os dados 
	$address->setData($_POST);
	//salva
	$address->save();
	//pega o id do carrinho 
	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();
	//novo objeto order
	$order = new Order();
	//seta os dados 
	$order->setData([
		'idcart' => $cart->getidcart(),
		'idaddress' => $address->getidaddress(),
		'iduser' => $user->getiduser(),
		'idstatus' => OrderStatus::EM_ABERTO,
		'vltotal' => $cart->getvltotal()
	]);
	//salva o pedido
	$order->save();

	switch ((int)$_POST['payment-method']) {

		case 1:
			header("Location: /order/" . $order->getidorder() . "/pagseguro");
			break;

		case 2:
			header("Location: /order/" . $order->getidorder() . "/paypal");
			break;
	}

	exit;
});

//Rota para pagamentos 
//pedido
$app->get("/order/:idorder/pagseguro", function ($idorder) {
	//verifica o login
	User::verifyLogin(false);

	$order = new Order();
	//carrega pelo id 
	$order->get((int)$idorder);

	$cart = $order->getCart();

	$page = new Page([
		'header' => false,
		'footer' => false
	]);
	//carrega o template 
	$page->setTpl("payment-pagseguro", [
		'order' => $order->getValues(),
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts(),
		'phone' => [
			'areaCode' => substr($order->getnrphone(), 0, 2),
			'number' => substr($order->getnrphone(), 2, strlen($order->getnrphone()))
		]
	]);
});

$app->get("/order/:idorder/paypal", function ($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();

	$page = new Page([
		'header' => false,
		'footer' => false
	]);

	$page->setTpl("payment-paypal", [
		'order' => $order->getValues(),
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts()
	]);
});

//Login usuário
$app->get("/login", function () {

	$page = new Page();

	//passa o erro para o template para
	//exibe a mensagme de erro durante o registro 
	//verificação dos dados dos campos 
	$page->setTpl("login", [
		'error' => User::getError(),
		'errorRegister' => User::getErrorRegister(),
		'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']
	]);
});

//login via post
$app->post("/login", function () {
	//tenta verificar o login
	try {
		//verifica o login
		User::login($_POST['login'], $_POST['password']);
	} catch (Exception $e) {
		//e for invalido, retorna um erro 
		User::setError($e->getMessage());
	}
	//redireciona para checkout
	header("Location: /checkout");
	exit;
});

//rota para sair 
$app->get("/logout", function () {

	User::logout();
	//redireciona para tela de login
	header("Location: /login");
	exit;
});

//novo usuário, registro 
$app->post("/register", function () {

	//armazena em um array, dados 
	$_SESSION['registerValues'] = $_POST;

	//verificação do nome 
	if (!isset($_POST['name']) || $_POST['name'] == '') {

		User::setErrorRegister("Preencha o seu nome.");
		//redireciona
		header("Location: /login");
		exit;
	}

	//verificação do email
	if (!isset($_POST['email']) || $_POST['email'] == '') {

		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;
	}
	//verificação da senha
	if (!isset($_POST['password']) || $_POST['password'] == '') {

		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;
	}
	//verificação de dois usuários semelhantes
	if (User::checkLoginExist($_POST['email']) === true) {

		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;
	}
	//cria um novo usuario
	$user = new User();
	//passa os dados via post
	$user->setData([
		'inadmin' => 0,
		'deslogin' => $_POST['email'],
		'desperson' => $_POST['name'],
		'desemail' => $_POST['email'],
		'despassword' => $_POST['password'],
		'nrphone' => $_POST['phone']
	]);
	//salva o usuário 
	$user->save();
	//faz avalidação e loga, passando o email e senha 
	User::login($_POST['email'], $_POST['password']);
	//redireciona 
	header('Location: /checkout');
	exit;
});

//rota para esqueceu a senha 
$app->get("/forgot", function () {

	$page = new Page();

	$page->setTpl("forgot");
});

//rota para retornar os dados via post 
$app->post("/forgot", function () {

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;
});

$app->get("/forgot/sent", function () {

	$page = new Page();

	$page->setTpl("forgot-sent");
});


$app->get("/forgot/reset", function () {

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name" => $user["desperson"],
		"code" => $_GET["code"]
	));
});

$app->post("/forgot/reset", function () {

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setFogotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = User::getPasswordHash($_POST["password"]);

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success");
});

//rota para o perfil
$app->get("/profile", function () {

	// User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		'user' => $user->getValues(),
		'profileMsg' => User::getSuccess(),
		'profileError' => User::getError()

	]);
});

//rota post para validar os campos 
$app->post("/profile", function () {

	// User::verifyLogin(false);
	//campos orbigatórios 
	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Preencha o seu nome.");
		header('Location: /profile');
		exit;
	}
	//campos orbigatórios 
	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	$user = User::getFromSession();

	if ($_POST['desemail'] !== $user->getdesemail()) {

		if (User::checkLoginExist($_POST['desemail']) === true) {

			User::setError("Este endereço de e-mail já está cadastrado.");
			header('Location: /profile');
			exit;
		}
	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->save();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;
});

//Rota para pedidos 
$app->get("/order/:idorder", function ($idorder) {

	// User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$page = new Page();

	$page->setTpl("payment", [
		'order' => $order->getValues()
	]);
});

//rota para gerar o boleto
$app->get("/boleto/:idorder", function ($idorder) {
	//verificação do usuáro
	// User::verifyLogin(false);
	//carrega o pedido
	$order = new Order();
	//carrega pelo id 
	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".", $valor_cobrado);
	$valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja TecCoisas E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@teccoisas.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja TecCoisas E-commerce - www.teccoisas.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "TecCoisas E-commerce";
	$dadosboleto["cpf_cnpj"] = "77.777.777/0007-07";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "Cidade Tec - BA";
	$dadosboleto["cedente"] = "TECCOISAS E-COMMERCE LTDA - ME";

	// NÃO ALTERAR! - caminho para gerar o boleto 
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
	//gera o boleto 
	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
});

//rotas para verificar pedidos realizados 
$app->get("/profile/orders", function () {
	//verificação do login
	// User::verifyLogin(false);
	//pega o id do usuário 
	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders", [
		'orders' => $user->getOrders()
	]);
});

//rota para detalhes do pedido 
$app->get("/profile/orders/:idorder", function ($idorder) {

	// User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();
	//pega o carrinho do pedido em questão 
	$cart->get((int)$order->getidcart());
	//calcula o total 
	$cart->getCalculateTotal();

	$page = new Page();
	//gera o template para o detalhes do pedido
	$page->setTpl("profile-orders-detail", [
		'order' => $order->getValues(),
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts()
	]);
});

//para mudar senha
$app->get("/profile/change-password", function () {
	//verificação do usuário 
	// User::verifyLogin(false);

	$page = new Page();
	//gera o template, passando as mensagens de erro/sucesso
	$page->setTpl("profile-change-password", [
		'changePassError' => User::getError(),
		'changePassSuccess' => User::getSuccess()
	]);
});
//
$app->post("/profile/change-password", function () {
	//verificação do login
	// User::verifyLogin(false);
	//verifica se a senha atual foi definida ou se esta vazio
	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;
	}
	//verifica se a nova senha foi definid ou se esta vazia
	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {

		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}
	//verificação do comfirmação de senha 
	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {

		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}
	//verifica se a nova senha é a mesma da atual 
	if ($_POST['current_pass'] === $_POST['new_pass']) {

		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;
	}
	//pega o id do usuário
	$user = User::getFromSession();
	//verificação da senha 
	if (!password_verify($_POST['current_pass'], $user->getdespassword())) {

		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;
	}
	//altera a senha 
	$user->setdespassword($_POST['new_pass']);
	//update no banco 
	$user->update();
	//mensagem 
	User::setSuccess("Senha alterada com sucesso.");
	//redireciona
	header("Location: /profile/change-password");
	exit;
});
