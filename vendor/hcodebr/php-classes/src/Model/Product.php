<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

//classe para listar os produtos, excluir, e atualizar
class Product extends Model
{

	//lista todos os produtos 
	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
	}

	//checa a lista de produtos 
	public static function checkList($list)
	{

		foreach ($list as &$row) {

			$p = new Product();
			$p->setData($row);
			$row = $p->getValues();
		}
		//retorna com os dados formatados 
		return $list;
	}

	//salva todos os produtos 
	public function save()
	{

		$sql = new Sql();

		//consulta no banco de dados 
		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
			":idproduct" => $this->getidproduct(),
			":desproduct" => $this->getdesproduct(),
			":vlprice" => $this->getvlprice(),
			":vlwidth" => $this->getvlwidth(),
			":vlheight" => $this->getvlheight(),
			":vllength" => $this->getvllength(),
			":vlweight" => $this->getvlweight(),
			":desurl" => $this->getdesurl()
		));

		$this->setData($results[0]);
	}

	//retorna o produto pelo id
	public function get($idproduct)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct' => $idproduct
		]);

		$this->setData($results[0]);
	}
	//deleta no banco 
	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct' => $this->getidproduct()
		]);
	}

	//verifica se na pasta existe o arquivo, em seguida salva a foto
	public function checkPhoto()
	{

		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
				"res" . DIRECTORY_SEPARATOR .
				"site" . DIRECTORY_SEPARATOR .
				"img" . DIRECTORY_SEPARATOR .
				"products" . DIRECTORY_SEPARATOR .
				$this->getidproduct() . ".jpg"
		)) {
			//caminho da pasta para armazenar as fotos 
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
		} else {
			//retona uma imagem cinza
			$url = "/res/site/img/product.jpg";
		}

		return $this->setdesphoto($url);
	}

	//pega os dados de acordo com o banco de dados 
	public function getValues()
	{
		//chama o método para verificar a foto
		$this->checkPhoto();

		$values = parent::getValues();

		return $values;
	}

	//atualiza a foto no banco de dados 
	public function setPhoto($file)
	{
		//procura onde tem um ponto e retorna um array
		$extension = explode('.', $file['name']);
		$extension = end($extension);

		switch ($extension) {

				//converte para jpg, caso necessário. Em seguida pega o arquivo e armazena 
			case "jpg":
			case "jpeg":
				$image = imagecreatefromjpeg($file["tmp_name"]);
				break;

			case "gif":
				$image = imagecreatefromgif($file["tmp_name"]);
				break;

			case "png":
				$image = imagecreatefrompng($file["tmp_name"]);
				break;
		}
		//caminho
		$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
			"res" . DIRECTORY_SEPARATOR .
			"site" . DIRECTORY_SEPARATOR .
			"img" . DIRECTORY_SEPARATOR .
			"products" . DIRECTORY_SEPARATOR .
			$this->getidproduct() . ".jpg";

		//salva o arquivo no formato jpg, no endereço passado dinamicamente ($dist)
		imagejpeg($image, $dist);
		//destroi 
		imagedestroy($image);
		//verifica a foto, e salva 
		$this->checkPhoto();
	}

	//retorna os produtos de acordo com a url 
	public function getFromURL($desurl)
	{

		$sql = new Sql();

		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
			':desurl' => $desurl
		]);

		$this->setData($rows[0]);
	}

	//retorna as categorias relacionados com o produto 
	public function getCategories()
	{

		$sql = new Sql();

		return $sql->select("
			SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct
		", [

			':idproduct' => $this->getidproduct()
		]);
	}
	//pega a pagina 
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products 
			ORDER BY desproduct
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
			FROM tb_products 
			WHERE desproduct LIKE :search
			ORDER BY desproduct
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
