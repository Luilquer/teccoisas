<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

// formata o preço
function formatPrice($vlprice)
{

	if (!$vlprice > 0) $vlprice = 0;

	// padrão R$
	return number_format($vlprice, 2, ",", ".");
}

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

function getCartNrQtd()
{

	$cart = Cart::getFromSession();

	$totals = $cart->getProductsTotals();

	return $totals['nrqtd'];
}

function getCartVlSubTotal()
{

	$cart = Cart::getFromSession();

	$totals = $cart->getProductsTotals();

	return formatPrice($totals['vlprice']);
}
