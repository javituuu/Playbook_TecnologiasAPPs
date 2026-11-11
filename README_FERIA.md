# 🏆 Guía de Feria de Ciencias · Playbook (Cloudflare Tunnel + QR + Reset Rápido)

Esta guía explica paso a paso cómo preparar y ejecutar **Playbook** en cualquier computador o notebook para la **Feria de Ciencias**, permitiendo:
1. Mostrar el **Panel del Administrador de Liga / Club / Coach** en la pantalla del notebook.
2. Que los visitantes escaneen un **Código QR** con su propio teléfono (usando sus datos móviles 4G/5G o Wi-Fi) y usen la **Web App del Jugador** conectada en tiempo real a tu computador mediante **Cloudflare Tunnel**.
3. **Reiniciar la base de datos en menos de 1 segundo** en cuanto se va un visitante para repetir el flujo desde cero con la siguiente persona.

---

## 🧠 ¿Cómo funciona la integración?

Para evitar tener que abrir múltiples túneles, configurar CORS o recompilar Ionic cada vez que Cloudflare entrega una URL nueva:
- La app de Ionic (`ionic/playbook-jugador`) se compila con ruta base `/jugador/` dentro de `laravel/public/jugador` y consume la API de forma relativa (`/api`).
- **Un único servidor (`php artisan serve`)** sirve todo:
  - `https://tu-tunel.trycloudflare.com/` 👉 **Panel Web (Administrador de Liga, Club y Coach)**
  - `https://tu-tunel.trycloudflare.com/jugador/` 👉 **Web App Móvil del Jugador (QR)**
  - `https://tu-tunel.trycloudflare.com/api/*` 👉 **API RESTful + Sanctum**

---

## 💻 1. Requisitos en tu otro PC / Notebook

Cuando clones el repositorio en el notebook que llevarás a la feria, asegúrate de tener instalado:

### En Arch Linux / Omarchy / Manjaro:
```bash
sudo pacman -S php composer nodejs npm cloudflared qrencode docker docker-compose
```

### En Ubuntu / Debian / WSL2:
```bash
sudo apt update && sudo apt install -y php php-cli php-mysql php-sqlite3 php-xml php-mbstring php-curl composer nodejs npm qrencode
# Instalar cloudflared:
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
```

### En macOS (Homebrew):
```bash
brew install php composer node cloudflared qrencode
```

### En Windows:
```powershell
winget install Cloudflare.cloudflared
```
*(En Windows puedes usar Git Bash o WSL2 para correr `./feria.sh`, o ejecutar los 3 comandos manuales de la sección 3).*

---

## ⚙️ 2. Configuración inicial en una máquina nueva (Solo 1 vez)

1. **Clonar el repositorio y entrar a la rama:**
   ```bash
   git clone <URL_DEL_REPO>
   cd playbook
   git checkout rama-laravel
   ```

2. **Instalar dependencias de Laravel:**
   ```bash
   cd laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configurar la Base de Datos (Elige Opción A u Opción B):**
   - **Opción A · Con Docker (MySQL 8.0, igual que en desarrollo):**
     ```bash
     docker compose up -d mysql
     php artisan migrate:fresh --seed
     ```
   - **Opción B · Sin Docker (SQLite ultrarrápido, ideal para notebook en feria):**
     Si no quieres levantar Docker en el notebook de la feria, edita `laravel/.env` y cambia `DB_CONNECTION=mysql` por:
     ```env
     DB_CONNECTION=sqlite
     ```
     Luego crea el archivo y migra:
     ```bash
     touch database/database.sqlite
     php artisan migrate:fresh --seed
     ```

4. **Instalar dependencias de Ionic:**
   ```bash
   cd ../ionic/playbook-jugador
   npm install
   ```

---

## 🚀 3. Cómo levantar todo el día de la Feria

### Forma Automática (1 solo comando)
Desde la raíz del proyecto, dale permisos de ejecución y lanza `feria.sh`:

```bash
chmod +x feria.sh
./feria.sh
```
*(Si ya compilaste Ionic antes y quieres arrancar en 2 segundos sin recompilar, usa `./feria.sh --skip-build`).*

El script automáticamente:
1. Compila Ionic dentro de `laravel/public/jugador`.
2. Reinicia la base de datos de demostración (`php artisan demo:reset`).
3. Levanta `php artisan serve` en el puerto `8000`.
4. Abre el túnel HTTPS con `cloudflared` y te dibuja el **Código QR gigante en la terminal**.

---

### Forma Manual (Si prefieres tener terminales separadas)

1. **Compilar la Web App del Jugador (solo 1 vez antes de la feria):**
   ```bash
   cd ionic/playbook-jugador
   npx ng build --configuration production --base-href /jugador/ --output-path ../../laravel/public/jugador
   ```

2. **Terminal 1 · Levantar Laravel:**
   ```bash
   cd laravel
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. **Terminal 2 · Levantar el túnel de Cloudflare:**
   ```bash
   cloudflared tunnel --url http://localhost:8000
   ```
   Cloudflare mostrará en consola un enlace como:
   `https://random-words-here.trycloudflare.com`

---

## 📱 4. Cómo mostrar el QR a los visitantes

Tienes **2 formas** de mostrar el QR:

1. **Desde la pantalla del Panel Web (Recomendado):**
   - Si abres el Panel de Liga usando la URL pública de Cloudflare (`https://xxxx.trycloudflare.com`), haz clic en el botón **"QR App Jugador"** en la barra lateral izquierda (`Modo Feria`).
   - Se abrirá un modal en pantalla con el código QR apuntando automáticamente a `https://xxxx.trycloudflare.com/jugador/`.
   - *(Si tienes el Panel abierto en `http://localhost:8000`, puedes pegar la URL de Cloudflare en el campo de texto del modal y el QR se actualiza en vivo al instante).*

2. **En el celular del visitante (Acceso en 1 clic):**
   - Cuando la persona escanea el QR, se abre la pantalla de Login de Playbook Jugador.
   - Al final del formulario hay una sección **"Acceso rápido · Modo Feria"** con dos botones:
     - **Entrar como Javier Benavides (UDD Titans)**: Entra con 1 toque sin tener que tipear correo ni contraseña.
     - **Entrar como Diego Pérez (Multi-liga LoL + Valorant)**: Ideal para mostrar el choque de horarios entre dos ligas.

---

## 🔄 5. Cómo resetear la Base de Datos entre visitantes (< 0.3 seg)

En cuanto termina la demostración con una persona y quieres dejar todo limpio como al principio para el siguiente visitante, tienes **2 opciones instantáneas**:

### Opción 1: Botón en el Panel Web (1 clic)
En la barra lateral izquierda del Panel Web (bajo **Modo Feria**), haz clic en:
> 🔄 **Reiniciar Demo**

Esto vacía todas las tablas de negocio, vuelve a sembrar el escenario inicial (con el conflicto de Diego Pérez, los horarios bloqueados y el roster incompleto de UDD Titans) en milisegundos y **mantiene tu sesión iniciada** en el panel.

### Opción 2: Comando Artisan por consola
En cualquier terminal dentro de la carpeta `laravel/`, ejecuta:
```bash
php artisan demo:reset
```
A diferencia de `migrate:fresh --seed` (que destruye y recrea las tablas en varios segundos), `demo:reset` limpia los registros, reinicia los contadores de IDs y ejecuta los seeders en **~100 a 300 ms**.

---

## 🎭 6. Guion sugerido de Demostración (Flujo de 2 minutos)

1. **El visitante escanea el QR** en su teléfono y toca **"Entrar como Javier Benavides"**.
   - Ve su **Agenda** con los partidos confirmados y entra a **Citaciones** (tiene 3 citaciones pendientes, incluyendo el partido contra *PUC Paladins* el martes 03/11 a las 20:00).
   - El visitante acepta o rechaza una citación desde su celular, o agrega un **Horario bloqueado** en su pestaña **Perfil**.
2. **En tu computador (Panel del Administrador):**
   - En **Equipos -> UDD Titans**, muestras cómo el bloqueo horario que acaba de crear el visitante impacta inmediatamente la **Matriz Semanal de Disponibilidad** del equipo, y cómo puedes promover a Valentina Soto y Felipe Araya para completar los 5 titulares.
   - En **Fixture**, muestras la **Violación Dura (`CHOQUE_MULTI_LIGA`)** de Diego Pérez (citado a LoL 20:00 y Valorant 20:30 el mismo día).
   - Usas el **Programador de Partidos** para buscar una ventana limpia sugerida por el motor (ej. Lunes 20:00), reprogramas el partido en conflicto y haces clic en **Publicar fixture**.
3. **Se va el visitante:**
   - Haces clic en **"Reiniciar Demo"** en la barra lateral (o corres `php artisan demo:reset`) y todo vuelve al estado inicial en menos de medio segundo para la siguiente persona.
