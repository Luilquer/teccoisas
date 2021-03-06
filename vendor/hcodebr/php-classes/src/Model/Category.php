<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

//classe para exibir as categorias armazenadas no banoc de dados 
class Category extends Model
{

	//lista todos os dados 
	public static function listAll()
	{

		$sql = new Sql();
		//consulta no banco de dados 
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
	}

	//método para salvar no banco de dados 
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory" => $this->getidcategory(),
			":descategory" => $this->getdescategory()
		));

		$this->setData($results[0]);

		//chma o método para atualizar
		Category::updateFile();
	}

	//método para listar de acorod com o id
	public function get($idcategory)
	{

		$sql = new Sql();
		//consulta no banco de dados 
		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory' => $idcategory
		]);
		//retorna os dados
		$this->setData($results[0]);
	}


	//método para deletar dados do banco de dados 
	public function delete()
	{

		$sql = new Sql();
		//consulta no banco, para deletar
		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory' => $this->getidcategory()
		]);
		//chma o método para atualizar
		Category::updateFile();
	}

	//atualiza os dados dos arquivos no banco de dados 
	public static function updateFile()
	{

		$categories = Category::listAll();

		$html = [];
		//pega cada registro e armazena 
		foreach ($categories as $row) {
			array_push($html, '<li><a href="/categories/' . $row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
		}
		//salva o arquivo, passando o caminho dinamicamente
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
	}

	//carrega todos os produtos 
	public function getProducts($related = true)
	{

		$sql = new Sql();

		if ($related === true) {
			//consulta no banco de dados, produtos relacionados 
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN(
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory' => $this->getidcategory()
			]);
		} else {

			//consulta dos produtos que não estão relacionados 
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN(
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory' => $this->getidcategory()
			]);
		}
	}

	// carrega os produtos de acordo com a pagina
	public function getProductsPage($page = 1, $itemsPerPage = 8)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", [
			':idcategory' => $this->getidcategory()
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => Product::checkList($results),
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//adiciona produtos 
	public function addProduct(Product $product)
	{

		$sql = new Sql();
		//insere na tabela produtos 
		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
			':idcategory' => $this->getidcategory(),
			':idproduct' => $product->getidproduct()
		]);
	}
	//remove produtos 
	public function removeProduct(Product $product)
	{

		$sql = new Sql();
		//deleta produto da tabela 
		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
			':idcategory' => $this->getidcategory(),
			':idproduct' => $product->getidproduct()
		]);
	}
	//retorna a pagina
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => $results,
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
	//pesquisa
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			WHERE descategory LIKE :search
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		", [
			':search' => '%' . $search . '%'
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => $results,
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
}
