<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Assessment Report</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background-color: #f5f7fa;
    }

    .container {
      padding: 40px;
    }

    header h1 {
      text-align: center;
      font-size: 2em;
      margin-bottom: 30px;
      color: #333;
    }

    .add-button {
      background-color: rgb(0, 0, 130);
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 20px;
      display: inline-block;
    }

    .add-button:hover {
      background-color: #000080;
    }

    .table-container {
      margin-top: 30px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px 16px;
      border: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #f0f0f0;
      font-weight: bold;
    }

    /* Modal Styling (unchanged) */
    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-content {
      background-color: #fff;
      padding: 20px;
      max-width: 800px;
      width: 100%;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    #reportForm {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    #reportForm label {
      width: 100%;
      font-weight: bold;
      margin-bottom: 4px;
    }

    #reportForm select,
    #reportForm input[type="text"],
    #reportForm input[type="number"] {
      width: 100%;
      padding: 8px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    #reportForm input[readonly] {
      background-color: #f0f0f0;
      color: #888;
    }

    #reportForm > div {
      width: calc(50% - 10px);
    }

    #reportForm .text-right {
      width: 100%;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .modal-content button {
      width: 200px;
      background-color: rgb(0, 0, 130);
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      text-align: center;
    }

    .modal-content button:hover {
      background-color: #000080;
    }
  </style>
</head>
<body>

<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$properties = $conn->query("SELECT DISTINCT property FROM users");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $property = $_POST['property'];
  $tenant_id = $_POST['tenant_id'];
  $unit = $_POST['unit'];
  $security_deposit = $_POST['security_deposit'];
  $damages = $_POST['damages'];
  $repair_amount = $_POST['repair_amount'];
  $payment_method = $_POST['payment_method'];
  $remaining_deposit = $_POST['remaining_deposit'];

  $stmt = $conn->prepare("
    INSERT INTO assesment_report 
    (property, tenant_id, unit, security_deposit, damages, repair_amount, payment_method, remaining_deposit)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("sisssdss", $property, $tenant_id, $unit, $security_deposit, $damages, $repair_amount, $payment_method, $remaining_deposit);

  if ($stmt->execute()) {
    $success = "Report saved successfully!";
  } else {
    $error = "Failed to save the report. Please try again.";
  }

  $stmt->close();
}
?>

<div class="container">
  <header>
    <h1>Assessment Report</h1>
  </header>

  <button class="add-button" onclick="openAddModal()">Add Report</button>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Property</th>
          <th>Name</th>
          <th>Unit</th>
          <th>Security Deposit Amount</th>
          <th>Damages</th>
          <th>Repair Amount</th>
          <th>Payment Method</th>
          <th>Remaining Deposit (After Deductions)</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $reports = $conn->query("
          SELECT ar.*, u.name AS tenant_name
          FROM assesment_report ar
          JOIN users u ON ar.tenant_id = u.id
        ");

        while ($report = $reports->fetch_assoc()):
        ?>
          <tr>
            <td><?= htmlspecialchars($report['property']) ?></td>
            <td><?= htmlspecialchars($report['tenant_name']) ?></td>
            <td><?= htmlspecialchars($report['unit']) ?></td>
            <td><?= htmlspecialchars($report['security_deposit']) ?></td>
            <td><?= htmlspecialchars($report['damages']) ?></td>
            <td><?= htmlspecialchars($report['repair_amount']) ?></td>
            <td><?= htmlspecialchars($report['payment_method']) ?></td>
            <td><?= htmlspecialchars($report['remaining_deposit']) ?></td>
            <td><?= htmlspecialchars($report['status']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="modal" class="modal">
  <div class="modal-content">
    <h2 class="text-lg font-bold mb-4">Add Assessment Report</h2>
    <form id="reportForm" method="POST">
      <div>
        <label for="property">Property</label>
        <select id="propertyDropdown" name="property" onchange="loadTenants()" required>
          <option value="">Select a Property</option>
          <?php
          $properties->data_seek(0);
          while ($p = $properties->fetch_assoc()):
          ?>
            <option value="<?= htmlspecialchars($p['property']) ?>"><?= htmlspecialchars($p['property']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label for="tenant">Tenant</label>
        <select id="tenantDropdown" name="tenant_id" onchange="populateTenantDetails()" required>
          <option value="">Select a Tenant</option>
        </select>
      </div>

      <div>
        <label for="unit">Unit</label>
        <input type="text" id="unitField" name="unit" readonly />
      </div>

      <div>
        <label for="securityDeposit">Security Deposit Amount</label>
        <input type="text" id="securityDepositField" name="security_deposit" readonly />
      </div>

      <div>
        <label for="damages">Damages</label>
        <select id="damagesDropdown" name="damages" required>
          <option value="">Select Damage</option>
          <option value="Floor Damage">Floor Damage</option>
          <option value="Glass Damage">Glass Damage</option>
          <option value="Wall Damage">Wall Damage</option>
          <option value="Bathroom Damages">Bathroom Damages</option>
          <option value="Pipe Clogs">Pipe Clogs</option>
        </select>
      </div>

      <div>
        <label for="repairAmount">Repair Amount</label>
        <input type="number" id="repairAmountField" name="repair_amount" oninput="calculateRemainingDeposit()" required />
      </div>

      <div>
        <label for="paymentMethod">Payment Method</label>
        <select id="paymentMethodDropdown" name="payment_method" onchange="calculateRemainingDeposit()" required>
          <option value="">Select Payment Method</option>
          <option value="Security Deposit">Security Deposit</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div>
        <label for="remainingDeposit">Remaining Deposit (After Deductions)</label>
        <input type="text" id="remainingDepositField" name="remaining_deposit" readonly />
      </div>

      <div class="text-right">
        <button type="submit">Save Report</button>
        <button type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById("modal").style.display = "flex";
  document.body.style.overflow = "hidden";
  resetForm();
}

function closeModal() {
  document.getElementById("modal").style.display = "none";
  document.body.style.overflow = "auto";
}

function resetForm() {
  document.getElementById("reportForm").reset();
  document.getElementById("tenantDropdown").innerHTML = '<option value="">Select a Tenant</option>';
  document.getElementById("unitField").value = "";
  document.getElementById("securityDepositField").value = "";
  document.getElementById("remainingDepositField").value = "";
}

function loadTenants() {
  const property = document.getElementById("propertyDropdown").value;
  const tenantDropdown = document.getElementById("tenantDropdown");

  tenantDropdown.innerHTML = '<option value="">Select a Tenant</option>';

  if (property) {
    fetch(`get_tenants.php?property=${property}`)
      .then(response => response.json())
      .then(data => {
        data.forEach(tenant => {
          const option = document.createElement("option");
          option.value = tenant.id;
          option.textContent = tenant.name;
          tenantDropdown.appendChild(option);
        });
      })
      .catch(error => console.error("Error fetching tenants:", error));
  }
}

function populateTenantDetails() {
  const tenantDropdown = document.getElementById("tenantDropdown");
  const tenantId = tenantDropdown.value;
  const property = document.getElementById("propertyDropdown").value;

  if (tenantId && property) {
    fetch(`get_tenants.php?property=${property}&tenant=${tenantId}`)
      .then(response => response.json())
      .then(data => {
        document.getElementById("unitField").value = data.unit || "";
        document.getElementById("securityDepositField").value = data.security_deposit || "";
        calculateRemainingDeposit();
      });
  }
}

function calculateRemainingDeposit() {
  const deposit = parseFloat(document.getElementById("securityDepositField").value) || 0;
  const repair = parseFloat(document.getElementById("repairAmountField").value) || 0;
  const method = document.getElementById("paymentMethodDropdown").value;

  const remaining = (method === "Security Deposit") ? (deposit - repair) : deposit;
  document.getElementById("remainingDepositField").value = remaining.toFixed(2);
}
</script>
</body>
</html>
