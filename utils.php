<?php
  //Abrir conexion a la base de datos
$con = pg_connect("host=172.16.0.44 options='--client_encoding=UTF8' port=5432 dbname=grupo_daisa user=daisa password =toor");
  function connect($db)
  {
      try {
          $conn = new PDO("pgsql:host={$db['host']};dbname={$db['db']}", $db['username'], $db['password']);
          //$conn = pg_connect("host=172.16.0.44 options='--client_encoding=UTF8' port=5432 dbname=grupo_daisa user=daisa password =toor");

          // Error en PDO
          $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          return $conn;
          } catch (PDOException $exception) {
          exit($exception->getMessage());
      }
  }


 //Obtener parametros para updates
 function getParams($input)
 {
    $filterParams = [];
    foreach($input as $param => $value)
    {
            $filterParams[] = "$param=:$param";
    }
    return implode(", ", $filterParams);
	}

  //Asociar todos los parametros a un sql
	function bindAllValues($statement, $params)
  {
		foreach($params as $param => $value)
    {
				$statement->bindValue(':'.$param, $value);
		}
		return $statement;
   }


   function obtener_pedido_bkend($pedido){
    global $con;
    $request = 'https://joyeriasbizzarro.com/rest/V1/orders/'.$pedido;
    $ch = curl_init($request);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . "ppvzwsy3ht6mx9sy2hkbk8c37k9dgh9q"));
    $result = curl_exec($ch);
    $data= json_decode($result, true);
    $row = array($data);

    if (empty($row[0]['entity_id'])) {

      $query = pg_query($con, "SELECT * FROM grupo_daisa.pedidos WHERE pedido_bkend = '".$pedido."' ");
      $ro = pg_fetch_array($query);
      $pedidon = 'pedido_user='.$ro['pedido_user']."*pedido_bkend=".$pedido;
      
    }else{
      $pedidon='pedido_bkend='.$row[0]['increment_id']."*pedido_user=".$pedido;
    }

    switch ($pedidon) {
      case '':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'null':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case ' ':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'pedido_user=*pedido_bkend=':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'pedido_bkend=*pedido_user=':$pedidon='Entity: '.$pedido.' no existe.';   break;
    }
    
    return $pedidon;

   }


    function buscar_ticket_por_pedido($pedido)
  {

      $email='comercial@bzr.com.mx';
      $password='12345678';

      //$urltopost = 'https://soportedaisa.freshdesk.com/api/v2/search/tickets?query="(created_at:%27'.$fecha_ini.'%27)%20AND%20(due_by:<%27'.$fecha_fin.'%27)"';

  $urltopost = 'https://bizzarro.freshdesk.com/api/v2/search/tickets?&query="tag:'.$pedido.'"';
  $header[] = "Content-type: application/xml";

  $ch = curl_init ($urltopost);
  curl_setopt ($ch, CURLOPT_POST, false);
  curl_setopt($ch, CURLOPT_USERPWD, "$email:$password");
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

  $returndata = curl_exec($ch);
  $data= json_decode($returndata, true);
  $row = array($data);

  //print_r("<pre>".print_r($row,true)."</pre>");

  return $row;
      
  }


   function obtener_pedido_get($pedido){
    error_reporting(0);
    global $con;
    $comentarios='';
    $query = pg_query($con, "SELECT * FROM grupo_daisa.pedidos WHERE pedido_bkend = '".$pedido."' ");
    $ro = pg_fetch_array($query);
    if ($ro['pedido_bkend']=="") {
      $query = pg_query($con, "SELECT * FROM grupo_daisa.pedidos WHERE pedido_user = '".$pedido."' ");
    $ro = pg_fetch_array($query);
    }

    //echo $ro['pedido_user'];

    $res = buscar_ticket_por_pedido($ro['pedido_bkend']);
    $tiene_ticket = $res[0];

    if ($res[0]['total']>=1) {
      $tiene_ticket = $tiene_ticket['results'][0]['id'];
    }else{
      $tiene_ticket="no";
    }

    $quer= pg_query($con,"SELECT * FROM grupo_daisa.comentarios_pedidos WHERE pedido_bkend='".$pedido."' OR pedido_user='".$ro['pedido_user']."' ");
    while($ver = pg_fetch_array($quer)){

      if (!$ver['comentario']=='') {
        
        $comentarios.=$ver['comentario']."|Usuario: ".$ver['usuario']."|Fecha: ".$ver['fecha']."|";
      
      }else{
        $comentarios='Este pedido no cuenta con comentarios.';
      }

    }

    

    $request = 'https://joyeriasbizzarro.com/rest/V1/orders/'.$ro['pedido_user'];
    $ch = curl_init($request);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . "ppvzwsy3ht6mx9sy2hkbk8c37k9dgh9q"));
    $result = curl_exec($ch);
    $data= json_decode($result, true);
    $row = array($data);

    if ($row[0]['entity_id']=='') {

      
      $query = pg_query($con, "SELECT * FROM grupo_daisa.pedidos WHERE pedido_bkend = '".$pedido."' ");
      $ro = pg_fetch_array($query);
      $pedidon = 'pedido_user='.$ro['pedido_user']."*pedido_bkend=".$pedido."*Ticket=".$tiene_ticket."*Comentario=".$comentarios;
      
    }else{
      $pedidon='pedido_bkend='.$row[0]['increment_id']."*pedido_user=".$row[0]['entity_id']."*Ticket=".$tiene_ticket."*Comentario=".$comentarios;
    }

    

    switch ($pedidon) {
      case '':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'null':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case ' ':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'pedido_user=*pedido_bkend=':$pedidon='Entity: '.$pedido.' no existe.';   break;
      case 'pedido_bkend=*pedido_user=':$pedidon='Entity: '.$pedido.' no existe.';   break;
    }


    return $pedidon;

   }


   function quitar_delimitadores($lo){

    $quita_delimitador = explode("*", $lo);
      $quita_iguales_bkend = explode("=", $quita_delimitador[0]);
      $quita_iguales_user = explode("=", $quita_delimitador[1]);
      $quita_iguales_ticket = explode("=", $quita_delimitador[2]);
      $quita_iguales_comment = explode("=", $quita_delimitador[3]);

      $pedido_bkend = $quita_iguales_bkend[1];
      $pedido_user = $quita_iguales_user[1];
      $ticket = $quita_iguales_ticket[1];

      $coment = $quita_iguales_comment[1];

      $quita_pipe_comment = explode("|", $coment);
      $comentario = $quita_pipe_comment[0];

      $quita_espacio_de_comment = explode(" ", $quita_pipe_comment[1]);
      $quita_espacio_de_comment_fecha = explode(" ", $quita_pipe_comment[2]);

      $usuario = $quita_espacio_de_comment[1];
      $fecha = $quita_espacio_de_comment_fecha[1]." ".$quita_espacio_de_comment_fecha[2];

      $datos = [
        "pedido_bkend" => $pedido_bkend,
        "pedido_user" => $pedido_user,
        "ticket" => $ticket,
        "comentario" => $comentario,
        "usuario" => $usuario,
        "fecha_creado" => $fecha
       ];

       if ($pedido_bkend == '' || $pedido_bkend== NULL || empty($pedido_bkend)) {
         $datos = json_encode('"Sin datos":{"pedido": "invalido"}');
       }

       return $datos;

   }

 ?>