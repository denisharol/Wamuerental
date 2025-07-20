<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .report-btn {
      background: none;
      border: none;
      padding: 0.5rem 0;
      font-size: 1.125rem;
      color: #4a5568;
      cursor: pointer;
      text-align: left;
      width: 100%;
    }

    .report-btn:hover {
      color: #2b6cb0;
      text-decoration: underline;
    }

    .report-btn:focus {
      outline: none;
    }

    .report-btn.active {
      color: white;
      background-color: rgb(0, 0, 130);
      border-radius: 0.375rem;
      padding: 0.5rem;
    }

    .text-2xl {
      font-size: 2rem;
    }

    .text-xl {
      font-size: 1.5rem;
    }
  </style>
  <script>
    function loadReport(reportPage) {
      const title = document.getElementById("reportTitle");
      const iframe = document.getElementById("reportFrame");
      const reportName = reportPage.replace(/([A-Z])/g, ' $1').trim();
      title.innerText = `Reports for ${reportName}`;
      iframe.src = reportPage + ".php";

      // Highlight the active button
      const buttons = document.querySelectorAll(".report-btn");
      buttons.forEach(button => button.classList.remove("active"));
      const activeButton = document.querySelector(`button[onclick="loadReport('${reportPage}')"]`);
      if (activeButton) {
        activeButton.classList.add("active");
      }
    }
  </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans h-screen overflow-hidden">
  <div class="flex h-full">

    <aside class="w-1/4 border-r border-gray-200 bg-white p-4 space-y-4">
      <h2 class="text-2xl font-bold mb-4">Reports</h2>
      <button onclick="loadReport('Financialstatements')" class="report-btn">Financial Statements</button>
      <button onclick="loadReport('Tenantstatements')" class="report-btn">Tenant Statements</button>
      <button onclick="loadReport('Propertystatements')" class="report-btn">Property Statements</button>
      <button onclick="loadReport('Arrearsreport')" class="report-btn">Arrears Report</button>
      <button onclick="loadReport('Expensesreport')" class="report-btn">Expenses Report</button>
      <button onclick="loadReport('Assesmentoverview')" class="report-btn">Assesment Overview</button>
      <button onclick="loadReport('Outstandingbalances')" class="report-btn">Outstanding Balances</button>
      <button onclick="loadReport('Logs')" class="report-btn">Logs</button>
    </aside>

    <main class="w-3/4 flex flex-col">
      <div class="p-6 border-b bg-white">
        <h2 id="reportTitle" class="text-2xl font-semibold">Reports for (None Selected)</h2>
      </div>
      <div class="flex-1 overflow-y-auto bg-gray-100">
        <iframe id="reportFrame" class="w-full h-full" frameborder="0"></iframe>
      </div>
    </main>
  </div>
</body>
</html>