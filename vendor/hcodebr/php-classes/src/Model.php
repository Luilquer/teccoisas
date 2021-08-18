<?php
//namespace principál
namespace Hcode;

class Model
{
	//atributos, dados do objeto
	private $values = [];


	//método mágico, rece o nome e argumentos passados
	public function __call($name, $args)
	{
		//retorna os tres primeiros digitos do nome que foi passado
		//caracter:name, inicio indice 0, retorna 3 caracters
		$method = substr($name, 0, 3);
		//vai até o final, strlen conta todos
		$fieldName = substr($name, 3, strlen($name));

		//verifica se é get(retorna a informação) ou set(altera o dado )
		switch ($method) {

			case "get":
				return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL;
				break;

			case "set":
				$this->values[$fieldName] = $args[0];
				break;
		}
	}


	//cria um atributo dinâmico para cada dado passado no banco de dados
	public function setData($data = array())
	{

		foreach ($data as $key => $value) {

			$this->{"set" . $key}($value);
		}
	}
	//retorna o atributo do banco de dados 
	public function getValues()
	{

		return $this->values;
	}
}
