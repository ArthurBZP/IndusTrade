<?php
// Conexão com o banco MySQL usando PDO.
// Ajuste os dados abaixo para o seu MySQL (no XAMPP/WAMP o padrão é root sem senha).
$usuario = "root";            // usuário do MySQL
$senha   = "";                // senha desse usuário
$host    = "localhost";
$porta   = "3306";
$banco   = "industrade";      // banco criado pelo Industrade.SQL

try {
    // No MySQL o DSN começa com "mysql:" e usa host, porta e nome do banco.
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Usa prepared statements de verdade (sem emulação do PDO).
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // O MySQL devolve os nomes das colunas exatamente como no SELECT (minúsculas
    // aqui), então não é preciso o PDO::ATTR_CASE que o Oracle exigia:
    // $linha['cnpj'], $linha['senha'] continuam funcionando.
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
