# 📂 Workspace Explorer 

Un explorador visual de proyectos y archivos desarrollado en PHP con interfaz moderna inspirada en el tema **Dracula**, soporte para tema claro/oscuro y actualización automática en tiempo real.

Ideal para servidores locales, entornos de desarrollo y workspaces con múltiples proyectos.

---

# ✨ Características

- 📁 Exploración recursiva de carpetas y archivos
- 🎨 Tema Dracula oscuro y claro
- 🔄 Auto-actualización cada 10 segundos
- 📊 Estadísticas globales:
  - cantidad de proyectos
  - carpetas
  - archivos
  - tamaño total
- 📦 Visualización jerárquica de directorios
- ⚡ Renderizado dinámico con AJAX
- 🧠 Preserva carpetas abiertas tras actualizar
- 📱 Diseño responsive
- 🪶 Sin dependencias externas
- 🐘 Hecho únicamente con PHP + JavaScript vanilla

---

# 🖼️ Vista general

El sistema:

1. Escanea automáticamente una carpeta base
2. Detecta proyectos dentro de ella
3. Muestra estadísticas globales
4. Permite expandir carpetas y navegar archivos
5. Se actualiza automáticamente sin recargar la página

---

# 📂 Estructura principal

```text
/var/www/html/
├── proyecto1/
├── proyecto2/
├── proyecto3/
└── index.php
```

Cada carpeta dentro de `basePath` es considerada un proyecto independiente.

---

# ⚙️ Configuración

Dentro del archivo `index.php`:

```php
$basePath = '/var/www/html/';
```

Define la carpeta raíz que será escaneada.

También puedes excluir carpetas:

```php
$excludeDirs = ['.', '..', '.git', 'node_modules'];
```

Y excluir archivos:

```php
$excludeFiles = ['README.md', '.htaccess'];
```

---

# 🚀 Instalación

## 1. Clonar el repositorio

```bash
git clone https://github.com/usuario/workspace-explorer.git
```

---

## 2. Copiar el archivo al servidor web

Por ejemplo:

```bash
sudo cp index.php /var/www/html/
```

---

## 3. Dar permisos

```bash
sudo chmod 755 /var/www/html/index.php
```

---

## 4. Abrir en el navegador

```text
http://localhost/index.php
```

---

# 🧰 Requisitos

- PHP 7.4 o superior
- Apache o Nginx
- Permisos de lectura sobre las carpetas escaneadas

---

# 🔄 Sistema de auto-actualización

El frontend utiliza:

```javascript
fetch('?ajax=1')
```

para consultar el backend cada 10 segundos y actualizar:

- proyectos
- archivos
- carpetas
- tamaños
- estructura visual

sin necesidad de refrescar manualmente la página.

---

# 🎨 Sistema de temas

Incluye:

- 🌙 Dracula Dark
- ☀️ Dracula Light

El tema seleccionado se guarda automáticamente usando:

```javascript
localStorage
```

---

# 📊 Estadísticas globales

El sistema calcula automáticamente:

- cantidad total de proyectos
- cantidad total de carpetas
- cantidad total de archivos
- tamaño total del workspace

Utilizando funciones recursivas en PHP.

---

# 🧠 Funciones importantes

## `scanDirectory()`

Escanea directorios recursivamente.

## `countRecursive()`

Cuenta archivos, carpetas y tamaño total.

## `getProjectsData()`

Genera toda la estructura de datos de proyectos.

## `renderDashboard()`

Renderiza dinámicamente el frontend.

---

# 📁 Tipos de archivos soportados

El explorador detecta extensiones y muestra iconos automáticamente:

| Extensión | Icono |
|---|---|
| php | 🐘 |
| js | ⚡ |
| css | 🎨 |
| html | 🌐 |
| md | 📖 |
| pdf | 📕 |
| zip | 📦 |
| py | 🐍 |
| mp3 | 🎵 |
| mp4 | 🎬 |

---

# 📱 Responsive Design

La interfaz utiliza:

```css
grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
```

permitiendo adaptarse automáticamente a:

- desktop
- notebook
- tablet
- móvil

---

# 🔒 Seguridad

El proyecto incluye:

- `htmlspecialchars()` para prevenir XSS
- validación básica de rutas
- exclusión de directorios sensibles
- escape HTML en frontend

---

# ⚡ Tecnologías utilizadas

- PHP
- HTML5
- CSS3
- JavaScript Vanilla
- AJAX
- LocalStorage

---

# 💡 Casos de uso

Perfecto para:

- servidores de desarrollo
- dashboards internos
- workspaces de programación
- hosting local
- laboratorios de pruebas
- exploradores de proyectos
- NAS caseros
- Raspberry Pi
- servidores Linux

---

# 🛠️ Posibles mejoras futuras

- búsqueda de archivos
- breadcrumbs
- sistema de autenticación
- WebSockets en tiempo real
- previews de imágenes
- editor integrado
- soporte drag & drop
- compresión ZIP
- soporte multiusuario

---

# 📸 Capturas sugeridas

Puedes agregar screenshots aquí:

<p align="center">
  <a href="https://imgur.com/abc123">
    <img src="https://i.imgur.com/d1tZiOH.png" width="700">
  </a>
</p>

# 📄 Licencia

MIT License

---

# 👨‍💻 Autor

Desarrollado por Gustavo Godoy.

---

# ⭐ Recomendación

Si el proyecto te sirve:

- dale una estrella en GitHub ⭐
- haz fork 🍴
- contribuye 🚀
