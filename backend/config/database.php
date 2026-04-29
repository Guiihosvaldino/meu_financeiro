<?php
$host = 'dpg-d7p8j0dckfvc73b20pcg-a'; 
$db   = 'meu_financeiro_gy56'; // O nome que você criou no phpMyAdmin
$user = 'meu_financeiro_gy56_user'; 
$pass = 'KrF8nvxGiz3Wa2IBL6op2Y3tsAmCITOT'; // No AppServ/XAMPP, a senha padrão costuma ser vazia ou 'root'

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se der erro, ele vai escrever "Erro na conexão", o que quebra o JSON do JS
    die("Erro na conexão: " . $e->getMessage());
}
