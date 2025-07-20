<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch caretaker's property
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_property);
$stmt->fetch();
$stmt->close();

// Fetch vacation notices for the caretaker's property
$query = "SELECT id, tenant_id, tenant_name, house_number, date_of_notice, planned_exit_date, reason 
          FROM vacating_notices 
          WHERE house_number IN (SELECT unit FROM units WHERE property = ?) 
          ORDER BY date_of_notice DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$result = $stmt->get_result();

// Handle accept or decline actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Fetch tenant details
    $noticeQuery = $conn->prepare("SELECT tenant_id, tenant_name, house_number, planned_exit_date 
                                   FROM vacating_notices 
                                   WHERE id = ? AND house_number IN (SELECT unit FROM units WHERE property = ?)");
    $noticeQuery->bind_param("is", $id, $caretaker_property);
    $noticeQuery->execute();
    $noticeResult = $noticeQuery->get_result();
    $notice = $noticeResult->fetch_assoc();
    $tenant_id = $notice['tenant_id'];
    $planned_exit_date = $notice['planned_exit_date'];
    $house_number = $notice['house_number'];
    $tenant_name = $notice['tenant_name'];
    $noticeQuery->close();

    if ($action === "accept") {
        // Save the vacating notice
        $saveNoticeQuery = $conn->prepare("
            INSERT INTO approved_vacating_notices (tenant_id, tenant_name, house_number, planned_exit_date, property)
            VALUES (?, ?, ?, ?, ?)
        ");
        $saveNoticeQuery->bind_param("issss", $tenant_id, $tenant_name, $house_number, $planned_exit_date, $caretaker_property);
        $saveNoticeQuery->execute();
        $saveNoticeQuery->close();

        // Remove the vacation notice after processing
        $deleteQuery = $conn->prepare("DELETE FROM vacating_notices WHERE id = ? AND house_number IN (SELECT unit FROM units WHERE property = ?)");
        $deleteQuery->bind_param("is", $id, $caretaker_property);
        $deleteQuery->execute();
        $deleteQuery->close();

    } elseif ($action === "decline") {
        // No additional action for decline
    }

    header("Location: vacating_notice.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Vacation Notices</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .action-link {
        color: grey;
        text-decoration: underline;
        background: none;
        border: none;
        cursor: pointer;
        font-size: inherit;
        padding: 0;
    }

    .action-link:hover {
        color: darkgrey;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Manage Vacation Notices</h2>
    <p class="text-sm text-gray-600 mb-6">Below is a list of all submitted vacation notices for your property. You can review and take action as needed.</p>

    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-gray-200 text-gray-700">
          <tr>
            <th class="text-left px-4 py-2">Tenant Name</th>
            <th class="text-left px-4 py-2">House Number</th>
            <th class="text-left px-4 py-2">Date of Notice</th>
            <th class="text-left px-4 py-2">Planned Exit Date</th>
            <th class="text-left px-4 py-2">Reason</th>
            <th class="text-left px-4 py-2">Options</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="border-t">
                <td class="px-4 py-2"><?= htmlspecialchars($row['tenant_name']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['house_number']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['date_of_notice']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['planned_exit_date']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['reason']) ?></td>
                <td class="px-4 py-2">
                  <button class="action-link" onclick="handleAction(<?= $row['id'] ?>, 'accept')">Accept</button>
                  |
                  <button class="action-link" onclick="handleAction(<?= $row['id'] ?>, 'decline')">Decline</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-gray-500">No vacation notices found for your property.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function handleAction(id, action) {
      if (action === "accept") {
        if (confirm("Are you sure you want to accept this vacation notice?")) {
          window.location.href = `vacating_notice.php?action=accept&id=${id}`;
        }
      } else if (action === "decline") {
        if (confirm("Are you sure you want to decline this vacation notice?")) {
          window.location.href = `vacating_notice.php?action=decline&id=${id}`;
        }
      }
    }
  </script>

</body>
</html>