<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Query to get all trainees data
$query = "SELECT * FROM trainees ORDER BY date DESC";
$result = $conn->query($query);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="trainees_' . date('Y-m-d_H-i-s') . '.xls"');
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
  <Title>Trainees Report</Title>
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
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#D9D9D9" ss:Pattern="Solid"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Trainees">
  <Table ss:ExpandedColumnCount="15" ss:ExpandedRowCount="<?= $result->num_rows + 1 ?>" x:FullColumns="1" x:FullRows="1">
   <Column ss:AutoFitWidth="0" ss:Width="120"/> <!-- Source -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/>  <!-- Batch -->
   <Column ss:AutoFitWidth="0" ss:Width="120"/> <!-- Name -->
   <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- National ID -->
   <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- Phone -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/>  <!-- Gender -->
   <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- Added By -->
   <Column ss:AutoFitWidth="0" ss:Width="140"/> <!-- Date -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/>  <!-- Quiz -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/>  <!-- Try 8 -->
   <Column ss:AutoFitWidth="0" ss:Width="60"/>  <!-- Try Road -->
   <Column ss:AutoFitWidth="0" ss:Width="60"/>  <!-- Sign -->
   <Column ss:AutoFitWidth="0" ss:Width="60"/>  <!-- Payment -->
   <Column ss:AutoFitWidth="0" ss:Width="60"/>  <!-- Status -->
   
   <!-- Header Row -->
   <Row ss:AutoFitHeight="0">
    <Cell ss:StyleID="s62"><Data ss:Type="String">Source</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Batch</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Name</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">National ID</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Phone Number</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Gender</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Added By</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Date</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Quiz</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Try 8</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Try Road</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Sign</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Payment</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Status</Data></Cell>
   </Row>
   
   <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
     <Row ss:AutoFitHeight="0">
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['source']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['batch']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['name']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['national_id']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['phone_number']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars(ucfirst($row['gender'])) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= htmlspecialchars($row['added_by']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= date('j-n-Y h:i A', strtotime($row['date'])) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= $row['quiz'] ?? '—' ?></Data></Cell>
      <Cell><Data ss:Type="Number"><?= htmlspecialchars($row['number_of_trails']) ?></Data></Cell>
      <Cell><Data ss:Type="Number"><?= htmlspecialchars($row['try_road']) ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= $row['sign'] ? 'Yes' : 'No' ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= $row['payment'] ? 'Paid' : 'Unpaid' ?></Data></Cell>
      <Cell><Data ss:Type="String"><?= $row['is_active'] ? 'Ongoing' : 'Completed' ?></Data></Cell>
     </Row>
    <?php endwhile; ?>
   <?php endif; ?>
  </Table>
 </Worksheet>
</Workbook>

<?php
$content = ob_get_clean();
echo $content;
exit();
?>
