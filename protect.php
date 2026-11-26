<?php
require_once __DIR__ . "/sessao.php";

// Se não estiver logado, manda para a tela de login
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}
