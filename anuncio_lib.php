<?php
// Funções e listas compartilhadas pelo cadastro/edição/exclusão de anúncios.

const CONDICOES_ITEM = ["Novo", "Seminovo", "Usado", "Revisado", "Para peças"];
const CONSERVACOES   = ["Excelente", "Bom", "Regular", "Necessita reparos"];
const UNIDADES       = ["Unidade", "Quilograma (kg)", "Tonelada (t)", "Metro (m)", "Litro (L)", "Caixa", "Pallet"];

function e($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }

// A tabela ANUNCIO tem uma única coluna (estado_conservacao, 30 caracteres) para
// "Condição do item" + "Estado de conservação". Guardamos os dois juntos: "Usado / Bom".
// (Maior combinação possível: "Para peças / Necessita reparos" = 30 caracteres.)
function codificarEstado($condicaoItem, $conservacao) {
    return $condicaoItem . " / " . $conservacao;
}
function decodificarEstado($texto) {
    $partes = explode(" / ", (string)$texto, 2);
    if (count($partes) === 2 && in_array($partes[0], CONDICOES_ITEM, true) && in_array($partes[1], CONSERVACOES, true)) {
        return [$partes[0], $partes[1]];
    }
    return ["", ""];    // anúncios antigos/de teste com outro formato
}

// Devolve o id da categoria (cria na primeira vez, se ainda não existir).
function obterCategoria(PDO $pdo, $tipo) {
    $stmt = $pdo->prepare("SELECT id_categoria FROM CATEGORIA WHERE tipo = ? ORDER BY id_categoria LIMIT 1");
    $stmt->execute([$tipo]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;
    $pdo->prepare("INSERT INTO CATEGORIA (tipo) VALUES (?)")->execute([$tipo]);
    return (int)$pdo->lastInsertId();
}

// Gera as <option> de um <select> marcando a escolhida.
function opcoes(array $lista, $atual) {
    $html = '<option value="">Selecione</option>';
    foreach ($lista as $o) {
        $html .= '<option' . ($o === $atual ? ' selected' : '') . '>' . e($o) . '</option>';
    }
    return $html;
}

// Setores (CATEGORIA.tipo). A chave é o "slug" usado nos filtros do catálogo (anuncios.php?categoria=energia).
const SETORES = [
    "metalmecanica" => "Metalmecânica",
    "movimentacao"  => "Movimentação e logística",
    "embalagem"     => "Embalagem e plástico",
    "energia"       => "Energia",
];
function slugSetor($tipo) {
    $s = array_search($tipo, SETORES, true);
    return $s === false ? "" : $s;
}

// "R$ 68.500" (sem centavos quando for valor redondo) ou "Sob consulta"
function precoFmt($valor) {
    $v = (float)$valor;
    if ($v <= 0) return "Sob consulta";
    return "R$ " . number_format($v, fmod($v, 1) == 0 ? 0 : 2, ",", ".");
}

// Iniciais para avatares: "Oficina Teste" -> "OT"
function iniciais($nome) {
    $r = "";
    foreach (preg_split('/\s+/', trim((string)$nome)) as $p) {
        if (mb_strlen($p) > 2 && mb_strlen($r) < 2) $r .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $r !== "" ? $r : mb_strtoupper(mb_substr((string)$nome, 0, 2));
}

// Anúncios ATIVOS para os cards (catálogo, home e "similares"), do mais novo para o mais antigo.
function buscarAnuncios(PDO $pdo, $extraWhere = "", array $params = [], $limite = 0) {
    $sql = "SELECT a.id_anuncio, a.nome, a.valor, a.tipo_preco, a.estado_conservacao,
                   c.tipo AS categoria, m.ano_fabricacao, r.quantidade, r.unidade_medida,
                   (SELECT i.link FROM IMAGEM i WHERE i.id_anuncio_fk = a.id_anuncio
                     ORDER BY i.id_imagem LIMIT 1) AS imagem
              FROM ANUNCIO a
              JOIN CATEGORIA c ON c.id_categoria = a.id_categoria_fk
              LEFT JOIN MAQUINARIO m ON m.id_anuncio_fk = a.id_anuncio
              LEFT JOIN RECURSO r    ON r.id_anuncio_fk = a.id_anuncio
             WHERE a.condicao = 'ATIVO' $extraWhere
             ORDER BY a.data_publicacao DESC, a.id_anuncio DESC";
    if ($limite > 0) $sql .= " LIMIT " . (int)$limite;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// <img> do anúncio; sem foto cadastrada usa a engrenagem do logo como imagem padrão
function imgAnuncio($url, $alt, $extraAttr = "") {
    if ($url) {
        return '<img src="' . e($url) . '" alt="' . e($alt) . '" ' . $extraAttr . '>';
    }
    return '<img src="img/Engrenagem v4.png" alt="' . e($alt) . ' (sem foto)" ' . $extraAttr
         . ' style="object-fit:contain;padding:1.5rem;box-sizing:border-box;background:#f1f1f1">';
}

// Card de anúncio. Com $ordem, inclui os data-* que o js/anuncios.js usa nos filtros.
function cardAnuncio(array $a, $ordem = null) {
    $partes = [];
    if ($a["ano_fabricacao"] !== null) {
        $partes[] = "Ano " . $a["ano_fabricacao"];
    } elseif ($a["quantidade"] !== null) {
        $partes[] = rtrim(rtrim(number_format((float)$a["quantidade"], 2, ",", "."), "0"), ",") . " " . $a["unidade_medida"];
    }
    list($cond, ) = decodificarEstado($a["estado_conservacao"]);
    $partes[] = $cond !== "" ? $cond : (string)$a["estado_conservacao"];
    $meta  = implode(" · ", array_filter($partes, 'strlen'));
    $preco = precoFmt($a["valor"]);
    $tag   = $a["tipo_preco"] === "NEGOCIAVEL"
           ? '<span class="tag tag-negociavel">Valor negociável</span>'
           : '<span class="tag tag-venda">Venda</span>';
    $attrs = "";
    if ($ordem !== null) {
        $attrs = ' data-categoria="' . e(slugSetor($a["categoria"])) . '"'
               . ' data-preco="' . ((float)$a["valor"] > 0 ? e((float)$a["valor"]) : "") . '"'
               . ' data-ordem="' . (int)$ordem . '"';
    }
    return '
          <article class="card-maquina"' . $attrs . '>
            <a class="card-maquina-link" href="anuncio.php?id=' . (int)$a["id_anuncio"] . '" aria-label="Ver anúncio: ' . e($a["nome"]) . ', ' . e($preco) . '">
              ' . imgAnuncio($a["imagem"], $a["nome"], 'loading="lazy"') . '
              <div class="card-conteudo">
                <div class="card-tags">' . $tag . '</div>
                <h3>' . e($a["nome"]) . '</h3>
                <p class="card-meta">' . e($meta) . '</p>
                <p class="card-preco">' . e($preco) . '</p>
              </div>
            </a>
          </article>
';
}
