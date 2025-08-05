# Prueba de Concepto: Sincronización unidireccional entre Sistemas A y B

Este repositorio contiene dos proyectos independientes (`Sistema A` (Plataforma de recuross humanos) y `Sistema B` (Plataforma de gestion de tiempos)) que demuestran una sincronización de datos en tiempo real unidireccional utilizando **RabbitMQ** y **PostgreSQL**.

## 🧩 Descripción General

- **Sistema A**: Se encarga de generar eventos y enviarlos a través de RabbitMQ.
- **Sistema B**: Escucha los eventos desde RabbitMQ y actualiza su propia base de datos.

Ambos sistemas están desarrollados en Laravel y utilizan contenedores Docker para levantar los servicios de **RabbitMQ** y **PostgreSQL**.

## 🐘 Requisitos Previos

- Docker y Docker Compose
- PHP >= 8.3
- Composer
- Laravel (puedes usarlo vía Composer sin instalación global)

---

## 📁 Estructura del Repositorio

```bash
/
├── test-sync-buk - (Sistema A)/
│   └── .env.example
├── test-sync-buk-b - (Sistema B)/
│   └── .env.example
└── docker/
    └── docker-compose.yml
```

## 🚀 Instrucciones para Iniciar la Prueba de Concepto

### 1. Clonar el repositorio

- git clone https://github.com/diegoruizr/test-sync-buk.git
- cd test-sync-buk


### 2. Levantar los servicios con Docker

Desde la raíz del repositorio:

```bash
docker-compose -f docker/docker-compose.yml up -d
```

Esto levantará:

- RabbitMQ

    - Puerto de mensajes: 5672

    - Interfaz web: http://localhost:15672

    - Usuario/Contraseña: guest / guest

- PostgreSQL

    - Puerto: 5432

    - Usuario, contraseña y base de datos configurables en el archivo docker-compose.yml

### 3. Configurar el entorno de cada sistema

- 🔧 Sistema A

```bash
cd test-sync-buck/
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

- 🔧 Sistema B

```bash
cd test-sync-buck-b/
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

**Importante:** Asegúrate de que las variables de entorno (.env) tengan los mismos datos de conexión que los servicios definidos en Docker (host, puertos, claves, nombres de cola, etc.).

### 4. Iniciar los workers para procesamiento de eventos

El sistema B debe tener en ejecución el queue:work para que funcione la sincronización.

- 🔧 Sistema B

```bash
cd test-sync-buck-b/
php artisan queue:work
```

### 5. Iniciar Tinker para ejecucion de prueba desde el sistema A

- 🔧 Sistema A
```bash
cd test-sync-buck/
php artisan tinker
```

Ejecutar 
```bash
\App\Models\Department::create(['name' => 'Nomina', 'cost_center_code' => '500']);
```

## 🧪 Verificación de RabbitMQ

Accede al panel de administración de RabbitMQ:

- URL: http://localhost:15672
- Usuario: guest
- Contraseña: guest

Desde ahí puedes monitorear las colas, los mensajes enviados, los consumidores y más.

## 📌 Notas

- Puedes modificar los puertos de PostgreSQL o RabbitMQ en el archivo docker-compose.yml.
- Si estás trabajando en una red local o en WSL, asegúrate de que los contenedores estén accesibles correctamente según tu sistema operativo.
- Puedes usar herramientas como TablePlus, DBeaver o PgAdmin para visualizar los datos de las bases PostgreSQL.
- Los eventos sincronizados entre los sistemas deben estar alineados tanto en la estructura como en el nombre de la cola usada.