<?php
session_start();

// Si no está logueado, redirigir a login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$tipo = $_SESSION['tipo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel - Inventario</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .user-bar {
      background: #333;
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 14px;
    }
    .logout-btn {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 3px;
      cursor: pointer;
    }
    .logout-btn:hover { background: #c0392b; }
  </style>
</head>
<body>
  <!-- Barra de usuario -->
  <div class="user-bar">
    <span>Usuario: <strong><?php echo $usuario; ?></strong> (<?php echo ucfirst($tipo); ?>)</span>
    <button class="logout-btn" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
  </div>

  <!-- encabezado -->
  <div class="header">
    <div class="header-left"></div>
    <div class="header-right"></div>
  </div>

  <!-- contenedor -->
  <div class="container">
    <!-- sidebar -->
    <div class="sidebar">
        <div class="logo-container" style="text-align:center; margin:-5px 0 12px 0;">
            <img src="logo.png" alt="Logo" style="max-width:70px; height:auto;">
        </div>
      <h2 style="cursor:pointer;" onclick="cargarContenido('dashboard_integrado.php')">DeepMindware</h2>
      <ul>
        <li>
          <a href="#" onclick="toggleSubmenu(event)">Inventario ▾</a>
          <ul class="submenu">
            <li><a href="#" onclick="cargarContenido('tallercomunicaciondatos/productos.php'); return false;">Taller de comunicación de datos</a></li>
            <li><a href="#" onclick="cargarContenido('almacencomunicacion/almacencomunicacion.php'); return false;">Almacén de comunicación y datos</a></li>
            <li><a href="#" onclick="cargarContenido('tallersoftware/tallersoftware.php'); return false;">Taller de software y sistemas</a></li>
            <li><a href="#" onclick="cargarContenido('almacensoftware/almacensoftware.php'); return false;">Almacén de software y sistemas</a></li>
            <li><a href="#" onclick="cargarContenido('almacenredes/almacenredes.php'); return false;">Almacén de redes y soporte</a></li>
            <li><a href="#" onclick="cargarContenido('tallerredes/tallerredes.php'); return false;">Taller de redes y soporte</a></li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- main -->
    <div class="main">
      <div id="contenido">
        <h1>Bienvenido <?php echo $usuario; ?></h1>
        <?php if($tipo == 'admin'): ?>
          <p>Tienes permisos de <strong>Administrador</strong>. Puedes ver, agregar, editar y eliminar registros.</p>
        <?php else: ?>
          <p>Tienes permisos de <strong>Usuario</strong>. Solo puedes ver y buscar registros.</p>
        <?php endif; ?>
        <p>Seleccione una opción del menú.</p>
      </div>
    </div>
  </div>

<script>
// Variable global para saber el tipo de usuario
const tipoUsuario = '<?php echo $tipo; ?>';

// Variable para controlar si Chart.js está cargado
let chartJsLoaded = false;
let chartJsLoading = false;

function cargarContenido(pagina){
  const xhr = new XMLHttpRequest();
  xhr.open("GET", pagina, true);
  xhr.onload = function(){
    if (this.status === 200) {
      document.getElementById("contenido").innerHTML = this.responseText;

      // Si es dashboard y Chart.js no está cargado, cargarlo
      if (pagina.includes('dashboard_integrado.php')) {
        if (!window.Chart && !chartJsLoading) {
          chartJsLoading = true;
          const script = document.createElement('script');
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
          script.onload = function() {
            chartJsLoaded = true;
            chartJsLoading = false;
            console.log('Chart.js cargado correctamente');
            // ESPERAR a que Chart.js esté disponible y luego ejecutar scripts
            ejecutarScriptsDashboard();
          };
          script.onerror = function() {
            chartJsLoading = false;
            console.error('Error al cargar Chart.js');
          };
          document.head.appendChild(script);
        } else if (window.Chart) {
          // Chart.js ya está cargado
          console.log('Chart.js ya disponible');
          // Ejecutar los scripts después de un pequeño delay
          setTimeout(ejecutarScriptsDashboard, 100);
        }
      }

      // Si no es admin, ocultar botones después de cargar contenido
      if (tipoUsuario !== 'admin') {
        setTimeout(ocultarBotonesAdmin, 100);
      }

      // Inicializar funciones de importación según la página cargada
      if (pagina.includes('productos.php')) {
        setTimeout(() => inicializarImportacion('productos'), 100);
      } else if (pagina.includes('almacencomunicacion.php')) {
        setTimeout(() => inicializarImportacion('almacencomunicacion'), 100);
      } else if (pagina.includes('tallersoftware.php')) {
        setTimeout(() => inicializarImportacion('tallersoftware'), 100);
      } else if (pagina.includes('almacensoftware.php')) {
        setTimeout(() => inicializarImportacion('almacensoftware'), 100);
      } else if (pagina.includes('almacenredes.php')) {
        setTimeout(() => inicializarImportacion('almacenredes'), 100);
      } else if (pagina.includes('tallerredes.php')) {
        setTimeout(() => inicializarImportacion('tallerredes'), 100);
      }

    } else {
      document.getElementById("contenido").innerHTML = "<p>Error cargando contenido.</p>";
    }
  };
  xhr.send();
}

// NUEVA FUNCIÓN: Ejecutar scripts del dashboard
function ejecutarScriptsDashboard() {
  const scripts = document.getElementById("contenido").getElementsByTagName("script");
  for (let script of scripts) {
    if (script.textContent.includes('dashboardData')) {
      try {
        eval(script.textContent);
        console.log('Scripts del dashboard ejecutados correctamente');
      } catch (error) {
        console.error('Error ejecutando scripts del dashboard:', error);
      }
      break;
    }
  }
}

function enviarFormulario(form, url) {
  // Solo admin puede enviar formularios
  if (tipoUsuario !== 'admin') {
    alert('Solo los administradores pueden realizar esta acción');
    return false;
  }

  const formData = new FormData(form);
  const xhr = new XMLHttpRequest();
  xhr.open("POST", url, true);
  xhr.onload = function () {
    if (this.status === 200) {
      document.getElementById("contenido").innerHTML = this.responseText;
    } else {
      document.getElementById("contenido").innerHTML = "<p>Error al enviar formulario.</p>";
    }
  };
  xhr.send(formData);
}

function toggleSubmenu(e){
  e.preventDefault();
  const submenu = document.querySelector(".submenu");
  submenu.style.display = submenu.style.display === "block" ? "none" : "block";
}

/* ---------- ELIMINAR (CORREGIDO) ---------- */
function mostrarConfirmar(el, id, carpeta) {
  // Solo admin puede eliminar
  if (tipoUsuario !== 'admin') {
    alert('Solo los administradores pueden eliminar registros');
    return false;
  }

  if (el.nextElementSibling && el.nextElementSibling.classList.contains("btn-confirmar")) return;

  const btn = document.createElement("button");
  btn.textContent = "Confirmar";
  btn.className = "btn-confirmar";

  btn.onclick = function() {
    if (confirm("¿Seguro que quieres eliminar este registro?")) {
      let ruta;

      if (carpeta === 'tallercomunicaciondatos') {
        ruta = carpeta + "/eliminar.php?id=" + id;
      } else if (carpeta === 'almacencomunicacion') {
        ruta = carpeta + "/da2.php?id=" + id;
      } else if (carpeta === 'tallersoftware') {
        ruta = carpeta + "/da3.php?id=" + id;
      } else if (carpeta === 'almacensoftware') {
        ruta = carpeta + "/da4.php?id=" + id;
      } else if (carpeta === 'almacenredes') {
        ruta = carpeta + "/da5.php?id=" + id;
      } else if (carpeta === 'tallerredes') {
        ruta = carpeta + "/da6.php?id=" + id;
      } else {
        ruta = (carpeta ? carpeta + '/' : '') + "eliminar.php?id=" + id;
      }

      fetch(ruta, { method: "GET" })
        .then(response => response.text())
        .then(() => {
          el.closest("tr").remove();
          // Recargar contenido para módulos específicos
          if (carpeta === 'tallercomunicaciondatos') {
            cargarContenido('tallercomunicaciondatos/productos.php');
          } else if (carpeta === 'almacencomunicacion') {
            cargarContenido('almacencomunicacion/almacencomunicacion.php');
          } else if (carpeta === 'tallersoftware') {
            cargarContenido('tallersoftware/tallersoftware.php');
          } else if (carpeta === 'almacensoftware') {
            cargarContenido('almacensoftware/almacensoftware.php');
          } else if (carpeta === 'almacenredes') {
            cargarContenido('almacenredes/almacenredes.php');
          } else if (carpeta === 'tallerredes') {
            cargarContenido('tallerredes/tallerredes.php');
          }
        })
        .catch(err => console.error("Error eliminando:", err));
    }
    btn.remove();
  };

  el.insertAdjacentElement("afterend", btn);
}

/* ---------- BUSCAR Y LIMPIAR ---------- */
function filtrarTabla() {
  const input = document.getElementById("buscador");
  if (!input) return;
  const filter = input.value.toLowerCase().trim();
  const tbody = document.querySelector("#tabla-productos tbody");
  if (!tbody) return;

  const prev = tbody.querySelector(".no-results");
  if (prev) prev.remove();

  let hayCoincidencia = false;
  tbody.querySelectorAll("tr").forEach(tr => {
    const textoFila = tr.textContent.toLowerCase();
    const mostrar = filter === "" || textoFila.includes(filter);
    tr.style.display = mostrar ? "" : "none";
    if (mostrar) hayCoincidencia = true;
  });

  if (!hayCoincidencia) {
    const colCount = document.querySelector("#tabla-productos thead tr th") ?
                     document.querySelector("#tabla-productos thead tr").children.length : 1;
    const tr = document.createElement("tr");
    tr.className = "no-results";
    tr.innerHTML = `<td colspan="${colCount}" style="text-align:center; padding:10px;">No se encontraron resultados</td>`;
    tbody.appendChild(tr);
  }
}

function limpiarBusqueda() {
  const input = document.getElementById("buscador");
  if (!input) return;
  input.value = "";
  filtrarTabla();
}

// Función para ocultar botones si no es admin
function ocultarBotonesAdmin() {
  // Ocultar botón Registrar
  const btnRegistrar = document.querySelectorAll('.btn-primary');
  btnRegistrar.forEach(btn => {
    if (btn.textContent.includes('Registrar')) {
      btn.style.display = 'none';
    }
  });

  // Ocultar botones Editar y Eliminar
  const botonesEditar = document.querySelectorAll('.btn-edit');
  const botonesEliminar = document.querySelectorAll('.btn-delete');

  botonesEditar.forEach(btn => btn.style.display = 'none');
  botonesEliminar.forEach(btn => btn.style.display = 'none');

  // Opcional: mostrar mensaje en lugar de botones
  const columnasAcciones = document.querySelectorAll('td:last-child');
  columnasAcciones.forEach(celda => {
    if (celda.querySelector('.btn-edit') || celda.querySelector('.btn-delete')) {
      celda.innerHTML = '<span style="color: #999; font-size: 12px;">Solo lectura</span>';
    }
  });
}

// Funciones globales para exportación
function toggleExportMenu(event) {
  event.stopPropagation();
  const menu = document.getElementById('exportMenu');
  if (menu) {
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
  }
}

function exportarPDF(modulo) {
  window.open(`exportar_pdf.php?modulo=${modulo}`, '_blank');
  const menu = document.getElementById('exportMenu');
  if (menu) menu.style.display = 'none';
}

function mostrarImportar() {
  const modal = document.getElementById('modalImportar');
  if (modal) {
    modal.style.display = 'block';
  }
  const menu = document.getElementById('exportMenu');
  if (menu) menu.style.display = 'none';
}

function cerrarImportar() {
  const modal = document.getElementById('modalImportar');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Función para manejar importación (debe ejecutarse después de cargar contenido)
function inicializarImportacion(modulo) {
  const form = document.getElementById('formImportar');
  if (form) {
    form.onsubmit = function(e) {
      e.preventDefault();
      const archivo = document.getElementById('archivoExcel').files[0];
      if (!archivo) {
        alert('Por favor selecciona un archivo');
        return;
      }

      const formData = new FormData();
      formData.append('archivo', archivo);
      formData.append('modulo', modulo);

      // Mostrar indicador de carga
      const btnSubmit = form.querySelector('button[type="submit"]');
      const textoOriginal = btnSubmit.textContent;
      btnSubmit.textContent = 'Procesando...';
      btnSubmit.disabled = true;

      fetch('importar_excel.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(result => {
        alert(result);
        cerrarImportar();
        // Recargar la tabla según el módulo
        recargarModulo(modulo);
      })
      .catch(err => {
        alert('Error al importar archivo: ' + err.message);
        console.error(err);
      })
      .finally(() => {
        btnSubmit.textContent = textoOriginal;
        btnSubmit.disabled = false;
      });
    };
  }
}

function recargarModulo(modulo) {
  switch(modulo) {
    case 'productos':
      cargarContenido('tallercomunicaciondatos/productos.php');
      break;
    case 'almacencomunicacion':
      cargarContenido('almacencomunicacion/almacencomunicacion.php');
      break;
    case 'tallersoftware':
      cargarContenido('tallersoftware/tallersoftware.php');
      break;
    case 'almacensoftware':
      cargarContenido('almacensoftware/almacensoftware.php');
      break;
    case 'almacenredes':
      cargarContenido('almacenredes/almacenredes.php');
      break;
    case 'tallerredes':
      cargarContenido('tallerredes/tallerredes.php');
      break;
  }
}

// Cerrar menú de exportación al hacer click fuera
document.addEventListener('click', function() {
  const menu = document.getElementById('exportMenu');
  if (menu) menu.style.display = 'none';
});

// Cargar dashboard automáticamente al iniciar
window.addEventListener('load', function() {
  cargarContenido('dashboard_integrado.php');
});
</script>
</body>
</html>