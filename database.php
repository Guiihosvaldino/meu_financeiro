<?php
// Use a "Internal Database URL" ou o Host que o Render te passou
$host = 'dpg-d7p8j0dckfvc73b20pcg-a'; 
$db   = 'meu_financeiro_gy56'; 
$user = 'meu_financeiro_gy56_user'; 
$pass = 'KrF8nvxGiz3Wa2IBL6op2Y3tsAmCITOT'; 

try {
    // Mudamos de mysql para pgsql
    // Muda de mysql: para pgsql:
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Isso ajuda a debugar no log do Render se algo falhar
    error_log("Erro na conexão: " . $e->getMessage());
    exit("Erro interno no servidor.");
}