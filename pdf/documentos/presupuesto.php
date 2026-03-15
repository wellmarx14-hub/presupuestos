<?php
/*-------------------------
Autor: Wellmar Carvajal Mendez
Web: systemswell.com
Mail: soporte@systemswell.com
---------------------------*/
ob_start();
session_start();
/* Connect To Database*/
include("../../config/db.php");
include("../../config/conexion.php");
$session_id = session_id();
$sql_count = mysqli_query($con, "select * from tmp ");
$count = mysqli_num_rows($sql_count);
if ($count == 0) {
    echo "<script>alert('No hay productos agregados al presupuesto')</script>";
    echo "<script>window.close();</script>";
    exit;
}

require('../../pdf/fpdf/fpdf.php');

//Variables por GET
$cliente = intval($_GET['cliente']);
$descripcion = mysqli_real_escape_string($con, (strip_tags($_REQUEST['descripcion'], ENT_QUOTES)));

//Fin de variables por GET
$sql = mysqli_query($con, "select LAST_INSERT_ID(id) as last from presupuestos order by id desc limit 0,1 ");
$rw = mysqli_fetch_array($sql);
$numero = (isset($rw['last'])) ? $rw['last'] + 1 : 1;
$perfil = mysqli_query($con, "select * from perfil limit 0,1"); //Obtengo los datos de la emprea
$rw_perfil = mysqli_fetch_array($perfil);
if (!$rw_perfil) $rw_perfil = array();

$sql_cliente = mysqli_query($con, "select * from clientes where id='$cliente' limit 0,1"); //Obtengo los datos del cliente
$rw_cliente = mysqli_fetch_array($sql_cliente);
if (!$rw_cliente) $rw_cliente = array();

class PDF extends FPDF
{
    function Header()
    {
        global $rw_perfil;
        // Logo
        if (file_exists('../../assets/img/logo.png')) {
            $this->Image('../../assets/img/logo.png', 10, 8, 33);
        } elseif (file_exists('../../assets/img/logo.jpg')) {
             $this->Image('../../assets/img/logo.jpg', 10, 8, 33);
        }
        
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(80);
        $this->Cell(100, 10, utf8_decode($rw_perfil['nombre_comercial']), 0, 0, 'R');
        $this->Ln(5);
        
        $this->SetFont('Arial', '', 9);
        $this->Cell(180, 10, utf8_decode("Dirección: " . $rw_perfil['direccion']), 0, 0, 'R');
        $this->Ln(5);
        $this->Cell(180, 10, utf8_decode("Teléfono: " . $rw_perfil['telefono']), 0, 0, 'R');
        $this->Ln(5);
        $this->Cell(180, 10, utf8_decode("Email: " . $rw_perfil['email']), 0, 0, 'R');
        
        $this->Ln(15);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->Cell(0, 10, utf8_decode('© systemswell.com ') . date('Y'), 0, 0, 'R');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFillColor(232, 232, 232);

// Titulo Presupuesto
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(52, 152, 219);
$pdf->Cell(0, 15, utf8_decode('PRESUPUESTO DE TRABAJO'), 0, 1, 'C', true);
$pdf->Ln(5);

// Info Presupuesto y Fecha
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(52, 152, 219);
$pdf->Cell(95, 7, '', 0, 0);
$pdf->Cell(45, 7, utf8_decode('PRESUPUESTO #'), 1, 0, 'C', true);
$pdf->Cell(45, 7, utf8_decode('FECHA'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(95, 7, '', 0, 0);
$pdf->Cell(45, 7, $numero, 1, 0, 'C');
$pdf->Cell(45, 7, date("d/m/Y"), 1, 1, 'C');
$pdf->Ln(10);

// Detalles del Cliente
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, utf8_decode('Detalles del cliente'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 6, utf8_decode("Nombre: " . (isset($rw_cliente['nombre']) ? $rw_cliente['nombre'] : '') . "\n" .
    "Dirección: " . (isset($rw_cliente['direccion']) ? $rw_cliente['direccion'] : '') . "\n" .
    "E-mail: " . (isset($rw_cliente['email']) ? $rw_cliente['email'] : '') . "\n" .
    "Teléfono: " . (isset($rw_cliente['telefono']) ? $rw_cliente['telefono'] : '')), 1, 'L');
$pdf->Ln(10);

// Descripción del trabajo
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, utf8_decode('Descripción del trabajo'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 6, utf8_decode($descripcion), 1, 'L');
$pdf->Ln(10);

// Tabla de Items
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(52, 152, 219);
$pdf->Cell(90, 10, utf8_decode('Descripción'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_decode('Cantidad'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_decode('Precio unitario'), 1, 0, 'C', true);
$pdf->Cell(35, 10, utf8_decode('Total'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

$query = mysqli_query($con, "select * from tmp order by id");
$suma = 0;
while ($row = mysqli_fetch_array($query)) {
    $total = $row['cantidad'] * $row['precio'];
    $total_f = number_format($total, 2, '.', '');
    
    $pdf->Cell(90, 8, utf8_decode($row['descripcion']), 1, 0, 'L');
    $pdf->Cell(30, 8, $row['cantidad'], 1, 0, 'C');
    $pdf->Cell(30, 8, number_format($row['precio'], 2), 1, 0, 'R');
    $pdf->Cell(35, 8, number_format($total, 2), 1, 1, 'R');
    
    $suma += $total;
    // Guardar en detalle
    mysqli_query($con, "INSERT INTO `detalle` (`id`, `descripcion`, `cantidad`, `precio`, `id_presupuesto`) VALUES (NULL, '" . $row['descripcion'] . "', '" . $row['cantidad'] . "', '" . $row['precio'] . "', '$numero');");
}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(52, 152, 219);
$pdf->Cell(150, 10, 'TOTAL COP ', 0, 0, 'R');
$pdf->Cell(35, 10, number_format($suma, 2), 1, 1, 'R');
$pdf->Ln(10);

// Nota
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 5, utf8_decode("Nota: Este presupuesto no es un contrato o una factura. Es nuestra mejor estimación al precio total para completar el trabajo indicado anteriormente, basado en nuestra inspección inicial, pero puede estar sujeto a cambios. Si los precios cambian o se requieren piezas y mano de obra adicionales, le informaremos antes de proceder con el trabajo."), 0, 'J');
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 10, utf8_decode("Si tiene alguna consulta relacionada con este presupuesto, por favor contáctenos:"), 0, 1, 'C');
$pdf->Cell(0, 5, utf8_decode($rw_perfil['nombre_comercial'] . ", " . $rw_perfil['telefono'] . ", " . $rw_perfil['email']), 0, 1, 'C');

// Guardar los datos del presupuesto
$fecha = date("Y-m-d H:i:s");
$sql_insert = "INSERT INTO `presupuestos` (`id`, `fecha`, `id_cliente`, `descripcion`, `monto`) VALUES (NULL, '$fecha', '$cliente', '$descripcion', '$suma');";
mysqli_query($con, $sql_insert);
mysqli_query($con, "delete from tmp");

$pdf->Output('I', 'Presupuesto.pdf');
ob_end_flush();
?>
