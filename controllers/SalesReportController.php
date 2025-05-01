<?php
require_once 'AdminController.php';
require_once 'config/config.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

class SalesReportController extends AdminController
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();  // Valida que el usuario tenga rol admin o superadmin.
        global $pdo;
        $this->pdo = $pdo;
    }

    // Muestra el reporte agrupado por día, con formulario para seleccionar rango.
    public function index()
    {
        // Por defecto, usa el día actual si no se indican fechas.
        $startDate = $_GET['start_date'] ?? date("Y-m-d");
        $endDate = $_GET['end_date'] ?? date("Y-m-d");

        // Validar que se hayan recibido ambas fechas
        if (empty($startDate) || empty($endDate)) {
            $_SESSION['flash'] = "Debe seleccionar ambas fechas para filtrar el reporte.";
            $_SESSION['flash_type'] = "alert";
            header("Location: index.php?controller=salesReport&action=index");
            exit;
        }

        // Consulta para agrupar ventas por día:
        $stmt = $this->pdo->prepare("SELECT DATE(created_at) AS sale_date, SUM(total) AS total_sales, COUNT(*) AS num_sales 
                                     FROM orders 
                                     WHERE status = 'completado' 
                                       AND DATE(created_at) BETWEEN ? AND ?
                                     GROUP BY DATE(created_at)
                                     ORDER BY sale_date ASC");
        $stmt->execute([$startDate, $endDate]);
        $salesGrouped = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/sales_report', [
            'salesGrouped' => $salesGrouped,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    // Genera el PDF del reporte agrupado por día.
    public function generatePDF()
    {
        $startDate = $_GET['start_date'] ?? date("Y-m-d");
        $endDate = $_GET['end_date'] ?? date("Y-m-d");

        // Consulta agrupada por día.
        $stmt = $this->pdo->prepare("SELECT DATE(created_at) AS sale_date, SUM(total) AS total_sales, COUNT(*) AS num_sales 
                                     FROM orders 
                                     WHERE status = 'completado' 
                                       AND DATE(created_at) BETWEEN ? AND ?
                                     GROUP BY DATE(created_at)
                                     ORDER BY sale_date ASC");
        $stmt->execute([$startDate, $endDate]);
        $salesGrouped = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generar el HTML para el PDF.
        ob_start();
        include __DIR__ . '/../views/admin/sales_report_pdf.php';
        $html = ob_get_clean();

        // Generar el PDF en orientación vertical (portrait)
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("ReporteVentas_{$startDate}_{$endDate}.pdf", ["Attachment" => false]);
        exit;
    }
}
?>