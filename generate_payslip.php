<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_pdo.php';
require_once 'fpdf.php';
require_once __DIR__ . '/FPDI/src/autoload.php';
use setasign\Fpdi\Fpdi;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$instructorId = $_GET['instructor'] ?? '';
$selectedYM = $_GET['month'] ?? date('Y-m');

if (empty($instructorId) || !preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    die('Invalid parameters');
}

[$year, $month] = explode('-', $selectedYM);
$payMonth = $selectedYM . '-01';

/* Fetch instructor */
$instStmt = $pdo->prepare("SELECT name, national_id FROM employees WHERE national_id = :id");
$instStmt->execute([':id' => $instructorId]);
$instructor = $instStmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) die('Instructor not found');

/* Get default rate for this instructor */
$rateStmt = $pdo->prepare("SELECT default_rate FROM instructor_rates WHERE Instructor_ID = :id");
$rateStmt->execute([':id' => $instructorId]);
$defaultRate = (float)($rateStmt->fetchColumn() ?: 8.0);

/* 
 * Calculate total payment using custom rates when available
 * This matches the logic used in billing.php and custom_rates.php
 * 
 * LEFT JOIN with custom_hourly_rates to get custom rate if set,
 * otherwise use the default rate
 */
$paymentStmt = $pdo->prepare("
    SELECT 
        abl.Hours,
        COALESCE(chr.custom_rate, :default_rate) as rate
    FROM approved_billing_logs abl
    LEFT JOIN custom_hourly_rates chr 
        ON abl.ID = chr.log_id 
        AND chr.Instructor_ID = :id2
        AND chr.pay_month = :pay_month
    WHERE abl.Instructor_ID = :id
      AND YEAR(abl.Date) = :yr
      AND MONTH(abl.Date) = :mn
");
$paymentStmt->execute([
    ':id' => $instructorId,
    ':id2' => $instructorId,
    ':yr' => $year, 
    ':mn' => $month,
    ':default_rate' => $defaultRate,
    ':pay_month' => $payMonth
]);

$totalPayment = 0.0;
$totalHours = 0.0;

while ($row = $paymentStmt->fetch(PDO::FETCH_ASSOC)) {
    $hours = (float)$row['Hours'];
    $rate = (float)$row['rate'];
    $totalPayment += ($hours * $rate);
    $totalHours += $hours;
}

/* Convert numbers to words */
function convert_number_to_words($n) {
    $n = (int)$n;
    $ones = [0=>'zero',1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve',13=>'thirteen',14=>'fourteen',15=>'fifteen',16=>'sixteen',17=>'seventeen',18=>'eighteen',19=>'nineteen'];
    $tens = [2=>'twenty',3=>'thirty',4=>'forty',5=>'fifty',6=>'sixty',7=>'seventy',8=>'eighty',9=>'ninety'];
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intdiv($n,10)] . (($n%10)?' '.$ones[$n%10]:'');
    if ($n < 1000) return $ones[intdiv($n,100)] . ' hundred' . (($n%100)?' ' . convert_number_to_words($n%100):'');
    $units = [1000000=>'million',1000=>'thousand'];
    foreach ($units as $div=>$name)
        if ($n >= $div)
            return convert_number_to_words(intdiv($n,$div)).' '.$name.(($n%$div)?' '.convert_number_to_words($n%$div):'');
    return '';
}

function amount_to_words($amount) {
    $amount = round(floatval($amount), 3);
    $integer = (int)floor($amount);
    $fraction = (int)round(($amount - $integer) * 1000);
    $r = ucfirst(convert_number_to_words($integer)) . ' Omani Rials';
    if ($fraction > 0) $r .= ' and ' . convert_number_to_words($fraction) . ' baisa';
    return $r;
}

/* --- Use Image Background Instead of A4 Template --- */
$pdf = new Fpdi();
$pdf->AddPage('L', [7138, 3305]); // exact image size in pixels (landscape)
$pdf->Image(__DIR__ . '/pay slip.jpg', 0, 0, 7138, 3305);

/* Text styling */
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 300); // Adjusted for pixel scale

// Coordinates based on image layout (fine-tune visually)
// DATE (top right)
$pdf->SetXY(5800, 290);
$pdf->Cell(1500, 200, date('F Y', strtotime($payMonth)), 0, 0, 'L');

// NAME
$pdf->SetXY(1350, 1330);
$pdf->Cell(4000, 200, $instructor['name'], 0, 0, 'L');

// OMR AMOUNT (inside the white box) — set color to black
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(5800, 1675);
$pdf->Cell(1000, 200, number_format($totalPayment, 1), 0, 0, 'L');
$pdf->SetTextColor(255, 255, 255); // Revert back to white for the rest

// PAYMENT IN LETTERS
$pdf->SetXY(2200, 1700);
$pdf->MultiCell(4200, 180, strtoupper(amount_to_words($totalPayment)), 0, 'L');

$filename = 'PaySlip_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $instructor['name']) . '_' . date('MY', strtotime($payMonth)) . '.pdf';
$pdf->Output('I', $filename);
exit;
?>