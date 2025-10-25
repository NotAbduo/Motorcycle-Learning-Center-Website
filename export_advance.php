<?php
require_once 'db.php';
require('fpdf.php');

/* ── 1. INPUT ─────────────────────────────────────────────── */
$selectedColumns  = $_POST['columns'] ?? [];
$filterFields     = $_POST['filter_field'] ?? [];
$filterOps        = $_POST['filter_operator'] ?? [];
$filterValues     = $_POST['filter_value'] ?? [];
$filterDateFrom   = $_POST['filter_value_from'] ?? [];
$filterDateTo     = $_POST['filter_value_to'] ?? [];

/* ── 2. LABELS (aligned with Excel export) ────────────────── */
$labels = [
    'source'           => 'Source',
    'batch'            => 'Batch',
    'name'             => 'Name',
    'national_id'      => 'National ID',
    'phone_number'     => 'Phone Number',
    'gender'           => 'Gender',
    'added_by'         => 'Added By',
    'date'             => 'Date',
    'quiz'             => 'Quiz',
    'number_of_trails' => 'Try 8',
    'try_road'         => 'Try Road',   // ✅ Added new column
    'sign'             => 'Sign',
    'payment'          => 'Payment',
    'is_active'        => 'Status'
];

if (empty($selectedColumns)) {
    die('No columns selected.');
}

/* ── 3. HEADERS & FIELDS ──────────────────────────────────── */
$headers = [];
$fields = [];
foreach ($selectedColumns as $col) {
    if (isset($labels[$col])) {
        $headers[] = $labels[$col];
        $fields[] = $col;
    }
}

/* ── 4. WHERE CLAUSE ──────────────────────────────────────── */
$whereSQL = [];
for ($i = 0; $i < count($filterFields); $i++) {
    $field = $filterFields[$i] ?? '';
    $op = $filterOps[$i] ?? '';

    if (!isset($labels[$field])) continue;

    if ($field === 'date') {
        $from = trim($filterDateFrom[$i] ?? '');
        $to   = trim($filterDateTo[$i] ?? '');
        if ($from && $to) {
            $whereSQL[] = "`$field` BETWEEN '$from' AND '$to'";
        } elseif ($from) {
            $whereSQL[] = "`$field` >= '$from'";
        } elseif ($to) {
            $whereSQL[] = "`$field` <= '$to'";
        }
    } else {
        $val = trim($filterValues[$i] ?? '');
        if ($val === '') continue;
        $safeVal = $conn->real_escape_string($val);
        switch ($op) {
            case 'equals':   $whereSQL[] = "`$field` = '$safeVal'"; break;
            case 'starts':   $whereSQL[] = "`$field` LIKE '$safeVal%'"; break;
            case 'ends':     $whereSQL[] = "`$field` LIKE '%$safeVal'"; break;
            case 'gt':       $whereSQL[] = "`$field` > '$safeVal'"; break;
            case 'lt':       $whereSQL[] = "`$field` < '$safeVal'"; break;
            case 'contains':
            default:         $whereSQL[] = "`$field` LIKE '%$safeVal%'"; break;
        }
    }
}
$whereClause = $whereSQL ? 'WHERE ' . implode(' AND ', $whereSQL) : '';

/* ── 5. QUERY ─────────────────────────────────────────────── */
$columnList = implode(', ', array_unique($fields));
$result = $conn->query("SELECT $columnList FROM trainees $whereClause ORDER BY date DESC");

if (!$result) {
    die('SQL Error: ' . $conn->error);
}

/* ── 6. PDF GENERATION ────────────────────────────────────── */
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Trainees Report', 0, 1, 'C');
$pdf->Ln();
$pdf->SetFont('Arial', '', 10);

// Calculate column widths
$max = [];
foreach ($headers as $h) {
    $max[] = $pdf->GetStringWidth($h);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;

    foreach ($fields as $i => $f) {
        $text = match ($f) {
            'sign'    => $row[$f] ? 'Yes' : 'No',
            'payment' => $row[$f] ? 'Paid' : 'Unpaid',
            'date'    => substr($row[$f], 0, 10),
            'number_of_trails' => $row[$f] ?? '0',
            'try_road' => $row[$f] ?? '0',   // ✅ handle Try Road
            'is_active' => $row[$f] ? 'Ongoing' : 'Completed',
            'gender'   => ucfirst($row[$f]),
            default   => $row[$f]
        };
        $w = $pdf->GetStringWidth($text);
        if ($w > $max[$i]) $max[$i] = $w;
    }
}

// Add padding and fit to page
$pad = 6;
foreach ($max as &$w) $w += $pad;

$pageW = 277;
$total = array_sum($max);
if ($total > $pageW) {
    $scale = $pageW / $total;
    foreach ($max as &$w) $w *= $scale;
    $total = array_sum($max);
}

$startX = ($pageW - $total) / 2 + 10;

/* Print headers */
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX($startX);
foreach ($headers as $i => $h) {
    $pdf->Cell($max[$i], 10, $h, 1, 0, 'C');
}
$pdf->Ln();

/* Print rows */
$pdf->SetFont('Arial', '', 10);
foreach ($rows as $r) {
    $pdf->SetX($startX);
    foreach ($fields as $i => $f) {
        $text = match ($f) {
            'sign'    => $r[$f] ? 'Yes' : 'No',
            'payment' => $r[$f] ? 'Paid' : 'Unpaid',
            'date'    => substr($r[$f], 0, 10),
            'number_of_trails' => $r[$f] ?? '0',
            'try_road' => $r[$f] ?? '0',   // ✅ Try Road in rows
            'is_active' => $r[$f] ? 'Ongoing' : 'Completed',
            'gender'   => ucfirst($r[$f]),
            default   => $r[$f]
        };
        $pdf->Cell($max[$i], 10, $text, 1, 0, 'C');
    }
    $pdf->Ln();
}

$pdf->Output();
