<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CONFIG ---
$to = 'denisobadoharold00@gmail.com'; // Change to your recipient
$from = 'denisobadoharold00@gmail.com'; // Change to your sender
$fromName = 'Reports System';

// --- DATE RANGE FOR PREVIOUS MONTH ---
$monthName = date('F Y', strtotime('last month'));
$monthNameFile = str_replace(' ', '_', $monthName); // For file names
$start = date('Y-m-01', strtotime('first day of last month'));
$end = date('Y-m-t', strtotime('last day of last month'));

// --- DB CONNECTION ---
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- GENERATE REPORT FILES ---
$files = [];
$files[] = generate_financial_statements($conn, $start, $end, $monthName, $monthNameFile);
$files[] = generate_property_statements($conn, $start, $end, $monthName, $monthNameFile);
$files[] = generate_arrears_report($conn, $start, $end, $monthName, $monthNameFile);
$files[] = generate_expenses_report($conn, $start, $end, $monthName, $monthNameFile);

// --- SEND EMAIL ---
$mail = new PHPMailer(true);
try {
    // SMTP config (customize for your mail server)
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; // Change to your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'smtp_username'; // Change to your SMTP username
    $mail->Password = 'smtp_password'; // Change to your SMTP password
    $mail->SMTPSecure = 'tls'; // Use 'tls' for compatibility
    $mail->Port = 587;

    $mail->setFrom($from, $fromName);
    $mail->addAddress($to);
    $mail->Subject = "Monthly Reports for $monthName";
    $mail->Body = "Attached are the monthly reports for $monthName.";

    foreach ($files as $file) {
        if ($file && file_exists($file)) {
            $mail->addAttachment($file);
        }
    }

    $mail->send();
    echo "Reports sent!";
} catch (Exception $e) {
    echo "Mail error: {$mail->ErrorInfo}";
}

// --- CLEANUP ---
foreach ($files as $file) {
    if ($file && file_exists($file)) unlink($file);
}

// --- REPORT GENERATION FUNCTIONS ---

function generate_financial_statements($conn, $start, $end, $monthName, $monthNameFile) {
    $filename = sys_get_temp_dir() . "/financialstatements_for_{$monthNameFile}.csv";
    $f = fopen($filename, 'w');
    if (!$f) return null;
    fputcsv($f, ['Transaction Code', 'Phone Number', 'Name', 'Amount', 'Date']);

    $sql = "SELECT transaction_code, phone_number, name, amount, date FROM transactions WHERE date BETWEEN ? AND ? ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            fputcsv($f, [
                $row['transaction_code'],
                $row['phone_number'],
                $row['name'],
                number_format($row['amount'], 2),
                $row['date']
            ]);
        }
        $stmt->close();
    }
    fclose($f);
    return $filename;
}

function generate_property_statements($conn, $start, $end, $monthName, $monthNameFile) {
    $filename = sys_get_temp_dir() . "/propertystatements_for_{$monthNameFile}.csv";
    $f = fopen($filename, 'w');
    if (!$f) return null;
    fputcsv($f, ['Property', 'Amount Collected (KES)', 'Expenses (KES)', 'Number of Units', 'Percentage Occupancy (%)', 'Percentage Vacancy (%)']);

    $res = $conn->query("SELECT DISTINCT property FROM properties");
    $properties = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $properties[] = $row['property'];
        }
    }

    foreach ($properties as $propertyName) {
        // Amount collected
        $amountCollected = 0;
        $stmt = $conn->prepare("SELECT SUM(amount) AS total_collected FROM transactions WHERE property = ? AND date BETWEEN ? AND ?");
        if ($stmt) {
            $stmt->bind_param("sss", $propertyName, $start, $end);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) $amountCollected = $row['total_collected'] ?? 0;
            $stmt->close();
        }

        // Expenses
        $totalExpenses = 0;
        $stmt = $conn->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE property = ? AND date BETWEEN ? AND ?");
        if ($stmt) {
            $stmt->bind_param("sss", $propertyName, $start, $end);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) $totalExpenses = $row['total_expenses'] ?? 0;
            $stmt->close();
        }

        // Units
        $totalUnits = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_units FROM units WHERE property = ?");
        if ($stmt) {
            $stmt->bind_param("s", $propertyName);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) $totalUnits = $row['total_units'] ?? 0;
            $stmt->close();
        }

        $occupiedUnits = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS occupied_units FROM units WHERE property = ? AND status = 'Occupied'");
        if ($stmt) {
            $stmt->bind_param("s", $propertyName);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) $occupiedUnits = $row['occupied_units'] ?? 0;
            $stmt->close();
        }

        $vacantUnits = $totalUnits - $occupiedUnits;
        $percentageOccupancy = $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0;
        $percentageVacancy = $totalUnits > 0 ? ($vacantUnits / $totalUnits) * 100 : 0;

        fputcsv($f, [
            $propertyName,
            number_format($amountCollected, 2),
            number_format($totalExpenses, 2),
            $totalUnits,
            number_format($percentageOccupancy, 2),
            number_format($percentageVacancy, 2)
        ]);
    }
    fclose($f);
    return $filename;
}

function generate_arrears_report($conn, $start, $end, $monthName, $monthNameFile) {
    $filename = sys_get_temp_dir() . "/arrearsreport_for_{$monthNameFile}.csv";
    $f = fopen($filename, 'w');
    if (!$f) return null;
    fputcsv($f, ['Property', 'Amount Collected (KES)', 'Total Arrears (KES)']);

    $res = $conn->query("SELECT DISTINCT property FROM properties");
    $properties = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $properties[] = $row['property'];
        }
    }

    foreach ($properties as $propertyName) {
        // Amount collected
        $amountCollected = 0;
        $stmt = $conn->prepare("SELECT SUM(amount) AS total_collected FROM transactions WHERE property = ? AND date BETWEEN ? AND ?");
        if ($stmt) {
            $stmt->bind_param("sss", $propertyName, $start, $end);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) $amountCollected = $row['total_collected'] ?? 0;
            $stmt->close();
        }

        // Calculate arrears (simplified: sum of all tenants' due - paid)
        $totalArrears = 0;
        $stmt = $conn->prepare("SELECT id, rent_amount, security_deposit, move_in_date FROM users WHERE property = ?");
        if ($stmt) {
            $stmt->bind_param("s", $propertyName);
            $stmt->execute();
            $users = $stmt->get_result();
            if ($users) {
                while ($user = $users->fetch_assoc()) {
                    $rentAmount = floatval($user['rent_amount']);
                    $securityDeposit = floatval($user['security_deposit']);
                    $moveInDate = new DateTime($user['move_in_date']);
                    $periodEnd = new DateTime($end);
                    $months = ($periodEnd->format('Y') - $moveInDate->format('Y')) * 12 + ($periodEnd->format('n') - $moveInDate->format('n')) + 1;
                    $required = $months * $rentAmount + $securityDeposit;

                    $totalPaid = 0; // Initialize to 0
                    $stmt2 = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ? AND date <= ?");
                    if ($stmt2) {
                        $stmt2->bind_param("is", $user['id'], $end);
                        $stmt2->execute();
                        $stmt2->bind_result($totalPaid);
                        $stmt2->fetch();
                        if ($totalPaid === null) $totalPaid = 0; // Ensure it's not null
                        $totalPaid = floatval($totalPaid);
                        $stmt2->close();

                        $amountDue = max(0, $required - $totalPaid);
                        $totalArrears += $amountDue;
                    }
                }
            }
            $stmt->close();
        }

        fputcsv($f, [
            $propertyName,
            number_format($amountCollected, 2),
            number_format($totalArrears, 2)
        ]);
    }
    fclose($f);
    return $filename;
}

function generate_expenses_report($conn, $start, $end, $monthName, $monthNameFile) {
    $filename = sys_get_temp_dir() . "/expensesreport_for_{$monthNameFile}.csv";
    $f = fopen($filename, 'w');
    if (!$f) return null;
    fputcsv($f, ['Date', 'Property', 'Category', 'Amount']);

    $sql = "SELECT date, property, category, amount FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            fputcsv($f, [
                $row['date'],
                $row['property'],
                $row['category'],
                number_format($row['amount'], 2),
            ]);
        }
        $stmt->close();
    }
    fclose($f);
    return $filename;
}
?>