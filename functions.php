<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

// formata o preço
function formatPrice($vlprice)
{
	//verifica se não é maior que zero 
	if (!$vlprice > 0) $vlprice = 0;

	// padrão R$
	return number_format($vlprice, 2, ",", ".");
}

//formata a data no padrão BR
function formatDate($date)
{

	return date('d/m/Y', strtotime($date));
}

//checa o login
function checkLogin($inadmin = true)
{

	return User::checkLogin($inadmin);
}

//retorna o usuário logado 
function getUserName()
{

	$user = User::getFromSession();

	return $user->getdesperson();
}

//pega o total do carrinho 
function getCartNrQtd()
{
	//pega o id do carrinho 
	$cart = Cart::getFromSession();
	//pega os produtos, total 
	$totals = $cart->getProductsTotals();
	//retorna o total
	return $totals['nrqtd'];
}

//calculo do subtotal 
function getCartVlSubTotal()
{
	//pega o id do carrinho 
	$cart = Cart::getFromSession();
	//total
	$totals = $cart->getProductsTotals();
	//o valor formatado
	return formatPrice($totals['vlprice']);
}
