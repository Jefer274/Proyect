<?php
session_start();
include "conexion.php";

// Obtener datos directamente desde PHP
try {
    // Conteos de almacenes
    $almacencomunicacion = 0;
    $almacenredes = 0;
    $almacensoftware = 0;

    // Conteos de talleres
    $tallercomunicacion = 0;
    $tallerredes = 0;
    $tallersoftware = 0;

    // Consultar almacencomunicacion
    $result = $conn->query("SELECT COUNT(*) as count FROM almacencomunicacion");
    if ($result) {
        $row = $result->fetch_assoc();
        $almacencomunicacion = (int)$row['count'];
    }

    // Consultar almacenredes
    $result = $conn->query("SELECT COUNT(*) as count FROM almacenredes");
    if ($result) {
        $row = $result->fetch_assoc();
        $almacenredes = (int)$row['count'];
    }

    // Consultar almacensoftware
    $result = $conn->query("SELECT COUNT(*) as count FROM almacensoftware");
    if ($result) {
        $row = $result->fetch_assoc();
        $almacensoftware = (int)$row['count'];
    }

    // Consultar tallercomunicaciondatos
    $result = $conn->query("SELECT COUNT(*) as count FROM tallercomunicaciondatos");
    if ($result) {
        $row = $result->fetch_assoc();
        $tallercomunicacion = (int)$row['count'];
    }

    // Consultar tallerredes
    $result = $conn->query("SELECT COUNT(*) as count FROM tallerredes");
    if ($result) {
        $row = $result->fetch_assoc();
        $tallerredes = (int)$row['count'];
    }

    // Consultar tallersoftware
    $result = $conn->query("SELECT COUNT(*) as count FROM tallersoftware");
    if ($result) {
        $row = $result->fetch_assoc();
        $tallersoftware = (int)$row['count'];
    }

    // Calcular totales
    $totalAlmacenes = $almacencomunicacion + $almacenredes + $almacensoftware;
    $totalTalleres = $tallercomunicacion + $tallerredes + $tallersoftware;
    $totalComunicacion = $almacencomunicacion + $tallercomunicacion;
    $totalRedes = $almacenredes + $tallerredes;
    $totalSoftware = $almacensoftware + $tallersoftware;

    $conexionExitosa = true;

} catch (Exception $e) {
    $conexionExitosa = false;
    $errorMessage = $e->getMessage();

    // Valores por defecto en caso de error
    $almacencomunicacion = 0;
    $almacenredes = 0;
    $almacensoftware = 0;
    $tallercomunicacion = 0;
    $tallerredes = 0;
    $tallersoftware = 0;
    $totalAlmacenes = 0;
    $totalTalleres = 0;
    $totalComunicacion = 0;
    $totalRedes = 0;
    $totalSoftware = 0;
}

// Obtener historial reciente
$historial = array();
try {
    $queries = array(
        "SELECT 'Almacén Comunicación' as origen, Id, FechaIngreso as fecha, CONCAT('ID: ', Id, ' - ', IFNULL(General, 'N/A')) as detalle FROM almacencomunicacion WHERE FechaIngreso IS NOT NULL ORDER BY FechaIngreso DESC LIMIT 2",
        "SELECT 'Taller Comunicación' as origen, Id, FechaIngreso as fecha, CONCAT('ID: ', Id, ' - ', IFNULL(Descripcion, 'N/A')) as detalle FROM tallercomunicaciondatos WHERE FechaIngreso IS NOT NULL ORDER BY FechaIngreso DESC LIMIT 2",
        "SELECT 'Almacén Redes' as origen, Id, fechaingreso as fecha, CONCAT('ID: ', Id, ' - ', IFNULL(general, 'N/A')) as detalle FROM almacenredes WHERE fechaingreso IS NOT NULL ORDER BY fechaingreso DESC LIMIT 2",
        "SELECT 'Almacén Software' as origen, id as Id, fechaingreso as fecha, CONCAT('ID: ', id, ' - ', IFNULL(general, 'N/A')) as detalle FROM almacensoftware WHERE fechaingreso IS NOT NULL ORDER BY fechaingreso DESC LIMIT 2"
    );

    foreach ($queries as $query) {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $historial[] = $row;
            }
        }
    }

    usort($historial, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    $historial = array_slice($historial, 0, 8);

} catch (Exception $e) {
    for ($i = 1; $i <= 5; $i++) {
        $historial[] = array(
            'origen' => 'Sistema',
            'Id' => $i,
            'fecha' => date('Y-m-d H:i:s', strtotime("-$i hours")),
            'detalle' => "Actividad de ejemplo $i"
        );
    }
}
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    #dashboard-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #1f2937;
        min-height: 100vh;
        padding: 20px;
    }

    .dashboard {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
        color: white;
    }

    .header h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .header p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .user-info {
        font-size: 1rem;
        opacity: 0.8;
        margin-top: 10px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-value {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .stat-label {
        font-size: 1rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .almacen { color: #4CAF50; }
    .taller { color: #2196F3; }
    .comunicacion { color: #FF9800; }
    .redes { color: #9C27B0; }
    .software { color: #F44336; }

    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .chart-title {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.4rem;
        color: #333;
        font-weight: 600;
    }

    .history-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }

    .history-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 600;
    }

    .refresh-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .refresh-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .history-timeline {
        max-height: 400px;
        overflow-y: auto;
    }

    .history-item {
        display: flex;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }

    .history-item:hover {
        background: #e3f2fd;
        transform: translateX(5px);
    }

    .history-time {
        font-weight: 600;
        color: #667eea;
        min-width: 120px;
        font-size: 0.9rem;
    }

    .history-action {
        flex: 1;
        margin-left: 15px;
    }

    .history-table {
        font-size: 0.9rem;
        color: #666;
        font-weight: 500;
    }

    .history-details {
        color: #333;
        margin-top: 5px;
    }

    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 10px;
        background: #4CAF50;
    }

    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #ef4444;
    }

    .success-message {
        background: #dcfce7;
        color: #065f46;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #10b981;
    }

    @media (max-width: 768px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .header h1 {
            font-size: 2rem;
        }
    }
</style>

<div id="dashboard-container">
    <div class="dashboard">
        <div class="header">
            <h1>Dashboard - Sistema de Gestión</h1>
            <p>Panel de control - Almacenes y Talleres</p>
            <div class="user-info">
                <span id="currentTime"></span> | Usuario: <?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Admin'; ?>
            </div>
        </div>

        <?php if (!$conexionExitosa): ?>
        <div class="error-message">
            ⚠️ Error de conexión a la base de datos: <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php else: ?>
        <div class="success-message">
            ✅ Conexión exitosa - Datos actualizados: <?php echo date('d/m/Y H:i:s'); ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value almacen"><?php echo $totalAlmacenes; ?></div>
                <div class="stat-label">Total Almacenes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value taller"><?php echo $totalTalleres; ?></div>
                <div class="stat-label">Total Talleres</div>
            </div>
            <div class="stat-card">
                <div class="stat-value comunicacion"><?php echo $totalComunicacion; ?></div>
                <div class="stat-label">Comunicación</div>
            </div>
            <div class="stat-card">
                <div class="stat-value redes"><?php echo $totalRedes; ?></div>
                <div class="stat-label">Redes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value software"><?php echo $totalSoftware; ?></div>
                <div class="stat-label">Software</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-title">📈 Distribución por Categorías</div>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-container">
                <div class="chart-title">📊 Almacenes - Talleres</div>
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>

        <div class="history-section">
            <div class="history-header">
                <h2 class="history-title">🕒 Actividades Recientes</h2>
                <button class="refresh-btn" onclick="cargarContenido('dashboard_integrado.php')">🔄 Actualizar</button>
            </div>
            <div class="history-timeline">
                <?php if (empty($historial)): ?>
                    <div class="history-item">
                        <div class="status-indicator"></div>
                        <div class="history-time">--:--</div>
                        <div class="history-action">
                            <div class="history-table">Sistema</div>
                            <div class="history-details">No hay actividades registradas</div>
                        </div>
                    </div>
                <?php else: ?>
                <?php foreach ($historial as $item): ?>
                <div class="history-item">
                    <div class="status-indicator"></div>
                    <div class="history-time">
                        <?php
                        try {
                            if (strpos($item['fecha'], '/') !== false) {
                                $fecha = DateTime::createFromFormat('d/m/Y H:i:s', $item['fecha']);
                                if (!$fecha) {
                                    $fecha = DateTime::createFromFormat('d/m/Y', $item['fecha']);
                                }
                            } else {
                                $fecha = new DateTime($item['fecha']);
                            }

                            if ($fecha) {
                                echo $fecha->format('H:i') . '<br><small>' . $fecha->format('d/m') . '</small>';
                            } else {
                                echo '--:--<br><small>--/--</small>';
                            }
                        } catch (Exception $e) {
                            echo '--:--<br><small>--/--</small>';
                        }
                        ?>
                    </div>
                    <div class="history-action">
                        <div class="history-table"><?php echo htmlspecialchars($item['origen']); ?></div>
                        <div class="history-details"><?php echo htmlspecialchars($item['detalle']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Datos desde PHP
    const dashboardData = {
        almacencomunicacion: <?php echo $almacencomunicacion; ?>,
        almacenredes: <?php echo $almacenredes; ?>,
        almacensoftware: <?php echo $almacensoftware; ?>,
        tallercomunicacion: <?php echo $tallercomunicacion; ?>,
        tallerredes: <?php echo $tallerredes; ?>,
        tallersoftware: <?php echo $tallersoftware; ?>
    };

    // Función para actualizar la hora
    function updateTime() {
        const timeElement = document.getElementById('currentTime');
        if (!timeElement) return;

        const now = new Date();
        const timeString = now.toLocaleString('es-PE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        timeElement.textContent = timeString;
    }

    // Crear gráfico de categorías
    function createCategoryChart() {
        const canvas = document.getElementById('categoryChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const comunicacionTotal = dashboardData.almacencomunicacion + dashboardData.tallercomunicacion;
        const redesTotal = dashboardData.almacenredes + dashboardData.tallerredes;
        const softwareTotal = dashboardData.almacensoftware + dashboardData.tallersoftware;

        const labels = [];
        const data = [];
        const colors = [];

        if (comunicacionTotal > 0) {
            labels.push('Comunicación');
            data.push(comunicacionTotal);
            colors.push('#FF9800');
        }
        if (redesTotal > 0) {
            labels.push('Redes');
            data.push(redesTotal);
            colors.push('#9C27B0');
        }
        if (softwareTotal > 0) {
            labels.push('Software');
            data.push(softwareTotal);
            colors.push('#F44336');
        }

        if (data.length === 0) {
            ctx.font = "16px Arial";
            ctx.fillStyle = "#666";
            ctx.textAlign = "center";
            ctx.fillText("No hay datos para mostrar", ctx.canvas.width/2, ctx.canvas.height/2);
            return;
        }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Crear gráfico de comparación
    function createComparisonChart() {
        const canvas = document.getElementById('comparisonChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        const almacenData = [
            dashboardData.almacencomunicacion || 0,
            dashboardData.almacenredes || 0,
            dashboardData.almacensoftware || 0
        ];

        const tallerData = [
            dashboardData.tallercomunicacion || 0,
            dashboardData.tallerredes || 0,
            dashboardData.tallersoftware || 0
        ];

        const maxValue = Math.max(...almacenData, ...tallerData);
        const stepSize = maxValue > 50 ? Math.ceil(maxValue / 10) : (maxValue > 10 ? 5 : 1);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Comunicación', 'Redes', 'Software'],
                datasets: [{
                    label: 'Almacén',
                    data: almacenData,
                    backgroundColor: '#4CAF50',
                    borderColor: '#4CAF50',
                    borderWidth: 1
                }, {
                    label: 'Taller',
                    data: tallerData,
                    backgroundColor: '#2196F3',
                    borderColor: '#2196F3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: stepSize,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' registros';
                            }
                        }
                    }
                }
            }
        });
    }

    // Inicializar cuando el contenido esté listo
    function inicializarDashboard() {
        updateTime();
        setInterval(updateTime, 1000);
        createCategoryChart();
        createComparisonChart();
    }

    // Ejecutar inmediatamente
    if (document.getElementById('categoryChart') && document.getElementById('comparisonChart')) {
        inicializarDashboard();
    } else {
        // Si los elementos no están listos, esperar un poco
        setTimeout(inicializarDashboard, 100);
    }
})();
</script>