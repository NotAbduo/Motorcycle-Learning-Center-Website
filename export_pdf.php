<?php
require_once 'db.php';
require('fpdf.php');
$pdf = new FPDF('L', 'mm', 'A4'); // Landscape A4
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
// Title
$pdf->Cell(0, 10, 'Trainees Report', 0, 1, 'C');
$pdf->Ln();
// Step 1: Headers & fields (match Excel export + batch + try_road)
$headers = [
    'Source', 'Batch', 'Name', 'National ID', 'Phone Number',
    'Gender', 'Added By', 'Date', 'Quiz', 'Try 8', 'Try Road',
    'Sign', 'Payment', 'Status'
];
$fields = [
    'source', 'batch', 'name', 'national_id', 'phone_number',
    'gender', 'added_by', 'date', 'quiz', 'number_of_trails', 'try_road',
    'sign', 'payment', 'is_active'
];
// Step 2: Calculate max widths dynamically
$pdf->SetFont('Arial', '', 10);
$maxWidths = [];
foreach ($headers as $header) {
    $maxWidths[] = $pdf->GetStringWidth($header);
}
$query = $conn->query("SELECT * FROM trainees ORDER BY date DESC");
$rows = [];
while ($row = $query->fetch_assoc()) {
    $rows[] = $row;
    foreach ($fields as $i => $field) {
        $text = match($field) {
            'sign'    => $row[$field] ? 'Yes' : 'No',
            'payment' => $row[$field] ? 'Paid' : 'Unpaid',
            'date'    => substr($row[$field], 0, 10), // short format
            'number_of_trails' => $row[$field] ?? '0',
            'try_road' => $row[$field] ?? '0',
            'is_active' => $row[$field] ? 'Ongoing' : 'Completed',
            'gender'   => ucfirst($row[$field]),
            default   => $row[$field]
        };
        $width = $pdf->GetStringWidth($text);
        if ($width > $maxWidths[$i]) {
            $maxWidths[$i] = $width;
        }
    }
}
// Step 3: Add padding
$padding = 6;
foreach ($maxWidths as &$w) {
    $w += $padding;
}
// Step 4: Scale to fit page if too wide
$totalWidth = array_sum($maxWidths);
$pageWidth = 277; // Landscape A4 usable width
if ($totalWidth > $pageWidth) {
    $scale = $pageWidth / $totalWidth;
    foreach ($maxWidths as &$w) {
        $w *= $scale;
    }
    $totalWidth = array_sum($maxWidths);
}
// Step 5: Center the table
$startX = ($pageWidth - $totalWidth) / 2 + 10;
// Step 6: Print headers
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX($startX);
foreach ($headers as $i => $col) {
    $pdf->Cell($maxWidths[$i], 10, $col, 1, 0, 'C');
}
$pdf->Ln();
// Step 7: Print rows
$pdf->SetFont('Arial', '', 10);
foreach ($rows as $row) {
    $pdf->SetX($startX);
    foreach ($fields as $i => $field) {
        $text = match($field) {
            'sign'    => $row[$field] ? 'Yes' : 'No',
            'payment' => $row[$field] ? 'Paid' : 'Unpaid',
            'date'    => substr($row[$field], 0, 10), // short format
            'number_of_trails' => $row[$field] ?? '0',
            'try_road' => $row[$field] ?? '0',
            'is_active' => $row[$field] ? 'Ongoing' : 'Completed',
            'gender'   => ucfirst($row[$field]),
            default   => $row[$field]
        };
        $pdf->Cell($maxWidths[$i], 10, $text, 1, 0, 'C');
    }
    $pdf->Ln();
}
$pdf->Output();
?>