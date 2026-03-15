<?php
// Recorte de código para incluir lógica de negocios y FPDF

require('../../pdf/fpdf/fpdf.php');

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        global $rw_perfil;
        // Logo
        if (file_exists('../../assets/img/logo.png')) {
            $this->Image('../../assets/img/logo.png', 10, 8, 33);
        }
        // Arial bold 15
        $this->SetFont('Arial', 'B', 12);
        // Movernos a la derecha
        $this->Cell(80);
        // Título
        $this->Cell(30, 10, utf8_decode($rw_perfil['nombre_comercial']), 0, 0, 'C');
        // Salto de línea
        $this->Ln(20);
    }

    // Pie de página
    function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// ... Lógica de conexión y datos ...
?>
