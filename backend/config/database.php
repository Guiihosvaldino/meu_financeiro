<?php
$host = 'localhost'; 
$db   = 'meu_financeiro'; // O nome que você criou no phpMyAdmin
$user = 'root'; 
$pass = 'jesuscristo'; // No AppServ/XAMPP, a senha padrão costuma ser vazia ou 'root'

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se der erro, ele vai escrever "Erro na conexão", o que quebra o JSON do JS
    die("Erro na conexão: " . $e->getMessage());
}
