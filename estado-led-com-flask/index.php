<?php
// ====== CONFIGURAÇÃO DO BANCO ======
$host = "paparella.com.br";
$user = "paparell_prof";
$pass = "@Senai2025";
$db = "paparell_iot";

$conexao = mysqli_connect($host, $user, $pass, $db);
if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}

// ====== FUNÇÃO PARA DETECTAR COLUNAS ======
function detectarColunas($conexao, $tabela) {
    $colunas = [];
    $result = mysqli_query($conexao, "SHOW COLUMNS FROM $tabela");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $colunas[] = $row['Field'];
        }
    }
    
    $col_id = $colunas[0] ?? 'id';
    $col_nome = null;
    $col_valor = null;
    
    foreach ($colunas as $col) {
        if (stripos($col, 'nome') !== false || stripos($col, 'aluno') !== false) {
            $col_nome = $col;
        }
        if ($col_valor === null && $col !== $col_id && $col !== $col_nome) {
            $col_valor = $col;
        }
    }
    
    if (!$col_nome && isset($colunas[1])) $col_nome = $colunas[1];
    if (!$col_valor && isset($colunas[2])) $col_valor = $colunas[2];
    
    return ['id' => $col_id, 'nome' => $col_nome, 'valor' => $col_valor];
}

// ====== DETECTA COLUNAS DAS TRÊS TABELAS ======
$cols_ultrassom = detectarColunas($conexao, 'ultrassom');
$cols_ldr = detectarColunas($conexao, 'ldr');
$cols_termistor = detectarColunas($conexao, 'termistor');

// ====== SALVAR/ATUALIZAR DADOS ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"] ?? "";
    $tipo_sensor = $_POST["tipo_sensor"] ?? "";
    $valor = $_POST["valor"] ?? null;

    if (!empty($nome) && !empty($tipo_sensor) && $valor !== null && $valor !== "") {
        $tabela = $tipo_sensor;
        $cols = null;
        
        switch($tipo_sensor) {
            case 'ultrassom':
                $cols = $cols_ultrassom;
                break;
            case 'ldr':
                $cols = $cols_ldr;
                break;
            case 'termistor':
                $cols = $cols_termistor;
                break;
        }
        
        if ($cols) {
            // Verifica se o aluno já existe
            $col_id = $cols['id'];
            $col_nome = $cols['nome'];
            $col_valor = $cols['valor'];
            
            $stmt = $conexao->prepare("SELECT $col_id FROM $tabela WHERE $col_nome = ?");
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                // Atualiza
                $sql = "UPDATE $tabela SET $col_valor = ? WHERE $col_nome = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("ds", $valor, $nome);
                $stmt->execute();
            } else {
                // Insere
                $sql = "INSERT INTO $tabela ($col_nome, $col_valor) VALUES (?, ?)";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("sd", $nome, $valor);
                $stmt->execute();
            }
        }
    }
}

// ====== BUSCA DADOS DAS TRÊS TABELAS ======
$dados_ultrassom = [];
$result = mysqli_query($conexao, "SELECT * FROM ultrassom ORDER BY {$cols_ultrassom['id']} DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $dados_ultrassom[] = $row;
    }
}

$dados_ldr = [];
$result = mysqli_query($conexao, "SELECT * FROM ldr ORDER BY {$cols_ldr['id']} DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $dados_ldr[] = $row;
    }
}

$dados_termistor = [];
$result = mysqli_query($conexao, "SELECT * FROM termistor ORDER BY {$cols_termistor['id']} DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $dados_termistor[] = $row;
    }
}

mysqli_close($conexao);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Controle de Sensores IoT</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

h1 {
    text-align: center;
    margin-bottom: 30px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    font-size: 2.5em;
}

.form-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.form-group {
    margin: 15px 0;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input[type="text"], input[type="number"], select {
    width: 100%;
    padding: 12px 20px;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    background: rgba(255,255,255,0.9);
    color: #333;
}

.btn-container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 20px;
}

button {
    padding: 12px 30px;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    flex: 1;
    min-width: 200px;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.btn-ultrassom {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-ldr {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: white;
}

.btn-termistor {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: white;
}

.tables-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.table-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.table-card h2 {
    margin-bottom: 15px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    overflow: hidden;
}

td, th {
    border: 1px solid rgba(255,255,255,0.2);
    padding: 10px;
    text-align: center;
}

th {
    background-color: rgba(0,0,0,0.3);
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
}

tr:hover {
    background-color: rgba(255,255,255,0.1);
}

.status {
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
}

.info-box {
    background: rgba(255,255,255,0.1);
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 12px;
    text-align: center;
}

@media (max-width: 768px) {
    .tables-container {
        grid-template-columns: 1fr;
    }
    
    .btn-container {
        flex-direction: column;
    }
    
    button {
        width: 100%;
    }
}
</style>
</head>
<body>

<div class="container">
    <h1>🔬 Sistema de Controle de Sensores IoT</h1>
    
    <div class="info-box">
        ℹ️ Ultrassom: <?= $cols_ultrassom['valor'] ?> | LDR: <?= $cols_ldr['valor'] ?> | Termistor: <?= $cols_termistor['valor'] ?>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label>👤 Nome do Aluno:</label>
                <input type="text" name="nome" placeholder="Digite seu nome" required>
            </div>

            <div class="form-group">
                <label>📊 Tipo de Sensor:</label>
                <select name="tipo_sensor" id="tipoSensor" required onchange="updatePlaceholder()">
                    <option value="">Selecione um sensor</option>
                    <option value="ultrassom">📏 Ultrassom (Distância)</option>
                    <option value="ldr">💡 LDR (Luminosidade)</option>
                    <option value="termistor">🌡️ Termistor (Temperatura)</option>
                </select>
            </div>

            <div class="form-group">
                <label>📈 Valor:</label>
                <input type="number" step="0.01" name="valor" id="valorInput" placeholder="Digite o valor" required>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-ultrassom">📏 Salvar Leitura</button>
            </div>
        </form>
    </div>

    <div class="tables-container">
        <!-- ULTRASSOM -->
        <div class="table-card">
            <h2>📏 Sensor Ultrassom</h2>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Distância</th>
                </tr>
                <?php foreach ($dados_ultrassom as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d[$cols_ultrassom['nome']]) ?></td>
                    <td><?= number_format($d[$cols_ultrassom['valor']], 1) ?> cm</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dados_ultrassom)): ?>
                <tr><td colspan="2">Nenhum registro</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- LDR -->
        <div class="table-card">
            <h2>💡 Sensor LDR</h2>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($dados_ldr as $d): ?>
                <?php 
                    $valor = floatval($d[$cols_ldr['valor']]);
                    $status = $valor > 500 ? "☀️ Claro" : "🌙 Escuro";
                ?>
                <tr>
                    <td><?= htmlspecialchars($d[$cols_ldr['nome']]) ?></td>
                    <td><?= number_format($d[$cols_ldr['valor']], 0) ?></td>
                    <td><?= $status ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dados_ldr)): ?>
                <tr><td colspan="3">Nenhum registro</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- TERMISTOR -->
        <div class="table-card">
            <h2>🌡️ Sensor Termistor</h2>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Temp.</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($dados_termistor as $d): ?>
                <?php 
                    $temp = floatval($d[$cols_termistor['valor']]);
                    if ($temp >= 30) {
                        $status = "🔥 Quente";
                    } elseif ($temp >= 20) {
                        $status = "☀️ Normal";
                    } else {
                        $status = "❄️ Frio";
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($d[$cols_termistor['nome']]) ?></td>
                    <td><?= number_format($d[$cols_termistor['valor']], 1) ?>°C</td>
                    <td><?= $status ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($dados_termistor)): ?>
                <tr><td colspan="3">Nenhum registro</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
function updatePlaceholder() {
    const select = document.getElementById('tipoSensor');
    const input = document.getElementById('valorInput');
    
    switch(select.value) {
        case 'ultrassom':
            input.placeholder = 'Distância em cm (ex: 25.5)';
            break;
        case 'ldr':
            input.placeholder = 'Valor do LDR (ex: 450)';
            break;
        case 'termistor':
            input.placeholder = 'Temperatura em °C (ex: 23.5)';
            break;
        default:
            input.placeholder = 'Digite o valor';
    }
}
</script>

</body>
</html>