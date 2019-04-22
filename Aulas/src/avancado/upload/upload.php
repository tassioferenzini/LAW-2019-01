<html>
    <head>
        <title>Exemplo de Upload</title>
    </head>
    <body>
        <form enctype="multipart/form-data" method="POST">
            <!-- MAX_FILE_SIZE deve preceder o campo input -->
            <input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
            <!-- O Nome do elemento input determina o nome da array $_FILES -->
            Enviar esse arquivo: <input name="file" type="file" />
            <input type="submit" value="Enviar arquivo" />
        </form>
    </body>
</html>
<?php
/**
 * Created by PhpStorm.
 * User: tassio
 * Date: 2019-04-22
 * Time: 19:42
 */

if (isset($_POST['MAX_FILE_SIZE'])){

    $dir = "uploads/";
    $file = $_FILES["file"];

echo '<pre>';
// Move o arquivo da pasta temporaria de upload para a pasta de destino
    if (move_uploaded_file($file["tmp_name"], "$dir/".$file["name"])) {
        echo "Arquivo válido e enviado com sucesso.\n";
    } else {
        echo "Falha no upload de arquivo!\n";
    }

echo 'Aqui está mais informações de debug:';
print_r($_FILES);

print "</pre>";

}

?>