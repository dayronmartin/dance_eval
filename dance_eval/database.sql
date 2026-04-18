-- ============================================================
-- Sistema de Evaluación de Danza en Tiempo Real
-- database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS dance_eval
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dance_eval;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username     VARCHAR(60)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    rol          ENUM('admin','jurado') NOT NULL DEFAULT 'jurado',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario admin por defecto (password: admin)
INSERT INTO usuarios (username, password, rol)
SELECT 'admin',
       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'admin'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE username = 'admin');

-- ------------------------------------------------------------
-- Tabla: participantes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS participantes (
    id_participante  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_acto      VARCHAR(120) NOT NULL,
    modalidad        ENUM('Solo','Dúo','Trío','Grupo','Mega') NOT NULL,
    categoria_edad   ENUM('Baby','Infantil','Juvenil','Senior') NOT NULL,
    estilo           VARCHAR(80)  NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_participante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: calificaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS calificaciones (
    id_calificacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_participante  INT UNSIGNED NOT NULL,
    id_usuario       INT UNSIGNED NOT NULL,
    nota_tecnica     DECIMAL(4,2) NOT NULL CHECK (nota_tecnica  BETWEEN 1 AND 10),
    nota_artistica   DECIMAL(4,2) NOT NULL CHECK (nota_artistica BETWEEN 1 AND 10),
    nota_musical     DECIMAL(4,2) NOT NULL CHECK (nota_musical  BETWEEN 1 AND 10),
    nota_escena      DECIMAL(4,2) NOT NULL CHECK (nota_escena   BETWEEN 1 AND 10),
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_calificacion),
    UNIQUE KEY uq_participante_jurado (id_participante, id_usuario),
    CONSTRAINT fk_calif_participante FOREIGN KEY (id_participante)
        REFERENCES participantes(id_participante) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_calif_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
