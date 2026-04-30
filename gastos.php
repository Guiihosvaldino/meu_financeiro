<?php
session_start(); // FUNDAMENTAL: Sem isso o login não funciona
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
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

        // Adicionamos: WHERE m.usuario_id = :user_id
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
    
    // Forçar o valor a ser numérico (Postgres exige isso)
    $valor = (float) ($dados['valor'] ?? 0);
    $categoria_id = (int) ($dados['categoria_id'] ?? 1); // Garante que é um número
    $descricao = $dados['descricao'] ?? 'Sem descrição';
    $data = $dados['data'] ?? date('Y-m-d');

    try {
        $sql = "INSERT INTO movimentacoes (usuario_id, descricao, valor, categoria_id, data_movimentacao, tipo) 
                VALUES (:user_id, :desc, :val, :cat, :data, 'despesa')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => (int)$user_id,
            ':desc'    => $descricao,
            ':val'     => $valor,
            ':cat'     => $categoria_id,
            ':data'    => $data
        ]);

        echo json_encode(["status" => "sucesso", "msg" => "Gasto registrado!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        // Isso vai fazer o erro aparecer no console do navegador para você ler!
        echo json_encode(["erro_detalhado" => $e->getMessage()]);
        exit;
    }
}

// --- DELETAR GASTO (DELETE) ---
elseif ($metodo === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        try {
            // Segurança extra: só deleta se o gasto pertencer ao usuário logado
            $stmt = $pdo->prepare("DELETE FROM movimentacoes WHERE id = :id AND usuario_id = :user_id");
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);
            echo json_encode(["status" => "sucesso", "msg" => "Gasto removido!"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "erro", "msg" => $e->getMessage()]);
        }
    }
}