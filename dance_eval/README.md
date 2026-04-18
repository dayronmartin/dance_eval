# 🩰 Dance Eval — Sistema de Evaluación de Danza en Tiempo Real

Sistema completo para calificar actuaciones de danza con panel de administración en tiempo real y app de calificación para jurados.

---

## 📁 Estructura de Archivos

```
dance_eval/
├── config.php              # Conexión PDO centralizada + helpers de sesión
├── index.php               # Login unificado (admin / jurado)
├── panel_admin.php         # Dashboard de administrador (4 tabs)
├── panel_jurado.php        # Interfaz de calificación para jurado
├── logout.php              # Destruye sesión y redirige
├── database.sql            # Script SQL (CREATE + usuario admin)
├── README.md               # Este archivo
└── api/
    ├── monitor.php         # GET  – datos del monitor en vivo
    ├── ranking.php         # GET  – ranking ponderado (filtrable)
    ├── jurados.php         # CRUD – gestión de usuarios jurado
    ├── participantes.php   # CRUD – gestión de participantes
    └── calificaciones.php  # POST – envío de calificaciones
```

---

## ⚙️ Requisitos

| Componente | Versión mínima |
|------------|---------------|
| PHP        | 8.0           |
| MySQL      | 5.7 / 8.0     |
| Extensión  | PDO + pdo_mysql|

> Funciona con **XAMPP**, **WAMP**, **LAMP**, **Laragon**, o cualquier servidor PHP estándar.

---

## 🚀 Instalación

### 1. Clonar / Copiar archivos

Coloca la carpeta `dance_eval/` dentro de tu `htdocs/` (XAMPP) o `www/` (WAMP/Laragon).

### 2. Crear la base de datos

Importa el archivo `database.sql` desde phpMyAdmin **o** por línea de comandos:

```bash
mysql -u root -p < database.sql
```

El script:
- Crea la base de datos `dance_eval` si no existe.
- Crea las 3 tablas (`usuarios`, `participantes`, `calificaciones`).
- Inserta el usuario admin por defecto.

### 3. Configurar la conexión

Edita `config.php` y ajusta las constantes:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'dance_eval');
define('DB_USER', 'root');   // ← tu usuario MySQL
define('DB_PASS', '');       // ← tu contraseña MySQL
```

### 4. Abrir en el navegador

```
http://localhost/dance_eval/
```

---

## 🔑 Credenciales por defecto

| Usuario | Contraseña | Rol   |
|---------|-----------|-------|
| admin   | admin     | admin |

> ⚠️ **Cambiar la contraseña del admin en producción.**

---

## 🎯 Fórmula de Puntaje

```
Puntaje Final = (Técnica × 0.40) + (Artística × 0.30) + (Musicalidad × 0.20) + (Escena × 0.10)
```

El puntaje final es el **promedio ponderado de todos los jurados** que evaluaron a ese participante.

---

## 📱 Flujo de uso

### Administrador

1. Ingresar a `index.php` con `admin` / `admin`.
2. **Tab "Participantes"** → registrar los actos del evento.
3. **Tab "Jurados"** → crear cuentas para cada jurado (rol automático).
4. **Tab "Monitor en Vivo"** → observar en tiempo real qué jurados ya votaron.
5. **Tab "Ranking"** → ver posiciones finales filtradas por categoría.

### Jurado

1. Ingresar a `index.php` con sus credenciales.
2. Seleccionar el participante del `<select>`.
3. Mover los 4 sliders (1–10) para Técnica, Artística, Musicalidad y Escena.
4. Presionar **"Enviar Calificación"** → sólo se permite **un voto por participante**.

---

## 🛡️ Seguridad implementada

- Contraseñas con `password_hash()` (bcrypt, cost 12).
- Consultas con **PDO + sentencias preparadas** (prevención SQL Injection).
- Validación de sesión y rol en cada endpoint.
- `session_regenerate_id()` al autenticarse.
- Cookies `httponly` + `samesite=Strict`.
- `UNIQUE KEY` en `calificaciones(id_participante, id_usuario)` para prevenir doble voto por race condition.

---

## 🎨 Tech Stack Frontend

- **Bootstrap 5.3** (dark theme) vía CDN
- **Bootstrap Icons 1.11** vía CDN
- **Google Fonts** – Bebas Neue + DM Sans
- Fetch API (AJAX sin recarga de página)
- CSS custom properties para theming coherente
