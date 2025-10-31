<?php
$conexao = mysqli_connect( 'paparella.com.br', 'paparell_prof', '@Senai2025','paparell_iot');
if (!$conexao) {
    die('Não foi possível conectar: ' . mysqli_connect_error());
}
echo "Conexão bem sucedida\n";

$nome ="Vinicios";
$estado = 1;

$query =  $conexao->prepare("SELECT id_led FROM led WHERE nome_aluno =?");
$query->bind_param("s", $nome);
if($query->execute()){
    echo"Consulta realizada com Sucesso!";
}else{
    echo "[ERRO] Dados não inseridos";
}
$result = $query->get_result();
$rows = $result->fetch_assoc();
$id = $rows["id_led"];

if(!empty($id)){
    $query =  $conexao->prepare("update led set estado_led = ? where id_led = ?");
    $query->bind_param("ii",$estado,$id);
    if($query->execute()){
        echo"Atualizado com Sucesso!";
    }else{
        echo "[ERRO] Dados não inseridos";
    }
}else{
    
    $query =  $conexao->prepare("insert into led(nome_aluno, estado_led) values(?,?)");
    $query->bind_param("si",$nome,$estado);
    if($query->execute()){
        echo"Inserido com Sucesso!";
    }else{
        echo "[ERRO] Dados não inseridos";
    }
}

// $result = $conexao->query("SELECT * FROM led");

// $rows = $result->fetch_all(MYSQLI_ASSOC);




foreach ($rows as $row) {
    printf("\nID => %s | Aluno => %s | Estado LED =>  %s",$row["id_led"],$row["nome_aluno"], $row["estado_led"]);
}

mysqli_close($conexao);


?>