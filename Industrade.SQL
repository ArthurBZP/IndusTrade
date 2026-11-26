-- ============================================================================
-- INDUSTRADE - SCRIPT DDL (MySQL 8.0.16+ / MariaDB 10.2.1+)
-- Convertido a partir da versao Oracle.
--
-- Principais equivalencias:
--   NUMBER GENERATED ALWAYS AS IDENTITY  ->  INT NOT NULL AUTO_INCREMENT
--   VARCHAR2(n)                          ->  VARCHAR(n)
--   NUMBER(p,s)                          ->  DECIMAL(p,s)
--   NUMBER(1) / BOOLEAN                  ->  TINYINT(1)
--   DATE DEFAULT SYSDATE                 ->  DATETIME DEFAULT CURRENT_TIMESTAMP
--                                            (o DATE do Oracle guarda data + hora)
--   TIMESTAMP                            ->  DATETIME
--   LENGTH()                             ->  CHAR_LENGTH()
--
-- Obs.: as FKs exigem InnoDB (padrao do MySQL). Os CHECKs so sao aplicados a
-- partir do MySQL 8.0.16 (em versoes antigas sao aceitos, mas ignorados).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS industrade
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE industrade;

-- 1) ENDERECO -------------------------------------------------------------
CREATE TABLE ENDERECO (
    id_endereco   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cep           VARCHAR(9)   NOT NULL,
    logradouro    VARCHAR(100) NOT NULL,
    numero        VARCHAR(10)  NOT NULL,
    complemento   VARCHAR(60),
    bairro        VARCHAR(80)  NOT NULL,
    cidade        VARCHAR(80)  NOT NULL,
    uf            CHAR(2)       NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) EMPRESA ----------------------------------------------------------------
CREATE TABLE EMPRESA (
    cnpj              VARCHAR(14)  PRIMARY KEY,
    razao_social      VARCHAR(150) NOT NULL,
    nome_fantasia     VARCHAR(150) NOT NULL,
    email             VARCHAR(150) NOT NULL,
    telefone          VARCHAR(20)  NOT NULL,
    senha             VARCHAR(255)  NOT NULL,
    nome_responsavel  VARCHAR(100),
    data_cadastro     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verificada        TINYINT(1)    NOT NULL DEFAULT 0,
    id_endereco_fk    INT NOT NULL,

    CONSTRAINT uq_empresa_email UNIQUE (email),

    CONSTRAINT fk_empresa_endereco
        FOREIGN KEY (id_endereco_fk)
        REFERENCES ENDERECO (id_endereco),

    CONSTRAINT ck_empresa_cnpj      CHECK (CHAR_LENGTH(cnpj) = 14),
    CONSTRAINT ck_empresa_senha     CHECK (CHAR_LENGTH(senha) >= 8),
    CONSTRAINT ck_empresa_verificada CHECK (verificada IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) CATEGORIA ---------------------------------------------------------------
CREATE TABLE CATEGORIA (
    id_categoria   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo           VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) ANUNCIO -----------------------------------------------------------------
CREATE TABLE ANUNCIO (
    id_anuncio           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome                 VARCHAR(150)  NOT NULL,
    descricao            VARCHAR(2000),
    estado_conservacao   VARCHAR(30),
    data_publicacao      DATE           NOT NULL,
    condicao             VARCHAR(20)   NOT NULL,
    valor                DECIMAL(12,2)   NOT NULL,
    tipo_preco           VARCHAR(12)   NOT NULL,
    cnpj_fk              VARCHAR(14)   NOT NULL,
    id_categoria_fk      INT NOT NULL,
    condicao_item VARCHAR(20) NOT NULL,

    CONSTRAINT fk_anuncio_empresa 
        FOREIGN KEY (cnpj_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT fk_anuncio_categoria 
        FOREIGN KEY (id_categoria_fk)
        REFERENCES CATEGORIA (id_categoria),

    CONSTRAINT ck_anuncio_tipo_preco
        CHECK (tipo_preco IN ('FIXO', 'NEGOCIAVEL')),

    CONSTRAINT ck_anuncio_condicao
        CHECK (condicao IN ('ATIVO', 'PAUSADO', 'VENDIDO', 'REMOVIDO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) MAQUINARIO (especializacao de ANUNCIO) ----------------------------------
CREATE TABLE MAQUINARIO (
    cod_maquinario   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    marca            VARCHAR(80),
    modelo           VARCHAR(80),
    ano_fabricacao   SMALLINT UNSIGNED,
    peso             DECIMAL(10,2),
    dimensao         VARCHAR(60),
    voltagem         VARCHAR(20),
    id_anuncio_fk    INT NOT NULL,

    CONSTRAINT fk_maquinario_anuncio 
        FOREIGN KEY (id_anuncio_fk)
        REFERENCES ANUNCIO (id_anuncio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) RECURSO (especializacao de ANUNCIO) -------------------------------------
CREATE TABLE RECURSO (
    cod_recurso      INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(120) NOT NULL,
    peso             DECIMAL(10,2),
    quantidade       DECIMAL(10,2)  NOT NULL,
    unidade_medida   VARCHAR(20)  NOT NULL,
    id_anuncio_fk    INT NOT NULL,

    CONSTRAINT fk_recurso_anuncio 
        FOREIGN KEY (id_anuncio_fk)
        REFERENCES ANUNCIO (id_anuncio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) IMAGEM -------------------------------------------------------------------
CREATE TABLE IMAGEM (
    id_imagem      INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    link           VARCHAR(500) NOT NULL,
    legenda        VARCHAR(120),
    id_anuncio_fk  INT NOT NULL,

    CONSTRAINT fk_imagem_anuncio 
        FOREIGN KEY (id_anuncio_fk)
        REFERENCES ANUNCIO (id_anuncio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) PEDIDO ---------------------------------------------------------------------
CREATE TABLE PEDIDO (
    id_pedido          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    data_criacao       DATE          NOT NULL,
    condicao_atual       VARCHAR(20)  NOT NULL,
    valor_total        DECIMAL(12,2) NOT NULL DEFAULT 0,
    cnpj_comprador_fk  VARCHAR(14)  NOT NULL,

    CONSTRAINT fk_pedido_empresa 
        FOREIGN KEY (cnpj_comprador_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT ck_pedido_condicao
        CHECK (condicao_atual IN ('CARRINHO', 'REALIZADO', 'ENVIADO', 'ENTREGUE', 'CANCELADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) ITEM_PEDIDO (entidade associativa, PK composta) --------------------------
CREATE TABLE ITEM_PEDIDO (
    id_pedido_pk_fk        INT NOT NULL,
    id_anuncio_pk_fk       INT NOT NULL,
    quantidade             DECIMAL(10,2) NOT NULL,
    valor_unitario         DECIMAL(12,2) NOT NULL,

    CONSTRAINT pk_item_pedido PRIMARY KEY (id_pedido_pk_fk, id_anuncio_pk_fk),

    CONSTRAINT fk_item_pedido_pedido 
        FOREIGN KEY (id_pedido_pk_fk)
        REFERENCES PEDIDO (id_pedido),

    CONSTRAINT fk_item_pedido_anuncio 
        FOREIGN KEY (id_anuncio_pk_fk)
        REFERENCES ANUNCIO (id_anuncio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) HISTORICO_STATUS_PEDIDO ----------------------------------------------------
CREATE TABLE HISTORICO_STATUS_PEDIDO (
    id_historico   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    condicao       VARCHAR(20)   NOT NULL,
    data_hora      DATETIME       NOT NULL,
    id_pedido_fk   INT NOT NULL,

    CONSTRAINT fk_historico_pedido 
        FOREIGN KEY (id_pedido_fk)
        REFERENCES PEDIDO (id_pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12) MENSAGEM ---------------------------------------------------------------------
CREATE TABLE MENSAGEM (
    id_mensagem          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    data_hora            DATETIME       NOT NULL,
    conteudo             VARCHAR(2000) NOT NULL,
    is_chatbot           TINYINT(1)     NOT NULL,
    cnpj_remetente_fk    VARCHAR(14)   NOT NULL,
    cnpj_destinatario_fk VARCHAR(14),
    id_anuncio_fk        INT NOT NULL,

    CONSTRAINT fk_mensagem_remetente 
        FOREIGN KEY (cnpj_remetente_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT fk_mensagem_destinatario 
        FOREIGN KEY (cnpj_destinatario_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT fk_mensagem_anuncio 
        FOREIGN KEY (id_anuncio_fk)
        REFERENCES ANUNCIO (id_anuncio),

    CONSTRAINT ck_mensagem_is_chatbot 
        CHECK (is_chatbot IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13) AVALIACAO -----------------------------------------------------------------------
CREATE TABLE AVALIACAO (
    id_avaliacao      INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nota              DECIMAL(2,1)   NOT NULL,
    comentario        VARCHAR(1000),
    data_avaliacao    DATE          NOT NULL,
    cnpj_avaliador_fk VARCHAR(14)  NOT NULL,
    cnpj_avaliado_fk  VARCHAR(14)  NOT NULL,
    id_pedido_fk      INT NOT NULL,

    CONSTRAINT fk_avaliacao_avaliador 
        FOREIGN KEY (cnpj_avaliador_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT fk_avaliacao_avaliado 
        FOREIGN KEY (cnpj_avaliado_fk)
        REFERENCES EMPRESA (cnpj),

    CONSTRAINT fk_avaliacao_pedido 
        FOREIGN KEY (id_pedido_fk)
        REFERENCES PEDIDO (id_pedido),

    CONSTRAINT ck_avaliacao_nota 
        CHECK (nota BETWEEN 0 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--// INSERTS //

INSERT INTO ENDERECO
(cep, logradouro, numero, complemento, bairro, cidade, uf)
VALUES
('11111-111', 'Rua das Industrias', '450', 'Galpao 02', 'Distrito Industrial', 'Rio Claro', 'SP'),
('22222-222', 'Avenida da Producao', '820', 'Bloco B', 'Jardim Industrial', 'Araras', 'SP'),
('33333-333', 'Rua dos Transportes', '1250', 'Galpao 05', 'Parque Industrial', 'Limeira', 'SP'),
('44444-444', 'Avenida da Energia', '300', 'Sala 04', 'Distrito Empresarial', 'Campinas', 'SP');


-- =====================================================
-- 2. EMPRESAS (USUARIOS)
-- =====================================================

INSERT INTO EMPRESA
(cnpj, razao_social, nome_fantasia, email, telefone,
 senha, nome_responsavel, id_endereco_fk)
VALUES
('11111111000111',
 'Metalurgica Horizonte Ltda.',
 'Horizonte Metais',
 'contato@horizontemetais.example',
 '(19) 90000-0001',
 SHA2('Metal@2026', 256),
 'Ricardo Almeida Santos',
 (SELECT id_endereco FROM ENDERECO WHERE cep = '11111-111')),

('22222222000122',
 'Polimeros Brasil Industria Ltda.',
 'PolyBrasil',
 'comercial@polybrasil.example',
 '(19) 90000-0002',
 SHA2('Plastico@2026', 256),
 'Fernanda Oliveira Costa',
 (SELECT id_endereco FROM ENDERECO WHERE cep = '22222-222')),

('33333333000133',
 'Transmov Equipamentos Industriais Ltda.',
 'Transmov Logistica',
 'vendas@transmov.example',
 '(19) 90000-0003',
 SHA2('Logistica@2026', 256),
 'Marcelo Henrique Ferreira',
 (SELECT id_endereco FROM ENDERECO WHERE cep = '33333-333')),

('44444444000144',
 'EletroPower Solucoes Energeticas Ltda.',
 'EletroPower',
 'atendimento@eletropower.example',
 '(19) 90000-0004',
 SHA2('Energia@2026', 256),
 'Camila Rodrigues Martins',
 (SELECT id_endereco FROM ENDERECO WHERE cep = '44444-444'));


-- =====================================================
-- 3. CATEGORIAS
-- =====================================================

INSERT INTO CATEGORIA (tipo)
VALUES
('Metalomecanica'),
('Movimentacao e Logistica'),
('Embalagem e plastico'),
('Energia');


-- =====================================================
-- 4. ANUNCIOS
-- 20 ANUNCIOS - 5 PARA CADA EMPRESA
-- =====================================================

INSERT INTO ANUNCIO
(nome, descricao, estado_conservacao,
 data_publicacao, condicao, valor, tipo_preco,
 cnpj_fk, id_categoria_fk, condicao_item)
VALUES

-- EMPRESA 1: HORIZONTE METAIS (ANUNCIOS 1 A 5)

( 'Torno CNC Industrial Haas ST-10',
 'Equipamento para usinagem de pecas metalicas cilindricas, com operacoes automatizadas de corte e acabamento.',
 'Bom', CURDATE(), 'ATIVO', 185000.00, 'NEGOCIAVEL',
 '11111111000111', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Usado'),

( 'Empilhadeira Industrial Toyota 8FG25',
 'Empilhadeira utilizada para transportar e elevar cargas, pallets e mercadorias em depositos e centros logisticos.',
 'Excelente', CURDATE(), 'ATIVO', 98000.00, 'FIXO',
 '11111111000111', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Movimentacao e Logistica'), 'Seminovo'),

( 'Inversor Solar Fronius Symo',
 'Inversor trifasico utilizado em sistemas fotovoltaicos comerciais para converter energia eletrica.',
 'Excelente', CURDATE(), 'ATIVO', 14500.00, 'NEGOCIAVEL',
 '11111111000111', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Energia'), 'Novo'),

( 'Chapas de Aco Carbono ASTM A36',
 'Chapas metalicas utilizadas na fabricacao de estruturas, suportes, equipamentos e componentes industriais.',
 'Excelente', CURDATE(), 'ATIVO', 12500.00, 'FIXO',
 '11111111000111', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Novo'),

( 'Resina Termoplastica de Polipropileno',
 'Granulos de polipropileno utilizados na fabricacao de embalagens, recipientes e componentes plasticos.',
 'Excelente', CURDATE(), 'ATIVO', 8900.00, 'NEGOCIAVEL',
 '11111111000111', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Novo'),


-- EMPRESA 2: POLYBRASIL (ANUNCIOS 6 A 10)

( 'Prensa Dobradeira CNC TRUMPF',
 'Maquina utilizada para dobrar chapas metalicas e fabricar componentes e estruturas industriais.',
 'Bom', CURDATE(), 'ATIVO', 210000.00, 'NEGOCIAVEL',
 '22222222000122', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Revisado'),

( 'Injetora Hidraulica Haitian Mars',
 'Equipamento utilizado para produzir pecas plasticas por injecao de material aquecido em moldes.',
 'Necessita reparos', CURDATE(), 'ATIVO', 32000.00, 'NEGOCIAVEL',
 '22222222000122', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Para pecas'),

( 'Lote de Cabos Eletricos de Cobre',
 'Cabos com condutores de cobre utilizados em instalacoes eletricas industriais, paineis e sistemas de distribuicao.',
 'Bom', CURDATE(), 'ATIVO', 7800.00, 'FIXO',
 '22222222000122', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Energia'), 'Seminovo'),

( 'Filme Stretch Transparente Industrial',
 'Filme plastico extensivel utilizado para envolver pallets e proteger mercadorias durante transporte e armazenamento.',
 'Excelente', CURDATE(), 'ATIVO', 4200.00, 'FIXO',
 '22222222000122', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Novo'),

( 'Lote de Pallets de Madeira',
 'Estruturas utilizadas para armazenar, acondicionar e transportar mercadorias em depositos e centros logisticos.',
 'Bom', CURDATE(), 'ATIVO', 3500.00, 'NEGOCIAVEL',
 '22222222000122', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Movimentacao e Logistica'), 'Usado'),


-- EMPRESA 3: TRANSMOV LOGISTICA (ANUNCIOS 11 A 15)

( 'Compressor Atlas Copco GA 15',
 'Compressor industrial utilizado para alimentar ferramentas pneumaticas e sistemas industriais.',
 'Regular', CURDATE(), 'ATIVO', 18500.00, 'NEGOCIAVEL',
 '33333333000133', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Energia'), 'Usado'),

( 'Centro de Usinagem Mazak VCN-430A',
 'Maquina utilizada para fresagem, perfuracao e fabricacao de pecas metalicas com precisao.',
 'Excelente', CURDATE(), 'ATIVO', 280000.00, 'FIXO',
 '33333333000133', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Seminovo'),

( 'Transpaleteira Eletrica Toyota BT Levio',
 'Equipamento utilizado para movimentar pallets e mercadorias em armazens e ambientes industriais.',
 'Bom', CURDATE(), 'ATIVO', 12500.00, 'NEGOCIAVEL',
 '33333333000133', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Movimentacao e Logistica'), 'Usado'),

( 'Lote de Lingotes de Aluminio',
 'Material metalico utilizado como materia-prima em processos de fundicao e fabricacao de componentes industriais.',
 'Excelente', CURDATE(), 'ATIVO', 16500.00, 'FIXO',
 '33333333000133', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Revisado'),

( 'Resina de Polietileno de Alta Densidade',
 'Materia-prima termoplastica utilizada na fabricacao de recipientes, caixas, pallets e embalagens rigidas.',
 'Excelente', CURDATE(), 'ATIVO', 7200.00, 'NEGOCIAVEL',
 '33333333000133', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Novo'),


-- EMPRESA 4: ELETROPOWER (ANUNCIOS 16 A 20)

( 'Maquina de Corte a Laser TRUMPF TruLaser 1030',
 'Equipamento utilizado para cortar chapas metalicas com tecnologia de laser e controle computadorizado.',
 'Bom', CURDATE(), 'ATIVO', 195000.00, 'NEGOCIAVEL',
 '44444444000144', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Revisado'),

( 'Envasadora Automatica Industrial Krones',
 'Equipamento utilizado em linhas industriais para enchimento e processamento de embalagens de bebidas.',
 'Regular', CURDATE(), 'ATIVO', 85000.00, 'FIXO',
 '44444444000144', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Usado'),

( 'Resina PET para Embalagens',
 'Resina utilizada na fabricacao de garrafas, recipientes e embalagens plasticas.',
 'Bom', CURDATE(), 'ATIVO', 5600.00, 'NEGOCIAVEL',
 '44444444000144', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Embalagem e plastico'), 'Seminovo'),

( 'Cimento Portland CP II',
 'Material utilizado em aplicacoes de construcao civil industrial, fabricacao de estruturas e obras de infraestrutura.',
 'Regular', CURDATE(), 'ATIVO', 1800.00, 'FIXO',
 '44444444000144', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Usado'),

( 'Chapas de Aco Inoxidavel AISI 304',
 'Chapas de aco inoxidavel utilizadas na fabricacao de equipamentos industriais, tanques, estruturas e componentes.',
 'Necessita reparos', CURDATE(), 'ATIVO', 4900.00, 'NEGOCIAVEL',
 '44444444000144', (SELECT id_categoria FROM CATEGORIA WHERE tipo = 'Metalomecanica'), 'Para pecas');


-- =====================================================
-- 5. MAQUINARIOS (10 REGISTROS)
-- =====================================================

INSERT INTO MAQUINARIO
(marca, modelo, ano_fabricacao,
 peso, dimensao, voltagem, id_anuncio_fk)
VALUES

('Haas', 'ST-10', 2020, 3585.00,
 '320x178x206 cm', '220V/440V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Torno CNC Industrial Haas ST-10')),

('Toyota', '8FG25', 2023, 3560.00,
 '364x115x216 cm', 'Nao aplicavel', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Empilhadeira Industrial Toyota 8FG25')),

('Fronius', 'Symo Advanced 20.0-3-M', 2024, 43.40,
 '72.5x51x22.5 cm', '380/220V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Inversor Solar Fronius Symo')),

('TRUMPF', 'TruBend 1060 Comfort', 2022, 5900.00,
 '276x191x250 cm', 'Trifasico', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Prensa Dobradeira CNC TRUMPF')),

('Haitian', 'MA900V/280', 2021, 3100.00,
 '435x116x187 cm', 'Trifasico', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Injetora Hidraulica Haitian Mars')),

('Atlas Copco', 'GA 15', 2019, 421.00,
 '160x80x150 cm', '220V/380V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Compressor Atlas Copco GA 15')),

('Mazak', 'VCN-430A', 2022, 5500.00,
 '264.8x222x277.8 cm', '220V/380V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Centro de Usinagem Mazak VCN-430A')),

('Toyota', 'BT Levio LWE160', 2021, 470.00,
 '170x72x130 cm', '24V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Transpaleteira Eletrica Toyota BT Levio')),

('TRUMPF', 'TruLaser 1030', 2020, 6000.00,
 '450x220x220 cm', 'Trifasico', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Maquina de Corte a Laser TRUMPF TruLaser 1030')),

('Krones', 'Modulfill', 2018, 4500.00,
 '350x200x250 cm', '380V', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Envasadora Automatica Industrial Krones'));


-- =====================================================
-- 6. RECURSOS (10 REGISTROS)
-- =====================================================

INSERT INTO RECURSO
(nome, peso, quantidade,
 unidade_medida, id_anuncio_fk)
VALUES

('Aco carbono ASTM A36',
 2000.00, 2.00, 'Tonelada(t)', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Chapas de Aco Carbono ASTM A36')),

('Polipropileno PP',
 1000.00, 1.00, 'Tonelada(t)', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Resina Termoplastica de Polipropileno')),

('Cabo de cobre',
 200.00, 500.00, 'Metro(m)', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Lote de Cabos Eletricos de Cobre')),

('Filme stretch',
 250.00, 50.00, 'Caixa', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Filme Stretch Transparente Industrial')),

('Pallet de madeira',
 750.00, 50.00, 'Pallet', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Lote de Pallets de Madeira')),

('Aluminio em lingotes',
 1500.00, 1.50, 'Tonelada(t)', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Lote de Lingotes de Aluminio')),

('Polietileno de alta densidade PEAD',
 800.00, 800.00, 'Quilo', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Resina de Polietileno de Alta Densidade')),

('Polietileno tereftalato PET',
 500.00, 500.00, 'Quilo', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Resina PET para Embalagens')),

('Cimento Portland CP II',
 2000.00, 40.00, 'Caixa', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Cimento Portland CP II')),

('Aco inoxidavel AISI 304',
 600.00, 600.00, 'Quilo', (SELECT id_anuncio FROM ANUNCIO WHERE nome = 'Chapas de Aco Inoxidavel AISI 304'));


COMMIT;


/*-- =====================================================
-- 7. CONSULTAS PARA CONFERIR OS CADASTROS
-- =====================================================

-- Conferir as quatro empresas

SELECT
    cnpj,
    razao_social,
    nome_fantasia,
    email
FROM EMPRESA;


-- Conferir os 20 anuncios e seus respectivos vendedores

SELECT
    A.id_anuncio,
    A.nome AS anuncio,
    E.nome_fantasia AS empresa,
    C.tipo AS categoria,
    A.condicao_item,
    A.estado_conservacao,
    A.valor
FROM ANUNCIO A
INNER JOIN EMPRESA E
    ON A.cnpj_fk = E.cnpj
INNER JOIN CATEGORIA C
    ON A.id_categoria_fk = C.id_categoria
ORDER BY A.id_anuncio;


-- Conferir a quantidade de anuncios por empresa

SELECT
    E.nome_fantasia,
    COUNT(A.id_anuncio) AS total_anuncios
FROM EMPRESA E
INNER JOIN ANUNCIO A
    ON E.cnpj = A.cnpj_fk
GROUP BY E.cnpj, E.nome_fantasia;


-- Conferir os maquinarios

SELECT
    A.nome,
    M.marca,
    M.modelo,
    M.ano_fabricacao
FROM MAQUINARIO M
INNER JOIN ANUNCIO A
    ON M.id_anuncio_fk = A.id_anuncio;


-- Conferir os recursos

SELECT
    A.nome,
    R.nome AS recurso,
    R.peso,
    R.quantidade,
    R.unidade_medida
FROM RECURSO R
INNER JOIN ANUNCIO A
    ON R.id_anuncio_fk = A.id_anuncio;
*/