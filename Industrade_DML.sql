-- ============================================================================
-- INDUSTRADE - SCRIPT DML (INSERT / UPDATE / SELECT / DELETE por tabela)
-- Versao MySQL 8.0.16+ / MariaDB 10.2.1+ (execute antes o Industrade.SQL).
--
-- ORDEM DE EXECUCAO:
--   * INSERT, UPDATE e SELECT: executar de cima para baixo (1 -> 13), pois
--     as tabelas filhas dependem das tabelas pais.
--   * DELETE: executar de baixo para cima (13 -> 1), filhos antes dos pais,
--     para nao violar as chaves estrangeiras.
--
-- OBSERVACOES:
--   * As colunas AUTO_INCREMENT nao recebem valor no INSERT;
--     as FKs sao resolvidas por subconsultas usando chaves naturais.
--   * Dois enderecos/empresas sao inseridos (vendedor e comprador), pois
--     PEDIDO, MENSAGEM e AVALIACAO precisam de duas empresas distintas.
--   * EMPRESA.senha e NOT NULL: as duas empresas recebem o hash bcrypt (formato
--     do password_hash do PHP) da senha de teste 'industrade123'.
--   * SQL_SAFE_UPDATES e desligado abaixo porque varios UPDATE/DELETE filtram
--     por colunas que nao sao chave (o MySQL Workbench bloqueia isso por padrao,
--     erro 1175). Ele e religado no final do script.
-- ============================================================================

USE industrade;
SET SQL_SAFE_UPDATES = 0;


-- 1) ENDERECO -------------------------------------------------------------

-- INSERT (endereco do vendedor)
INSERT INTO ENDERECO (cep, logradouro, numero, complemento, bairro, cidade, uf)
VALUES ('01310-100', 'Avenida Paulista', '1000', 'Conjunto 12', 'Bela Vista', 'Sao Paulo', 'SP');

-- INSERT (endereco do comprador)
INSERT INTO ENDERECO (cep, logradouro, numero, complemento, bairro, cidade, uf)
VALUES ('04551-060', 'Rua Funchal', '500', NULL, 'Vila Olimpia', 'Sao Paulo', 'SP');

-- UPDATE
UPDATE ENDERECO
   SET complemento = 'Conjunto 15',
       numero      = '1010'
 WHERE cep    = '01310-100'
   AND numero = '1000';

-- SELECT
SELECT id_endereco, cep, logradouro, numero, complemento, bairro, cidade, uf
  FROM ENDERECO
 WHERE cidade = 'Sao Paulo'
   AND uf     = 'SP'
 ORDER BY bairro, logradouro;

-- DELETE
DELETE FROM ENDERECO
 WHERE (cep = '01310-100' AND numero = '1010')
    OR (cep = '04551-060' AND numero = '500');


-- 2) EMPRESA ---------------------------------------------------------------

-- INSERT (vendedor)
INSERT INTO EMPRESA (cnpj, razao_social, nome_fantasia, email, telefone, senha, id_endereco_fk)
VALUES ('12345678000190',
        'Metalurgica Silva & Filhos LTDA',
        'Metal Silva',
        'contato@metalsilva.com.br',
        '(11) 3000-1000',
        '$2y$12$lw9VgWGcrOi0QMdDyyH1zuPUrZFmP8oVP47FSyxCsjWuAv0B/KGE6',
        (SELECT id_endereco FROM ENDERECO WHERE cep = '01310-100' AND numero = '1010'));

-- INSERT (comprador)
INSERT INTO EMPRESA (cnpj, razao_social, nome_fantasia, email, telefone, senha, id_endereco_fk)
VALUES ('98765432000110',
        'Industria Moreira de Pecas S.A.',
        'Moreira Pecas',
        'compras@moreirapecas.com.br',
        '(11) 4000-2000',
        '$2y$12$lw9VgWGcrOi0QMdDyyH1zuPUrZFmP8oVP47FSyxCsjWuAv0B/KGE6',
        (SELECT id_endereco FROM ENDERECO WHERE cep = '04551-060' AND numero = '500'));

-- UPDATE
UPDATE EMPRESA
   SET email    = 'vendas@metalsilva.com.br',
       telefone = '(11) 3000-1111'
 WHERE cnpj = '12345678000190';

-- SELECT
SELECT e.cnpj, e.razao_social, e.nome_fantasia, e.email, e.telefone,
       en.logradouro, en.numero, en.bairro, en.cidade, en.uf
  FROM EMPRESA e
  JOIN ENDERECO en ON en.id_endereco = e.id_endereco_fk
 WHERE e.cnpj = '12345678000190';

-- DELETE
DELETE FROM EMPRESA
 WHERE cnpj IN ('12345678000190', '98765432000110');


-- 4) CATEGORIA ---------------------------------------------------------------

-- INSERT
INSERT INTO CATEGORIA (tipo)
VALUES ('Metalmecanica');

-- UPDATE
UPDATE CATEGORIA
   SET tipo = 'Metalmecanica e Usinagem'
 WHERE tipo = 'Metalmecanica';

-- SELECT
SELECT id_categoria, tipo
  FROM CATEGORIA
 ORDER BY tipo;

-- DELETE
DELETE FROM CATEGORIA
 WHERE tipo = 'Metalmecanica e Usinagem';


-- 5) ANUNCIO -----------------------------------------------------------------

-- INSERT
INSERT INTO ANUNCIO (nome, descricao, estado_conservacao, data_publicacao,
                     condicao, valor, tipo_preco, cnpj_fk, id_categoria_fk)
VALUES ('Torno CNC Romi Centur 30D',
        'Torno CNC com manutencao em dia, pouco uso e painel original.',
        'BOM',
        DATE '2026-10-05',
        'ATIVO',
        185000.00,
        'NEGOCIAVEL',
        '12345678000190',
        (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalmecanica e Usinagem'));

-- UPDATE
UPDATE ANUNCIO
   SET valor      = 179900.00,
       tipo_preco = 'FIXO',
       condicao   = 'ATIVO'
 WHERE nome    = 'Torno CNC Romi Centur 30D'
   AND cnpj_fk = '12345678000190';

-- SELECT
SELECT a.id_anuncio, a.nome, a.valor, a.tipo_preco, a.condicao,
       a.data_publicacao, c.tipo AS categoria, e.nome_fantasia AS vendedor
  FROM ANUNCIO a
  JOIN CATEGORIA c ON c.id_categoria = a.id_categoria_fk
  JOIN EMPRESA   e ON e.cnpj         = a.cnpj_fk
 WHERE a.condicao = 'ATIVO'
 ORDER BY a.data_publicacao DESC;

-- DELETE
DELETE FROM ANUNCIO
 WHERE nome    = 'Torno CNC Romi Centur 30D'
   AND cnpj_fk = '12345678000190';


-- 6) MAQUINARIO (especializacao de ANUNCIO) ----------------------------------

-- INSERT
INSERT INTO MAQUINARIO (marca, modelo, ano_fabricacao, peso, dimensao, voltagem, id_anuncio_fk)
VALUES ('Romi',
        'Centur 30D',
        2015,
        3200.00,
        '2,80 x 1,70 x 1,90 m',
        '380V',
        (SELECT id_anuncio FROM ANUNCIO
          WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190'));

-- UPDATE
UPDATE MAQUINARIO
   SET ano_fabricacao = 2016,
       voltagem       = '220V'
 WHERE id_anuncio_fk = (SELECT id_anuncio FROM ANUNCIO
                         WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');

-- SELECT
SELECT m.cod_maquinario, a.nome AS anuncio, m.marca, m.modelo,
       m.ano_fabricacao, m.peso, m.dimensao, m.voltagem
  FROM MAQUINARIO m
  JOIN ANUNCIO a ON a.id_anuncio = m.id_anuncio_fk
 WHERE m.marca = 'Romi';

-- DELETE
DELETE FROM MAQUINARIO
 WHERE id_anuncio_fk = (SELECT id_anuncio FROM ANUNCIO
                         WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');


-- 7) RECURSO (especializacao de ANUNCIO) -------------------------------------

-- INSERT
INSERT INTO RECURSO (nome, peso, quantidade, unidade_medida, id_anuncio_fk)
VALUES ('Sucata de aco carbono',
        1500.00,
        1500.00,
        'KG',
        (SELECT id_anuncio FROM ANUNCIO
          WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190'));

-- UPDATE
UPDATE RECURSO
   SET quantidade = 1200.00,
       peso       = 1200.00
 WHERE nome = 'Sucata de aco carbono'
   AND id_anuncio_fk = (SELECT id_anuncio FROM ANUNCIO
                         WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');

-- SELECT
SELECT r.cod_recurso, r.nome, r.peso, r.quantidade, r.unidade_medida,
       a.nome AS anuncio
  FROM RECURSO r
  JOIN ANUNCIO a ON a.id_anuncio = r.id_anuncio_fk
 WHERE r.unidade_medida = 'KG';

-- DELETE
DELETE FROM RECURSO
 WHERE nome = 'Sucata de aco carbono'
   AND id_anuncio_fk = (SELECT id_anuncio FROM ANUNCIO
                         WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');


-- 8) IMAGEM -------------------------------------------------------------------

-- INSERT
INSERT INTO IMAGEM (link, legenda, id_anuncio_fk)
VALUES ('img/torno-cnc-romi-frente.jpg',
        'Vista frontal do torno',
        (SELECT id_anuncio FROM ANUNCIO
          WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190'));

-- UPDATE
UPDATE IMAGEM
   SET legenda = 'Vista frontal do torno CNC Romi Centur 30D'
 WHERE link = 'img/torno-cnc-romi-frente.jpg';

-- SELECT
SELECT i.id_imagem, i.link, i.legenda, a.nome AS anuncio
  FROM IMAGEM i
  JOIN ANUNCIO a ON a.id_anuncio = i.id_anuncio_fk
 WHERE a.nome = 'Torno CNC Romi Centur 30D';

-- DELETE
DELETE FROM IMAGEM
 WHERE link = 'img/torno-cnc-romi-frente.jpg';


-- 9) PEDIDO ---------------------------------------------------------------------

-- INSERT (valor_total usa o DEFAULT 0)
INSERT INTO PEDIDO (data_criacao, condicao_atual, cnpj_comprador_fk)
VALUES (DATE '2026-10-05', 'CARRINHO', '98765432000110');

-- UPDATE
UPDATE PEDIDO
   SET condicao_atual = 'REALIZADO',
       valor_total    = 179900.00
 WHERE cnpj_comprador_fk = '98765432000110'
   AND data_criacao      = DATE '2026-10-05';

-- SELECT
SELECT p.id_pedido, p.data_criacao, p.condicao_atual, p.valor_total,
       e.nome_fantasia AS comprador
  FROM PEDIDO p
  JOIN EMPRESA e ON e.cnpj = p.cnpj_comprador_fk
 WHERE p.cnpj_comprador_fk = '98765432000110'
 ORDER BY p.data_criacao DESC;

-- DELETE
DELETE FROM PEDIDO
 WHERE cnpj_comprador_fk = '98765432000110'
   AND data_criacao      = DATE '2026-10-05';


-- 10) ITEM_PEDIDO (entidade associativa, PK composta) --------------------------

-- INSERT
INSERT INTO ITEM_PEDIDO (id_pedido_pk_fk, id_anuncio_pk_fk, quantidade, valor_unitario)
VALUES ((SELECT id_pedido FROM PEDIDO
          WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05'),
        (SELECT id_anuncio FROM ANUNCIO
          WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190'),
        1,
        179900.00);

-- UPDATE
UPDATE ITEM_PEDIDO
   SET quantidade     = 1,
       valor_unitario = 175000.00
 WHERE id_pedido_pk_fk  = (SELECT id_pedido FROM PEDIDO
                            WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05')
   AND id_anuncio_pk_fk = (SELECT id_anuncio FROM ANUNCIO
                            WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');

-- SELECT
SELECT ip.id_pedido_pk_fk AS id_pedido, a.nome AS anuncio,
       ip.quantidade, ip.valor_unitario,
       ip.quantidade * ip.valor_unitario AS subtotal
  FROM ITEM_PEDIDO ip
  JOIN ANUNCIO a ON a.id_anuncio = ip.id_anuncio_pk_fk
 WHERE ip.id_pedido_pk_fk = (SELECT id_pedido FROM PEDIDO
                              WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05');

-- DELETE
DELETE FROM ITEM_PEDIDO
 WHERE id_pedido_pk_fk  = (SELECT id_pedido FROM PEDIDO
                            WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05')
   AND id_anuncio_pk_fk = (SELECT id_anuncio FROM ANUNCIO
                            WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190');


-- 11) HISTORICO_STATUS_PEDIDO ----------------------------------------------------

-- INSERT
INSERT INTO HISTORICO_STATUS_PEDIDO (condicao, data_hora, id_pedido_fk)
VALUES ('REALIZADO',
        TIMESTAMP '2026-10-05 10:30:00',
        (SELECT id_pedido FROM PEDIDO
          WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05'));

-- UPDATE
UPDATE HISTORICO_STATUS_PEDIDO
   SET condicao  = 'ENVIADO',
       data_hora = TIMESTAMP '2026-10-07 14:00:00'
 WHERE condicao = 'REALIZADO'
   AND id_pedido_fk = (SELECT id_pedido FROM PEDIDO
                        WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05');

-- SELECT
SELECT h.id_historico, h.condicao, h.data_hora, h.id_pedido_fk
  FROM HISTORICO_STATUS_PEDIDO h
 WHERE h.id_pedido_fk = (SELECT id_pedido FROM PEDIDO
                          WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05')
 ORDER BY h.data_hora;

-- DELETE
DELETE FROM HISTORICO_STATUS_PEDIDO
 WHERE id_pedido_fk = (SELECT id_pedido FROM PEDIDO
                        WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05');


-- 12) MENSAGEM ---------------------------------------------------------------------

-- INSERT (is_chatbot: 0 = falso, 1 = verdadeiro, conforme ck_mensagem_is_chatbot)
INSERT INTO MENSAGEM (data_hora, conteudo, is_chatbot,
                      cnpj_remetente_fk, cnpj_destinatario_fk, id_anuncio_fk)
VALUES (TIMESTAMP '2026-10-05 09:15:00',
        'Boa tarde! O torno ainda esta disponivel?',
        0,
        '98765432000110',
        '12345678000190',
        (SELECT id_anuncio FROM ANUNCIO
          WHERE nome = 'Torno CNC Romi Centur 30D' AND cnpj_fk = '12345678000190'));

-- UPDATE
UPDATE MENSAGEM
   SET conteudo = 'Bom dia! O torno ainda esta disponivel para visita?'
 WHERE cnpj_remetente_fk = '98765432000110'
   AND data_hora         = TIMESTAMP '2026-10-05 09:15:00';

-- SELECT
SELECT m.id_mensagem, m.data_hora, m.conteudo, m.is_chatbot,
       r.nome_fantasia AS remetente, d.nome_fantasia AS destinatario,
       a.nome AS anuncio
  FROM MENSAGEM m
  JOIN EMPRESA r       ON r.cnpj = m.cnpj_remetente_fk
  LEFT JOIN EMPRESA d  ON d.cnpj = m.cnpj_destinatario_fk
  JOIN ANUNCIO a       ON a.id_anuncio = m.id_anuncio_fk
 WHERE a.nome = 'Torno CNC Romi Centur 30D'
 ORDER BY m.data_hora;

-- DELETE
DELETE FROM MENSAGEM
 WHERE cnpj_remetente_fk = '98765432000110'
   AND data_hora         = TIMESTAMP '2026-10-05 09:15:00';


-- 13) AVALIACAO -----------------------------------------------------------------------

-- INSERT
INSERT INTO AVALIACAO (nota, comentario, data_avaliacao,
                       cnpj_avaliador_fk, cnpj_avaliado_fk, id_pedido_fk)
VALUES (4.5,
        'Vendedor atencioso e maquina conforme o anuncio.',
        DATE '2026-10-15',
        '98765432000110',
        '12345678000190',
        (SELECT id_pedido FROM PEDIDO
          WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05'));

-- UPDATE
UPDATE AVALIACAO
   SET nota       = 5.0,
       comentario = 'Excelente negociacao, entrega dentro do prazo.'
 WHERE cnpj_avaliador_fk = '98765432000110'
   AND cnpj_avaliado_fk  = '12345678000190'
   AND id_pedido_fk      = (SELECT id_pedido FROM PEDIDO
                             WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05');

-- SELECT
SELECT av.id_avaliacao, av.nota, av.comentario, av.data_avaliacao,
       ar.nome_fantasia AS avaliador, ad.nome_fantasia AS avaliado
  FROM AVALIACAO av
  JOIN EMPRESA ar ON ar.cnpj = av.cnpj_avaliador_fk
  JOIN EMPRESA ad ON ad.cnpj = av.cnpj_avaliado_fk
 WHERE av.cnpj_avaliado_fk = '12345678000190'
 ORDER BY av.data_avaliacao DESC;

-- DELETE
DELETE FROM AVALIACAO
 WHERE cnpj_avaliador_fk = '98765432000110'
   AND cnpj_avaliado_fk  = '12345678000190'
   AND id_pedido_fk      = (SELECT id_pedido FROM PEDIDO
                             WHERE cnpj_comprador_fk = '98765432000110' AND data_criacao = DATE '2026-10-05');

SET SQL_SAFE_UPDATES = 1;
