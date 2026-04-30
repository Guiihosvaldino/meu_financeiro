<?php

session_start();
header("Content-Type: application/json");
require_once "database.php";

$dados = json_decode(file_get_contents("php://input"), true);
$acao = $_GET['acao'] ?? '';

if ($acao === 'cadastrar') {
    $senhaCripto = password_hash($dados['senha'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':nome' => $dados['nome'], ':email' => $dados['email'], ':senha' => $senhaCripto]);
        echo json_encode(["status" => "sucesso"]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["erro" => "E-mail já cadastrado"]);
    }
}

if ($acao === 'login') {
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $dados['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($dados['senha'], $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        echo json_encode(["status" => "sucesso", "nome" => $user['nome']]);
    } else {
        http_response_code(401);
        echo json_encode(["erro" => "E-mail ou senha incorretos"]);
    }
}
// Verificação de Sessão (quem sou eu?)
if ($acao === 'verificar') {
    if (isset($_SESSION['usuario_id'])) {
        echo json_encode([
            "logado" => true, 
            "nome" => $_SESSION['usuario_nome']
        ]);
    } else {
        echo json_encode(["logado" => false]);
    }
    exit;
}
$acao = $_GET['acao'] ?? '';

if ($acao === 'verificar') {
    if (isset($_SESSION['usuario_id'])) {
        echo json_encode(["logado" => true, "nome" => $_SESSION['usuario_nome']]);
    } else {
        echo json_encode(["logado" => false]);
    }
    exit;
}

if ($acao === 'logout') {
    session_destroy();
    echo json_encode(["status" => "sucesso"]);
    exit;
}