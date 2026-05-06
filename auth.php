<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
header("Content-Type: application/json");
require_once "database.php";

$dados = json_decode(file_get_contents("php://input"), true);
$acao = $_GET['acao'] ?? '';

// CADASTRO
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
    exit;
}

// LOGIN
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
    exit;
}

// VERIFICAR SESSÃO
if ($acao === 'verificar') {
    if (isset($_SESSION['usuario_id'])) {
        echo json_encode(["logado" => true, "nome" => $_SESSION['usuario_nome']]);
    } else {
        echo json_encode(["logado" => false]);
    }
    exit;
}

// LOGOUT
if ($acao === 'logout') {
    session_destroy();
    echo json_encode(["status" => "sucesso"]);
    exit;
}

// SOLICITAR RECUPERAÇÃO
if ($acao == 'recuperar') {
    $email = $dados['email'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expira, $email]);

        echo json_encode([
            "mensagem" => "Link gerado! Copie e cole no navegador para testar.",
            "link" => "http://localhost/seu-projeto/reset-senha.html?token=" . $token
        ]);
    } else {
        echo json_encode(["mensagem" => "E-mail não encontrado."]);
    }
    exit;
}

// RESETAR SENHA (DE FATO)
if ($acao === 'resetar') {
    $token = $dados['token'];
    $novaSenha = password_hash($dados['novaSenha'], PASSWORD_DEFAULT);
    $agora = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > ?");
    $stmt->execute([$token, $agora]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$novaSenha, $user['id']]);
        echo json_encode(["mensagem" => "Senha alterada com sucesso!"]);
    } else {
        http_response_code(400);
        echo json_encode(["mensagem" => "Link inválido ou expirado."]);
    }
    exit;
}