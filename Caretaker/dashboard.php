<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch caretaker details
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT name, property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_name, $caretaker_property);
$stmt->fetch();
$stmt->close();

// Fetch total due balances for the caretaker's property
$total_due_balances = 0;
$usersQuery = $conn->prepare("
    SELECT id, rent_amount, move_in_date, unit 
    FROM users 
    WHERE property = ?
");
$usersQuery->bind_param("s", $caretaker_property);
$usersQuery->execute();
$usersResult = $usersQuery->get_result();

while ($user = $usersResult->fetch_assoc()) {
    $userId = $user['id'];
    $rentAmount = $user['rent_amount'];
    $moveInDate = $user['move_in_date'];

    // Fetch Wi-Fi and Water charges for the property
    $billingStmt = $conn->prepare("SELECT wifi, water FROM billing WHERE property = ?");
    $billingStmt->bind_param("s", $caretaker_property);
    $billingStmt->execute();
    $billingStmt->bind_result($wifiAmount, $waterAmount);
    $billingStmt->fetch();
    $billingStmt->close();

    $wifiAmount = $wifiAmount ?? 0;
    $waterAmount = $waterAmount ?? 0;

    // Calculate total required amount based on move-in date
    $moveIn = new DateTime($moveInDate);
    $now = new DateTime();
    $months = $moveIn->diff($now)->y * 12 + $moveIn->diff($now)->m + 1;
    $required = $months * ($rentAmount + $wifiAmount + $waterAmount);

    // Fetch total paid amount
    $transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
    $transactionStmt->bind_param("i", $userId);
    $transactionStmt->execute();
    $transactionStmt->bind_result($totalPaid);
    $transactionStmt->fetch();
    $transactionStmt->close();

    $totalPaid = $totalPaid ?? 0;

    // Calculate amount due
    $amountDue = $required > $totalPaid ? $required - $totalPaid : 0;
    $total_due_balances += $amountDue;
}
$usersQuery->close();

// Fetch total tenants for the caretaker's property
$totalTenantsQuery = $conn->prepare("
    SELECT COUNT(*) AS total_tenants 
    FROM users 
    WHERE property = ?
");
$totalTenantsQuery->bind_param("s", $caretaker_property);
$totalTenantsQuery->execute();
$totalTenantsQuery->bind_result($total_tenants);
$totalTenantsQuery->fetch();
$totalTenantsQuery->close();

// Fetch total vacant units for the caretaker's property
$vacantUnitsQuery = $conn->prepare("
    SELECT COUNT(*) AS vacant_units 
    FROM units 
    WHERE property = ? AND status = 'Vacant'
");
$vacantUnitsQuery->bind_param("s", $caretaker_property);
$vacantUnitsQuery->execute();
$vacantUnitsQuery->bind_result($vacant_units);
$vacantUnitsQuery->fetch();
$vacantUnitsQuery->close();

// Fetch unread messages for the caretaker's property
$unreadMessagesQuery = $conn->prepare("
    SELECT COUNT(*) AS unread_messages 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0
");
$unreadMessagesQuery->bind_param("i", $caretaker_id);
$unreadMessagesQuery->execute();
$unreadMessagesQuery->bind_result($unread_messages);
$unreadMessagesQuery->fetch();
$unreadMessagesQuery->close();

// Fetch vacating requests for the caretaker's property
$vacatingRequestsQuery = $conn->prepare("
    SELECT COUNT(*) AS vacating_requests 
    FROM vacating_notices 
    WHERE house_number IN (SELECT unit FROM units WHERE property = ?)
");
$vacatingRequestsQuery->bind_param("s", $caretaker_property);
$vacatingRequestsQuery->execute();
$vacatingRequestsQuery->bind_result($vacating_requests);
$vacatingRequestsQuery->fetch();
$vacatingRequestsQuery->close();

// Fetch maintenance requests for the caretaker's property
$maintenanceRequestsQuery = $conn->prepare("
    SELECT COUNT(*) AS maintenance_requests 
    FROM maintenance 
    WHERE property = ?
");
$maintenanceRequestsQuery->bind_param("s", $caretaker_property);
$maintenanceRequestsQuery->execute();
$maintenanceRequestsQuery->bind_result($maintenance_requests);
$maintenanceRequestsQuery->fetch();
$maintenanceRequestsQuery->close();

// Fetch collected revenue for the caretaker's property
$collectedRevenueQuery = $conn->prepare("
    SELECT SUM(amount) AS collected_revenue 
    FROM transactions 
    WHERE property = ?
");
$collectedRevenueQuery->bind_param("s", $caretaker_property);
$collectedRevenueQuery->execute();
$collectedRevenueQuery->bind_result($collected_revenue);
$collectedRevenueQuery->fetch();
$collectedRevenueQuery->close();
?>

<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
  }

  h1 {
    margin-bottom: 10px;
    font-size: 32px;
    color: #333;
  }

  h2 {
    margin-bottom: 30px;
    font-size: 18px;
    color: #666;
    font-weight: normal;
    border-top: 1px solid #ddd;
    padding-top: 10px;
  }

  .dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
  }

  .card {
    background: #fff;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
  }

  .dashboard-icon {
    background-color: #eef1f7;
    padding: 15px;
    border-radius: 50%;
    font-size: 24px;
    color: #4a90e2;
    margin-right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .card.red .dashboard-icon { color: #e74c3c; background-color: #fdecea; }
  .card.blue .dashboard-icon { color: #3498db; background-color: #eaf4fd; }
  .card.orange .dashboard-icons { color: #e67e22; background-color: #fff3e6; }
  .card.purple .dashboard-icon { color: #9b59b6; background-color: #f3e6f9; }
  .card.yellow .dashboard-icon { color: #f1c40f; background-color: #fef9e7; }
  .card.green .dashboard-icon { color: #2ecc71; background-color: #eafaf1; }
  .card.indigo .dashboard-icon { color: #5c6bc0; background-color: #e8eaf6; }

  .info h3 {
    margin-bottom: 8px;
    font-size: 20px;
    color: #333;
  }

  .info p {
    font-size: 16px;
    color: #666;
  }
</style>

<h1>Welcome, <?php echo htmlspecialchars($caretaker_name); ?></h1>
<h2>Property Manager: <?php echo htmlspecialchars($caretaker_property); ?></h2>

<div class="dashboard">

  <div class="card red">
    <div class="dashboard-icon"><i data-feather="file-text"></i></div>
    <div class="info">
      <h3>Total Due Balances</h3>
      <p>Ksh <?php echo number_format($total_due_balances, 2); ?></p>
    </div>
  </div>

  <div class="card blue">
    <div class="dashboard-icon"><i data-feather="users"></i></div>
    <div class="info">
      <h3>Tenants</h3>
      <p><?php echo $total_tenants; ?></p>
    </div>
  </div>

  <div class="card orange">
    <div class="dashboard-icon"><i data-feather="home"></i></div>
    <div class="info">
      <h3>Vacant Units</h3>
      <p><?php echo $vacant_units; ?></p>
    </div>
  </div>

  <div class="card purple">
    <div class="dashboard-icon"><i data-feather="mail"></i></div>
    <div class="info">
      <h3>Unread Messages</h3>
      <p><?php echo $unread_messages; ?></p>
    </div>
  </div>

  <div class="card yellow">
    <div class="dashboard-icon"><i data-feather="truck"></i></div>
    <div class="info">
      <h3>Vacating Requests</h3>
      <p><?php echo $vacating_requests; ?></p>
    </div>
  </div>

  <div class="card indigo">
    <div class="dashboard-icon"><i data-feather="tool"></i></div>
    <div class="info">
      <h3>Maintenance Requests</h3>
      <p><?php echo $maintenance_requests; ?></p>
    </div>
  </div>

  <div class="card green">
    <div class="dashboard-icon"><i data-feather="trending-up"></i></div>
    <div class="info">
      <h3>Collected Revenue</h3>
      <p>Ksh <?php echo number_format($collected_revenue, 2); ?></p>
    </div>
  </div>

</div>