<?php
include "config.php";
include "utils.php";


$dbConn =  connect($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
    if (isset($_GET['id']))
    {
      //Mostrar un pedido
      
      $lo= obtener_pedido_get($_GET['id']);
      header("HTTP/1.1 200 OK");
      echo json_encode(quitar_delimitadores($lo), true);
      exit();
	  }
    else {
      //Mostrar lista de pedidos con comentarios
      $sql = $dbConn->prepare("SELECT * FROM grupo_daisa.comentarios_pedidos limit 20");
      $sql->execute();
      $sql->setFetchMode(PDO::FETCH_ASSOC);
      header("HTTP/1.1 200 OK");
      echo json_encode( $sql->fetchAll() );
      exit();
	}
}

// Crear un nuevo comentario en pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $input = $_POST;
    $fecha = date("Y-m-d H:i:s");
    $lo= obtener_pedido_bkend($input['pedido']);
    $to = explode('*', $lo);
    $de = explode('=', $to[0]);
    $de1 = explode('=', $to[1]);

    switch ($de[0]) {
      case 'pedido_user': $tabla = 'pedido_bkend'; $tabla1='pedido_user'; $pedido_user = $de1[1]; $pedido_bkend= $de[1]; break;
      case 'pedido_bkend': $tabla = 'pedido_bkend'; $tabla1='pedido_user'; $pedido_user = $de[1]; $pedido_bkend= $de1[1]; break;
    }
   
    $sql = "INSERT INTO comentarios_pedidos
          (".$tabla.", ".$tabla1.", comentario, usuario, fecha)
          VALUES
          ('".$pedido_user."', '".$pedido_bkend."', '".$input['comentario']."', '".$input['usuario']."', '".$fecha."' )";    
    $statement = $dbConn->prepare($sql);
    //bindAllValues($statement, $input);
    $statement->execute();
    $postId = $dbConn->lastInsertId();
    if($postId)
    {
      $input['id'] = $postId;
      header("HTTP/1.1 200 OK");
      echo json_encode($input);
      exit();
	 }
}

//Borrar un pedido
if ($_SERVER['REQUEST_METHOD'] == 'DELETE')
{
	$id = $_GET['id'];
  $statement = $dbConn->prepare("DELETE FROM comentarios_pedidos where id=:id");
  $statement->bindValue(':id', $id);
  $statement->execute();
	header("HTTP/1.1 200 OK");
	exit();
}

//Actualizar
if ($_SERVER['REQUEST_METHOD'] == 'PUT')
{
    $input = $_GET;
    $postId = $input['id'];
    $fields = getParams($input);

    $sql = "
          UPDATE grupo_daisa.comentarios_pedidos
          SET $fields
          WHERE id='$postId'
           ";

    $statement = $dbConn->prepare($sql);
    bindAllValues($statement, $input);

    $statement->execute();
    header("HTTP/1.1 200 OK");
    exit();
}


//En caso de que ninguna de las opciones anteriores se haya ejecutado
header("HTTP/1.1 400 Bad Request");

?>