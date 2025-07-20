<?php
session_start();
$conn = new mysqli("localhost", "root", "", "Demo1");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user rent info
$userStmt = $conn->prepare("SELECT name, phone, rent_amount, property FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userStmt->bind_result($name, $phone, $rentAmount, $property);
$userStmt->fetch();
$userStmt->close();

// Fetch wifi amount from billing
$billStmt = $conn->prepare("SELECT wifi FROM billing WHERE property = ?");
$billStmt->bind_param("s", $property);
$billStmt->execute();
$billStmt->bind_result($wifiAmount);
$billStmt->fetch();
$billStmt->close();

$totalAmount = $rentAmount + $wifiAmount;
$invoiceNumber = 'INV' . date('Ym') . $user_id; // Format: INV2025047
$invoiceDate = date("Y-m-01"); // First of current month
$dueDate = date("Y-m-10"); // Example due date
$email = "reservations@topnotchguesthouse.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Invoice</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <h1 class="text-[18px] font-semibold text-gray-800 mb-6">This Month's Invoice</h1>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left">
      <thead>
        <tr class="text-gray-600 bg-gray-100">
          <th class="px-4 py-3">Invoice Number</th>
          <th class="px-4 py-3">Rent (KES)</th>
          <th class="px-4 py-3">Wi-Fi (KES)</th>
          <th class="px-4 py-3">Total Amount</th>
          <th class="px-4 py-3">Date</th>
          <th class="px-4 py-3">Options</th>
        </tr>
      </thead>
      <tbody class="text-gray-700">
        <tr>
          <td class="px-4 py-3"><?= $invoiceNumber ?></td>
          <td class="px-4 py-3"><?= number_format($rentAmount, 2) ?></td>
          <td class="px-4 py-3"><?= number_format($wifiAmount, 2) ?></td>
          <td class="px-4 py-3"><?= number_format($totalAmount, 2) ?></td>
          <td class="px-4 py-3"><?= $invoiceDate ?></td>
          <td class="px-4 py-3">
            <span 
              onclick="generatePDF()" 
              class="text-blue-600 hover:underline cursor-pointer"
            >
              Download
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <script>
    function generatePDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      const invoiceNumber = "<?= $invoiceNumber ?>";
      const invoiceDate = "<?= $invoiceDate ?>";
      const dueDate = "<?= $dueDate ?>";
      const name = "<?= $name ?>";
      const phone = "<?= $phone ?>";
      const email = "<?= $email ?>";
      const rent = "<?= number_format($rentAmount, 2) ?>";
      const wifi = "<?= number_format($wifiAmount, 2) ?>";
      const total = "<?= number_format($totalAmount, 2) ?>";

      doc.setFontSize(20);
      doc.text("INVOICE", 105, 20, null, null, "center");

      doc.setFontSize(11);
      doc.text(`Invoice #: ${invoiceNumber}`, 14, 35);
      doc.text(`Date: ${invoiceDate}`, 14, 42);
      doc.text(`Due Date: ${dueDate}`, 14, 49);

      doc.setFontSize(12);
      doc.text("Billed To:", 14, 62);
      doc.setFontSize(11);
      doc.text(`Name: ${name}`, 14, 68);
      doc.text(`Phone: ${phone}`, 14, 74);
      doc.text(`Email: ${email}`, 14, 80);
      doc.text("Payment Method: M-Pesa", 150, 35);

      doc.setFillColor(230, 230, 230);
      doc.rect(14, 95, 182, 10, 'F');
      doc.setFontSize(11);
      doc.text("Description", 16, 102);
      doc.text("Amount (KES)", 160, 102);

      let y = 112;
      doc.text("Monthly Rent", 16, y);
      doc.text(`${rent}`, 196, y, null, null, 'right');
      y += 8;
      doc.text("Wi-Fi Service", 16, y);
      doc.text(`${wifi}`, 196, y, null, null, 'right');

      y += 10;
      doc.line(14, y, 196, y);
      y += 6;

      doc.setFontSize(12);
      doc.text("Total Amount:", 120, y);
      doc.text(`${total} KES`, 196, y, null, null, 'right');

      doc.setFontSize(10);
      doc.text("Thank you for your payment!", 14, y + 20);
      doc.text("This invoice was generated electronically and does not require a signature.", 14, y + 26);

      doc.save(`${invoiceNumber}.pdf`);
    }
  </script>

</body>
</html>
