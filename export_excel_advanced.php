<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Available columns with their display names
$availableColumns = [
    'source' => 'Source',
    'batch' => 'Batch', // ✅ Batch
    'name' => 'Name',
    'national_id' => 'National ID',
    'phone_number' => 'Phone Number',
    'gender' => 'Gender',
    'added_by' => 'Added By',
    'date' => 'Date',
    'quiz' => 'Quiz',
    'number_of_trails' => 'Try 8',
    'try_road' => 'Try Road', // ✅ NEW COLUMN
    'sign' => 'Sign',
    'payment' => 'Payment',
    'is_active' => 'Status'
];

// Get selected columns, default to all if none selected
$selectedColumns = $_POST['columns'] ?? array_keys($availableColumns);

// Validate selected columns
$selectedColumns = array_intersect($selectedColumns, array_keys($availableColumns));
if (empty($selectedColumns)) {
    $selectedColumns = array_keys($availableColumns);
}

// Build the SELECT clause
$selectClause = implode(', ', $selectedColumns);

// Process filters
$whereConditions = [];
if (isset($_POST['filter_field']) && is_array($_POST['filter_field'])) {
    for ($i = 0; $i < count($_POST['filter_field']); $i++) {
        $field = $_POST['filter_field'][$i];
        $operator = $_POST['filter_operator'][$i] ?? 'contains';
        $value = $_POST['filter_value'][$i] ?? '';
        $valueFrom = $_POST['filter_value_from'][$i] ?? '';
        $valueTo = $_POST['filter_value_to'][$i] ?? '';

        if (!array_key_exists($field, $availableColumns)) continue;

        if ($field === 'date' && ($valueFrom || $valueTo)) {
            if ($valueFrom && $valueTo) {
                $whereConditions[] = "$field BETWEEN '" . $conn->real_escape_string($valueFrom . ' 00:00:00') . "' AND '" . $conn->real_escape_string($valueTo . ' 23:59:59') . "'";
            } elseif ($valueFrom) {
                $whereConditions[] = "$field >= '" . $conn->real_escape_string($valueFrom . ' 00:00:00') . "'";
            } elseif ($valueTo) {
                $whereConditions[] = "$field <= '" . $conn->real_escape_string($valueTo . ' 23:59:59') . "'";
            }
        } elseif (!empty($value)) {
            $escapedValue = $conn->real_escape_string($value);
            switch ($operator) {
                case 'equals':   $whereConditions[] = "$field = '$escapedValue'"; break;
                case 'starts':   $whereConditions[] = "$field LIKE '$escapedValue%'"; break;
                case 'ends':     $whereConditions[] = "$field LIKE '%$escapedValue'"; break;
                case 'gt':       $whereConditions[] = "$field > '$escapedValue'"; break;
                case 'lt':       $whereConditions[] = "$field < '$escapedValue'"; break;
                case 'contains':
                default:         $whereConditions[] = "$field LIKE '%$escapedValue%'"; break;
            }
        }
    }
}

// Build the WHERE clause
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Build and execute the query
$query = "SELECT $selectClause FROM trainees $whereClause ORDER BY date DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="trainees_filtered_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Start output buffering
ob_start();

echo '<?xml version="1.0"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Title>Trainees Filtered Report</Title>
  <Author>Training System</Author>
  <Created><?= date('Y-m-d\TH:i:s\Z') ?></Created>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>12000</WindowHeight>
  <WindowWidth>18000</WindowWidth>
  <WindowTopX>240</WindowTopX>
  <WindowTopY>120</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s63">
   <Alignment ss:Horizontal="Center"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Trainees Report">
  <Table ss:ExpandedColumnCount="<?= count($selectedColumns) ?>" ss:ExpandedRowCount="<?= $result->num_rows + 1 ?>" x:FullColumns="1" x:FullRows="1">
   
   <?php
   // Set column widths based on content type
   foreach ($selectedColumns as $column) {
       $width = 100; // default
       switch ($column) {
           case 'name': $width = 150; break;
           case 'national_id':
           case 'phone_number': $width = 120; break;
           case 'date': $width = 140; break;
           case 'source':
           case 'added_by':
           case 'batch': $width = 110; break;
           case 'gender':
           case 'quiz':
           case 'sign':
           case 'payment':
           case 'is_active': $width = 80; break;
           case 'number_of_trails':
           case 'try_road': $width = 70; break; // ✅ width for try_road
       }
       echo "<Column ss:AutoFitWidth=\"0\" ss:Width=\"$width\"/>\n";
   }
   ?>
   
   <!-- Header Row -->
   <Row ss:AutoFitHeight="0">
    <?php foreach ($selectedColumns as $column): ?>
     <Cell ss:StyleID="s62"><Data ss:Type="String"><?= htmlspecialchars($availableColumns[$column]) ?></Data></Cell>
    <?php endforeach; ?>
   </Row>
   
   <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
     <Row ss:AutoFitHeight="0">
      <?php foreach ($selectedColumns as $column): ?>
       <Cell<?= in_array($column, ['sign', 'payment', 'is_active', 'number_of_trails', 'try_road']) ? ' ss:StyleID="s63"' : '' ?>>
        <?php
        $cellValue = '';
        $dataType = 'String';
        
        switch ($column) {
            case 'source':
            case 'batch':
            case 'name':
            case 'national_id':
            case 'phone_number':
            case 'added_by':
                $cellValue = htmlspecialchars($row[$column] ?? '');
                break;
            case 'gender':
                $cellValue = htmlspecialchars(ucfirst($row[$column] ?? ''));
                break;
            case 'date':
                $cellValue = $row[$column] ? date('j-n-Y h:i A', strtotime($row[$column])) : '';
                break;
            case 'quiz':
                $cellValue = $row[$column] ?? '—';
                break;
            case 'number_of_trails':
            case 'try_road': // ✅ NEW
                $cellValue = $row[$column] ?? 0;
                $dataType = 'Number';
                break;
            case 'sign':
                $cellValue = $row[$column] ? 'Yes' : 'No';
                break;
            case 'payment':
                $cellValue = $row[$column] ? 'Paid' : 'Unpaid';
                break;
            case 'is_active':
                $cellValue = $row[$column] ? 'Ongoing' : 'Completed';
                break;
            default:
                $cellValue = htmlspecialchars($row[$column] ?? '');
        }
        ?>
        <Data ss:Type="<?= $dataType ?>"><?= $cellValue ?></Data>
       </Cell>
      <?php endforeach; ?>
     </Row>
    <?php endwhile; ?>
   <?php else: ?>
    <Row ss:AutoFitHeight="0">
     <Cell ss:MergeAcross="<?= count($selectedColumns) - 1 ?>">
      <Data ss:Type="String">No trainees found matching the criteria.</Data>
     </Cell>
    </Row>
   <?php endif; ?>
  </Table>
  
  <!-- Summary Row -->
  <?php if ($result->num_rows > 0): ?>
  <Row ss:Index="<?= $result->num_rows + 3 ?>">
   <Cell ss:StyleID="s62">
    <Data ss:Type="String">Total Records: <?= $result->num_rows ?></Data>
   </Cell>
  </Row>
  <Row>
   <Cell ss:StyleID="s62">
    <Data ss:Type="String">Generated: <?= date('j-n-Y h:i A') ?></Data>
   </Cell>
  </Row>
  <?php endif; ?>
  
 </Worksheet>
</Workbook>

<?php
$content = ob_get_clean();
echo $content;
exit();
?>
