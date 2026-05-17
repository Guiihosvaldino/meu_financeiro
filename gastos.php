<?php
session_start(); 
date_default_timezone_set('America/Sao_Paulo');

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS"); // Adicionado PUT
header("Access-Control-Allow-Headers: Content-Type");

require_once "database.php"; 

$metodo = $_SERVER['REQUEST_METHOD'];

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit(json_encode(["erro" => "Não autorizado"]));
}

$user_id = $_SESSION['usuario_id'];

// --- BUSCAR GASTOS (GET) ---
if ($metodo === 'GET') {
    try {
        $data_inicio = $_GET['inicio'] ?? null;
        $data_fim = $_GET['fim'] ?? null;

        $sql = "SELECT m.*, c.nome as categoria_nome 
                FROM movimentacoes m 
                JOIN categorias c ON m.categoria_id = c.id 
                WHERE m.tipo = 'despesa' AND m.usuario_id = :user_id";

        if ($data_inicio && $data_fim) {
            $sql .= " AND m.data_movimentacao BETWEEN :inicio AND :fim";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC";
        
        $stmt = $pdo->prepare($sql);
        $params = [':user_id' => $user_id];
        if ($data_inicio && $data_fim) {
            $params[':inicio'] = $data_inicio;
            $params[':fim'] = $data_fim;
        }

        $stmt->execute($params);
        $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($gastos);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}

// --- SALVAR NOVO GASTO (POST) ---
elseif ($metodo === 'POST') {
    $dados = json_decode(file_get_contents("php://input"), true);
    $valor = floatval($dados['valor'] ?? 0);
    $descricao = $dados['descricao'] ?? 'Sem descrição';
    $categoria_id = (int)($dados['categoria_id'] ?? 1);
    $data = $dados['data'] ?? date('Y-m-d');
    
    // Se o JS não mandar observacao, o PHP assume um texto vazio '' em vez de NULL
    $observacao = $dados['observacao'] ?? ''; 

    try {
        // Mantemos a observação aqui para o banco de produção não reclamar
        $sql = "INSERT INTO movimentacoes (usuario_id, categoria_id, descricao, valor, data_movimentacao, tipo, observacao) 
                VALUES (:user_id, :cat, :desc, :val, :data, 'despesa', :obs)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':cat'     => $categoria_id,
            ':desc'    => $descricao,
            ':val'     => $valor,
            ':data'    => $data,
            ':obs'     => $observacao // Passando a string vazia configurada acima
        ]);

        echo json_encode(["status" => "sucesso", "msg" => "Gasto registrado!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "erro", "msg" => $e->getMessage()]);
    }
}

// --- EDITAR GASTO EXISTENTE (PUT) ---
elseif ($metodo === 'PUT') {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $id = (int)$dados['id'];
    $desc = $dados['descricao'];
    $val = floatval($dados['valor']);
    $data = $dados['data'];
    $cat = (int)$dados['categoria_id'];

    try {
        $sql = "UPDATE movimentacoes 
                SET descricao = :desc, valor = :val, data_movimentacao = :data, categoria_id = :cat 
                WHERE id = :id AND usuario_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':desc' => $desc,
            ':val' => $val,
            ':data' => $data,
            ':cat' => $cat,
            ':id' => $id,
            ':user_id' => $user_id
        ]);
        echo json_encode(["status" => "sucesso"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["erro" => $e->getMessage()]);
    }
}
// --- DELETAR GASTO (DELETE) ---
elseif ($metodo === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM movimentacoes WHERE id = :id AND usuario_id = :user_id");
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);
            echo json_encode(["status" => "sucesso", "msg" => "Gasto removido!"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "erro", "msg" => $e->getMessage()]);
        }
    }
}