<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;


class User extends Model
{
	//constantes com nome da sessão
	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret"; //chave para criptografar a senha
	const SECRET_IV = "HcodePhp7_Secret_IV";
	const ERROR = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";
	const SUCCESS = "UserSucesss";


	//login verifica se existe 
	public static function getFromSession()
	{
		//
		$user = new User();
		//verifica se no banco de dados existe os dados inseridos
		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
			//retorna o novo usuário 
			$user->setData($_SESSION[User::SESSION]);
		}

		return $user;
	}


	//verifica o login
	public static function checkLogin($inadmin = true)
	{
		//verifica se está logado 
		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;
		} else {
			//verifica se é admin ou cliente 
			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

				return true;
				//ta logado mas não é admin, pode finalizar a compra
			} else if ($inadmin === false) {

				return true;
			} else {
				//não está logado 
				return false;
			}
		}
	}


	//login 
	public static function login($login, $password)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN" => $login
		));

		//verifica se é igual a zero
		if (count($results) === 0) {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}

		//dados do usuario
		$data = $results[0];

		//verifica a senha do usuario
		if (password_verify($password, $data["despassword"]) === true) {

			$user = new User();

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;
		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}
	}


	//verifica o login
	public static function verifyLogin($inadmin = true)
	{
		//verifica se não é definida ou se é falsa
		if (!User::checkLogin($inadmin)) {
			//teste
			$inadmin = true;
			//redireciona para o adm
			if ($inadmin) {
				header("Location: /admin/login");
			} else {
				//redireciona para o login usuario
				header("Location: /login");
			}
			exit;
		}
	}

	//sair, limpa a session
	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;
	}

	//lista todos os usuários do banco de dados 
	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}

	//salvar os dados no banco de dados  
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson" => utf8_decode($this->getdesperson()),
			":deslogin" => $this->getdeslogin(),
			":despassword" => User::getPasswordHash($this->getdespassword()),
			":desemail" => $this->getdesemail(),
			":nrphone" => $this->getnrphone(),
			":inadmin" => $this->getinadmin()
		));

		$this->setData($results[0]);
	}

	//método para atualizar no banco de dados 
	public function get($iduser)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser" => $iduser
		));

		$data = $results[0];

		$data['desperson'] = utf8_encode($data['desperson']);


		$this->setData($data);
	}

	//método para atualizar no banco de dados 
	public function update()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser" => $this->getiduser(),
			":desperson" => utf8_decode($this->getdesperson()),
			":deslogin" => $this->getdeslogin(),
			":despassword" => User::getPasswordHash($this->getdespassword()),
			":desemail" => $this->getdesemail(),
			":nrphone" => $this->getnrphone(),
			":inadmin" => $this->getinadmin()
		));

		$this->setData($results[0]);
	}

	//método para excluir no banco de dados 
	public function delete()
	{

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser" => $this->getiduser()
		));
	}

	//método para redefinir a senha
	public static function getForgot($email, $inadmin = true)
	{
		//consulta no banco de dados 
		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email" => $email
		));

		//verifica se encontrou algum email
		if (count($results) === 0) {

			throw new \Exception("Não foi possível recuperar a senha.");
		} else {
			//cria um novo registro na tabela 
			$data = $results[0];

			//consulta no banco de dados, cria uma nova senha
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser" => $data['iduser'],
				":desip" => $_SERVER['REMOTE_ADDR']
			));
			//verifica se criou
			if (count($results2) === 0) {
				//lança uma exception
				throw new \Exception("Não foi possível recuperar a senha.");
			} else {
				//recebe o dados gerados 
				$dataRecovery = $results2[0];
				//criptografia
				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);

				if ($inadmin === true) {
					//link para redirecionar
					$link = "http://www.teccoisas.com.br/admin/forgot/reset?code=$code";
				} else {

					$link = "http://www.teccoisas.com.br/forgot/reset?code=$code";
				}

				//envia por email, com a classe Mailer()
				//passas os dados 
				$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da TecCoisas Store", "forgot", array(
					"name" => $data['desperson'],
					"link" => $link
				));

				//envia o email para a pessoa 
				$mailer->send();

				return $link;
			}
		}
	}

	//Decodifica para redefinir a senha 
	public static function validForgotDecrypt($code)
	{

		$code = base64_decode($code);

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

		$sql = new Sql();

		//verificação no banco de dados 
		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery" => $idrecovery
		));
		//verifica se trouxe corretamente 
		if (count($results) === 0) {
			throw new \Exception("Não foi possível recuperar a senha.");
		} else {
			//retorna os dados 
			return $results[0];
		}
	}


	//ALTERA A SENHA NO BANCO DE DADOS 
	public static function setFogotUsed($idrecovery)
	{

		$sql = new Sql();
		//FAZ UM UPDATE NO BANCO DE DADOS 
		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery" => $idrecovery
		));
	}

	//faz o update da senha no banco de dados 
	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password" => $password,
			":iduser" => $this->getiduser()
		));
	}
	//metodos para Erro
	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;
	}
	//retorna o erro 
	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;
	}
	//limpa erro 
	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;
	}

	//set para mensagem 
	public static function setSuccess($msg)
	{

		$_SESSION[User::SUCCESS] = $msg;
	}

	public static function getSuccess()
	{

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;
	}

	public static function clearSuccess()
	{

		$_SESSION[User::SUCCESS] = NULL;
	}

	//Recebe a mensagem do erro 
	public static function setErrorRegister($msg)
	{

		$_SESSION[User::ERROR_REGISTER] = $msg;
	}
	//retorna a mensagem de erro 
	public static function getErrorRegister()
	{
		//verifica se foi definido, qual usurio = true, retorna o usuário. caso contrário, retorna vazio 
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;
	}

	//limpa o erro 
	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;
	}

	//verificação de um usuário existente 
	public static function checkLoginExist($login)
	{

		$sql = new Sql();
		//se retorna algo, significa que existe o usuário em questão 
		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin' => $login
		]);

		return (count($results) > 0);
	}

	//faz a criptografia da senha
	public static function getPasswordHash($password)
	{

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost' => 12
		]);
	}

	//retorna os pedidos realizados 
	public function getOrders()
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.iduser = :iduser
		", [
			':iduser' => $this->getiduser()
		]);

		return $results;
	}
	//retorna a pagina atual
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data' => $results,
			'total' => (int)$resultTotal[0]["nrtotal"],
			'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//retorna a busca de acordo com a busca passada 
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();
		//consulta no banco 
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson)
			WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
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
