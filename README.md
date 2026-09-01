# 🔧 Sistema de Gestión para Taller

<p align="center">
  <strong>Sistema web integral para la gestión de operaciones, inventario, ventas y reparaciones de un taller.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white">
  <img src="https://img.shields.io/badge/MySQL%2FMariaDB-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white">
  <img src="https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white">
</p>

---

## 📌 Sobre el proyecto

**Sistema de Gestión para Taller** es una aplicación web desarrollada para centralizar y optimizar los procesos administrativos y operativos de un taller.

El sistema permite gestionar desde un mismo lugar las **ventas, reparaciones, inventario, técnicos y reportes**, proporcionando una visión general de la operación mediante un dashboard.

El proyecto fue desarrollado aplicando una arquitectura organizada basada en **Laravel**, utilizando controladores, modelos, servicios, solicitudes de validación, migraciones y vistas Blade.

### 🎯 Objetivo

Digitalizar y facilitar la gestión de un taller, reduciendo procesos manuales y permitiendo tener la información organizada y disponible en tiempo real.

---

## ✨ Funcionalidades principales

### 📊 Dashboard

* Resumen general de la operación.
* Indicadores de ventas.
* Información de inventario.
* Seguimiento de reparaciones.
* Métricas para facilitar la toma de decisiones.

### 💰 Gestión de ventas

* Registro de ventas.
* Detalle de productos vendidos.
* Control de descuentos.
* Consulta de ventas.
* Visualización del detalle de cada venta.

### 🔧 Gestión de reparaciones

* Registro de órdenes de reparación.
* Gestión de máquinas asociadas a cada orden.
* Actualización del estado de las reparaciones.
* Consulta detallada de órdenes.
* Edición y eliminación de órdenes.
* Asociación de técnicos y trabajos realizados.

### 📦 Gestión de inventario

* Administración de productos.
* Registro de movimientos de inventario.
* Ajustes de existencias.
* Búsqueda de productos.
* Control de entradas y salidas.
* Actualización y eliminación de productos.

### 👨‍🔧 Gestión de técnicos

* Registro de técnicos.
* Actualización de información.
* Activación y desactivación de técnicos.
* Gestión centralizada del personal técnico.

### 📈 Reportes

El sistema cuenta con diferentes módulos de consulta y análisis:

* Reportes de ventas.
* Reportes de reparaciones.
* Reportes de inventario.
* Reportes de técnicos.
* Reportes financieros.
* Resúmenes generales.
* Exportación de información.

---

## 🛠️ Tecnologías utilizadas

| Tecnología          | Uso                                    |
| ------------------- | -------------------------------------- |
| **PHP 8.2+**        | Lenguaje principal                     |
| **Laravel 12**      | Framework backend                      |
| **Blade**           | Motor de plantillas                    |
| **MySQL / MariaDB** | Base de datos                          |
| **Vite**            | Compilación y desarrollo frontend      |
| **JavaScript**      | Interactividad                         |
| **CSS**             | Diseño y estilos                       |
| **PhpSpreadsheet**  | Exportación y manejo de archivos Excel |
| **Git / GitHub**    | Control de versiones                   |

---

## 🏗️ Arquitectura del proyecto

El proyecto utiliza una estructura organizada siguiendo las convenciones de Laravel.

```text
sistema-taller/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   │
│   ├── Models/
│   ├── Services/
│   │
│   └── Providers/
│
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
│
├── resources/
│   ├── js/
│   └── views/
│
├── routes/
│   └── web.php
│
├── public/
│   ├── css/
│   └── img/
│
├── tests/
│
├── composer.json
└── package.json
```

### 💡 Separación por responsabilidades

Uno de los aspectos importantes del proyecto es la separación de responsabilidades.

Los **Controllers** gestionan las peticiones HTTP, mientras que diferentes procesos de negocio se encuentran organizados dentro de **Services**, por ejemplo:

```text
app/
├── Services/
│   ├── Dashboard/
│   ├── Inventario/
│   ├── Reportes/
│   ├── Tecnicos/
│   └── Ventas/
```

Esto permite mantener el código más organizado, reutilizable y fácil de mantener.

---

## 🗄️ Modelo de datos

El sistema trabaja con diferentes entidades relacionadas con la operación del taller:

```text
Clientes
   │
   └── Órdenes de reparación
            │
            ├── Máquinas
            └── Técnicos

Productos
   │
   └── Movimientos de inventario

Ventas
   │
   └── Detalle de venta
            │
            └── Productos
```

La estructura de la base de datos se administra mediante **migraciones de Laravel**, permitiendo crear y modificar el esquema de manera controlada.

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU-USUARIO/TU-REPOSITORIO.git
```

Entrar al proyecto:

```bash
cd sistema-taller
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Configurar las variables de entorno

Crear el archivo `.env`:

```bash
cp .env.example .env
```

En Windows también puedes copiar manualmente `.env.example` y renombrarlo como:

```text
.env
```

Después configurar las credenciales de la base de datos.

Ejemplo:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_taller
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generar la clave de Laravel

```bash
php artisan key:generate
```

### 5. Ejecutar las migraciones

```bash
php artisan migrate
```

Si el proyecto utiliza datos iniciales:

```bash
php artisan db:seed
```

### 6. Instalar dependencias frontend

```bash
npm install
```

### 7. Ejecutar el proyecto

En una terminal:

```bash
php artisan serve
```

En otra terminal:

```bash
npm run dev
```

Después acceder a:

```text
http://127.0.0.1:8000
```

---

## 📂 Módulos del sistema

```text
📊 Dashboard
│
├── 💰 Ventas
│
├── 🔧 Reparaciones
│
├── 📦 Inventario
│   ├── Productos
│   ├── Movimientos
│   └── Ajustes
│
├── 👨‍🔧 Técnicos
│
└── 📈 Reportes
    ├── Ventas
    ├── Reparaciones
    ├── Inventario
    ├── Técnicos
    ├── Financiero
    └── Resumen
```

---

## 🔐 Seguridad y buenas prácticas

El proyecto contempla diferentes prácticas para mantener una estructura segura y organizada:

* Variables sensibles almacenadas mediante `.env`.
* `.env` excluido del repositorio mediante `.gitignore`.
* Validación de datos mediante **Form Requests**.
* Uso de **migraciones** para controlar la estructura de la base de datos.
* Separación de lógica mediante **Services**.
* Organización modular de controladores.
* Manejo de errores mediante páginas personalizadas.

> ⚠️ Nunca subas las credenciales reales de la base de datos, API keys u otras variables sensibles al repositorio.

---

## 📊 Exportación de información

El sistema incorpora **PhpSpreadsheet** para generar y trabajar con archivos de Excel.

Esto permite convertir información del sistema en reportes que pueden ser utilizados para análisis, control administrativo y seguimiento de la operación.

---

## 🧪 Pruebas

Laravel incluye PHPUnit para realizar pruebas automatizadas.

Ejecutar:

```bash
php artisan test
```

---

## 👨‍💻 Autor

**Juan José Crespo Aldana**

Estudiante de **Ingeniería de Sistemas** enfocado en desarrollo de software, aplicaciones web y bases de datos.

### 💻 Tecnologías

```text
Java • JavaScript • Python • PHP
Laravel • Spring Boot
MySQL • MariaDB
Git • GitHub
```

---

## 📄 Licencia

Este proyecto se encuentra bajo la licencia **MIT**.

---

<p align="center">
  ⭐ Si este proyecto te resulta interesante, considera darle una estrella al repositorio.
</p>

<p align="center">
  <strong>Desarrollado con Laravel 🚀</strong>
</p>
