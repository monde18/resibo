<?php
require('fpdf/fpdf.php');
require_once 'config.php'; // contains $pdo

// Get payment ID from query string or form
$payment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$payment_id) {
    die("Missing or invalid payment ID.");
}

// Fetch record from DB
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$payment_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("No record found for ID " . htmlspecialchars($payment_id));
}

// Assign values from DB
$date   = $row['date'];
$orno   = $row['orno'] ?? '';
$payor  = $row['payor'] ?? '';
$item1  = $row['nature1'] ?? '';
$amt1   = $row['amount1'] ?? 0;
$item2  = $row['nature2'] ?? '';
$amt2   = $row['amount2'] ?? 0;
$item3  = $row['nature3'] ?? '';
$amt3   = $row['amount3'] ?? 0;
$total  = $row['total'] ?? ($amt1 + $amt2 + $amt3);

// Convert total to words (simple helper)
function convertNumberToWords($num) {
    $ones = ['','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE','TEN',
        'ELEVEN','TWELVE','THIRTEEN','FOURTEEN','FIFTEEN','SIXTEEN','SEVENTEEN','EIGHTEEN','NINETEEN'];
    $tens = ['','','TWENTY','THIRTY','FORTY','FIFTY','SIXTY','SEVENTY','EIGHTY','NINETY'];

    if ($num == 0) return 'ZERO';
    if ($num < 20) return $ones[$num];
    if ($num < 100) return $tens[intval($num/10)] . (($num%10)?' '.$ones[$num%10]:'');
    if ($num < 1000) return $ones[intval($num/100)].' HUNDRED'.(($num%100)?' '.convertNumberToWords($num%100):'');
    return (string)$num;
}
$amountWords = strtoupper(convertNumberToWords(intval($total))) . " PESOS ONLY";

// === PDF init: match your calibration size (100×200 mm) ===
$pdf = new FPDF('P', 'mm', array(200,100));
$pdf->AddPage();

// Background image
$pdf->Image(__DIR__ . '/resibo.png', 0, 0, 100, 200);

$pdf->SetFont('Arial','',10);

// === Overlay fields using your mm coordinates ===
$pdf->SetXY(10.5, 49.9); $pdf->Cell(40, 5, $date, 0, 0);
$pdf->SetXY(70.5, 49.9); $pdf->Cell(50, 5, $orno, 0, 0);
$pdf->SetXY(9.5, 65.7);  $pdf->Cell(80, 5, $payor, 0, 0);

$pdf->SetXY(9.5, 65.7);  $pdf->Cell(120, 5, $item1, 0, 0);
$pdf->SetXY(70.8, 83.9); $pdf->Cell(40, 5, number_format($amt1,2), 0, 0, 'R');

$pdf->SetXY(8.7, 88.9);  $pdf->Cell(120, 5, $item2, 0, 0);
$pdf->SetXY(69.5, 90.0); $pdf->Cell(40, 5, number_format($amt2,2), 0, 0, 'R');

$pdf->SetXY(8.7, 95.3);  $pdf->Cell(120, 5, $item3, 0, 0);
$pdf->SetXY(68.7, 95.8); $pdf->Cell(40, 5, number_format($amt3,2), 0, 0, 'R');

$pdf->SetXY(70.5, 132.2); $pdf->Cell(40, 5, number_format($total,2), 0, 0, 'R');
$pdf->SetXY(7.9, 141.4);  $pdf->MultiCell(90, 5, $amountWords);

$pdf->SetXY(22.9, 153.3); $pdf->Cell(5, 5, "X", 0, 0);

// Save + display
$pdf->Output('F', __DIR__ . '/receipt_overlay.pdf');
$pdf->Output('I', 'receipt_overlay.pdf');
