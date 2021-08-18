<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;

//classe para finalizar os pedidos 
class Order extends Model
{

	const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";

	//salva os dados 
	public function save()
	{

		$sql = new Sql();
		//consulta no banco 
		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder' => $this->getidorder(),
			':idcart' => $this->getidcart(),
			':iduser' => $this->getiduser(),
			':idstatus' => $this->getidstatus(),
			':idaddress' => $this->getidaddress(),
			':vltotal' => $this->getvltotal()
		]);
		//veridicação se retornou alto 
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}
	//retorna os dados do BD
	public function get($idorder)
	{

		$sql = new Sql();
		//select no banco de dados 
		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder
		", [
			':idorder' => $idorder
		]);
		//verifica se encontrou algo 
		if (count($results) > 0) {
			//seta os dados 
			$this->setData($results[0]);
		}
	}

	//lista todos os pedidos do banco de dados 
	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
		");
	}

	//deleta os pedidos de acorodo com a ID
	public function delete()
	{

		$sql = new Sql();
		//consulta no banco, deleta
		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
			':idorder' => $this->getidorder()
		]);
	}

	//pega o carrinho 
	public function getCart(): Cart
	{

		$cart = new Cart();

		$cart->get((int)$this->getidcart());

		return $cart;
	}

	//Mensagens de Erros/seta
	public static function setError($msg)
	{

		$_SESSION[Order::ERROR] = $msg;
	}
	//retorna a mensagem de erro 
	public static function getError()
	{

		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

		Order::clearError();

		return $msg;
	}
	//limpa a mensagem de erro 
	public static function clearError()
	{

		$_SESSION[Order::ERROR] = NULL;
	}

	public static function setSuccess($msg)
	{

		$_SESSION[Order::SUCCESS] = $msg;
	}

	public static function getSuccess()
	{

		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

		Order::clearSuccess();

		return $msg;
	}

	public static function clearSuccess()
	{

		$_SESSION[Order::SUCCESS] = NULL;
	}

	//retorna a pagina 
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => $results,
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
	//pesquisa por pedido 
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :id OR f.desperson LIKE :search
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		", [
			':search' => '%' . $search . '%',
			':id' => $search
		]);



		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => $results,
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
}
