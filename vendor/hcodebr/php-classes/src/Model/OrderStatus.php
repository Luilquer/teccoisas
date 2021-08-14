<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

//classe para retornar os status de comprar/pedidos
class OrderStatus extends Model
{
	//constantes
	const EM_ABERTO = 1;
	const AGUARDANDO_PAGAMENTO = 2;
	const PAGO = 3;
	const ENTREGUE = 4;

	//lista todos os status do BD
	public static function listAll()
	{

		$sql = new Sql();
		//retorna a consulta de acordo com o status do pedido 
		return $sql->select("SELECT * FROM tb_ordersstatus ORDER BY desstatus");
	}
}
