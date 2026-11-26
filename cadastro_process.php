<?php
require_once __DIR__ . "/sessao.php";
require_once "conexao.php";

// Só aceita envio pelo formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php");
    exit;
}

// Volta para o cadastro com o erro e mantém o que foi digitado (menos a senha)
function voltar($erro) {
    $dados = $_POST;
    unset($dados["senha"]);
    $_SESSION["cadastro_dados"] = $dados;
    header("Location: cadastro.php?error=" . $erro);
    exit;
}

// ---- Leitura dos campos ----
$cnpj        = preg_replace('/\D/', '', $_POST["cnpj"] ?? "");   // só números (VARCHAR(14))
$senha       = $_POST["senha"] ?? "";
$razao       = trim($_POST["razao"] ?? "");
$fantasia    = trim($_POST["fantasia"] ?? "");
$email       = trim($_POST["email"] ?? "");
$telefone    = trim($_POST["telefone"] ?? "");                   // ex.: (11) 3000-1000
$responsavel = trim($_POST["responsavel"] ?? "");
$responsavel = $responsavel === "" ? null : $responsavel;

$cepNum      = preg_replace('/\D/', '', $_POST["cep"] ?? "");
$cep         = substr($cepNum, 0, 5) . "-" . substr($cepNum, 5); // 00000-000
$logradouro  = trim($_POST["logradouro"] ?? "");
$numero      = trim($_POST["numero"] ?? "");
$complemento = trim($_POST["complemento"] ?? "");
$complemento = $complemento === "" ? null : $complemento;
$bairro      = trim($_POST["bairro"] ?? "");
$cidade      = trim($_POST["cidade"] ?? "");
$uf          = strtoupper(trim($_POST["uf"] ?? ""));

// ---- Validação (mesmas regras das constraints da tabela) ----
$ufs = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB",
        "PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"];

if ($razao === "" || $fantasia === "" || $logradouro === "" || $numero === ""
    || $bairro === "" || $cidade === "")                    voltar("campos_obrigatorios");
if (strlen($cnpj) !== 14)                                   voltar("cnpj_invalido");
if (strlen($senha) < 8)                                     voltar("senha_curta");
if (!filter_var($email, FILTER_VALIDATE_EMAIL))             voltar("email_invalido");
if (strlen(preg_replace('/\D/', '', $telefone)) < 10)       voltar("telefone_invalido");
if (strlen($cepNum) !== 8)                                  voltar("cep_invalido");
if (!in_array($uf, $ufs, true))                             voltar("uf_invalida");

try {
    // ---- CNPJ ou e-mail já cadastrados? ----
    $stmt = $pdo->prepare("SELECT cnpj FROM EMPRESA WHERE cnpj = ? OR email = ?");
    $stmt->execute([$cnpj, $email]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existente) {
        voltar($existente["cnpj"] === $cnpj ? "cnpj_existente" : "email_existente");
    }

    // ---- Grava ENDERECO e EMPRESA juntos (ou nenhum dos dois) ----
    $pdo->beginTransaction();

    // No MySQL o id gerado pela coluna AUTO_INCREMENT é lido com lastInsertId()
    // logo após o INSERT (precisa vir antes de qualquer outro INSERT).
    $stmt = $pdo->prepare(
        "INSERT INTO ENDERECO (cep, logradouro, numero, complemento, bairro, cidade, uf)
         VALUES (:cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf)"
    );
    $stmt->bindValue(":cep", $cep);
    $stmt->bindValue(":logradouro", $logradouro);
    $stmt->bindValue(":numero", $numero);
    $stmt->bindValue(":complemento", $complemento);
    $stmt->bindValue(":bairro", $bairro);
    $stmt->bindValue(":cidade", $cidade);
    $stmt->bindValue(":uf", $uf);
    $stmt->execute();
    $idEndereco = (int) $pdo->lastInsertId();

    // data_cadastro (DEFAULT CURRENT_TIMESTAMP) e verificada (DEFAULT 0) são preenchidas pelo banco
    $stmt = $pdo->prepare(
        "INSERT INTO EMPRESA (cnpj, razao_social, nome_fantasia, email, telefone, senha,
                              nome_responsavel, id_endereco_fk)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $cnpj, $razao, $fantasia, $email, $telefone,
        password_hash($senha, PASSWORD_DEFAULT),
        $responsavel, $idEndereco
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Erro 1062 do MySQL (SQLSTATE 23000) = entrada duplicada em chave única
    // (CNPJ ou e-mail repetido)
    $codigoMySQL = $e->errorInfo[1] ?? 0;
    voltar($codigoMySQL == 1062 ? "duplicado" : "server_error");
}

unset($_SESSION["cadastro_dados"]);
header("Location: login.php?cadastro=ok");
exit;
