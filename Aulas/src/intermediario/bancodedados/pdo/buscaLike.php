<?php
/**
 * Created by PhpStorm.
 * User: tassio
 * Date: 05/01/2018
 * Time: 13:53
 */

//ini_set('display_errors', 0);

try {
$pdo = new PDO('mysql:host=localhost;dbname=aulas', 'root', 'root');
$pdo->exec("set names utf8");
} catch ( PDOException $e ) {
    echo 'Erro ao conectar com o Banco: ' . $e->getMessage();
    exit(1);
}

function buscaPorNome(String $nome){
    global $pdo;
    $texto = "%$nome%";
    $query = "SELECT * FROM Professores WHERE Nome LIKE :nome";
    $statement = $pdo->prepare($query);
    $statement->bindValue(":nome",$texto);
    $statement->execute();

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $value) {
        $pessoaArray[] = $value;
    }
    return $pessoaArray;
}

$prof = "T";

$resultado = buscaPorNome($prof);

if(isset($resultado)) {
    foreach ($resultado as $r){
        echo $r['Nome']."<br>";
    }
} else {
    echo "Nenhum Registro Encontrado";
}

?>