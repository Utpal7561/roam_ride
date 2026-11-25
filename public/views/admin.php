<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Admin Operations Center — Rome &amp; Ride</title>
  <meta name="theme-color" content="#FFE500">
<?php
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_contains($reqUri, '/public')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseHref = rtrim($scriptDir, '/') . '/';
} else {
    $folder = defined('ROOT_PATH') ? basename(ROOT_PATH) : 'roam_ride';
    $baseHref = '/' . trim($folder, '/') . '/';
}
if ($baseHref === '//') $baseHref = '/';
?>
  <base href="<?= htmlspecialchars($baseHref) ?>">
  <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            'rnr-yellow': '#FFE500',
            'rnr-yellow-dark': '#E5CD00',
            'rnr-red': '#EF4444',
            'rnr-black': '#09090B',
            'rnr-dark': '#121215',
            'rnr-surface': '#18181B',
            'rnr-border': '#27272A',
          }
        }
      }
    }
  </script>

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom Styling -->
  <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-rnr-black text-white antialiased min-h-screen flex flex-col">

  <!-- Top Navigation Bar -->
  <header class="sticky top-0 z-40 bg-zinc-950/95 backdrop-blur-md border-b border-zinc-800 h-16 flex items-center justify-between px-4 sm:px-6">
    <div class="flex items-center gap-4">
      <!-- Mobile Drawer Toggle Button -->
      <button id="admin-drawer-toggle" class="lg:hidden text-zinc-300 hover:text-white text-xl p-1">
        <i class="bi bi-list"></i>
      </button>

      <a href="./" class="flex items-center gap-2.5">
        <img src="assets/images/logo.jpg" alt="Logo" class="h-9 w-auto rounded border border-yellow-brand/40">
        <div class="leading-tight">
          <span class="text-base font-black text-white">ROME &amp; RIDE</span>
          <span class="badge-status bg-yellow-brand text-black font-extrabold text-[9px] block">OPERATIONS</span>
        </div>
      </a>
    </div>

    <!-- Quick Stats & Admin Profile -->
    <div class="flex items-center gap-3 text-xs">
      <div id="admin-user-display" class="font-bold text-zinc-300 hidden sm:block">Admin Chief (Super Admin)</div>
      <a href="./" class="btn-rnr-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
        <i class="bi bi-box-arrow-up-right"></i> Customer Site
      </a>
      <button onclick="Auth.logout()" class="btn-rnr-secondary text-xs px-2.5 py-1.5 text-red-400 hover:border-red-500">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </div>
  </header>

  <div class="flex-1 flex overflow-hidden relative">
    
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="admin-sidebar-overlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-30 hidden lg:hidden"></div>

    <!-- Sidebar Navigation -->
    <aside id="admin-sidebar" class="fixed lg:static top-16 bottom-0 left-0 w-64 bg-zinc-950 border-r border-zinc-800 p-4 z-40 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 overflow-y-auto space-y-1.5">
      <div class="text-[10px] font-extrabold text-zinc-500 uppercase tracking-widest px-3 mb-2">Management</div>

      <button data-tab="dashboard" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold bg-yellow-brand text-black shadow-md transition-all">
        <i class="bi bi-speedometer2 text-base"></i> Dashboard
      </button>

      <button data-tab="bookings" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-calendar-check text-base"></i> Bookings
      </button>

      <button data-tab="payments" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-cash-stack text-base"></i> Payments Ledger
      </button>

      <button data-tab="vehicles" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-bicycle text-base"></i> Fleet Vehicles
      </button>

      <button data-tab="handover" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-clipboard2-check text-base"></i> Handover &amp; Return
      </button>

      <div class="text-[10px] font-extrabold text-zinc-500 uppercase tracking-widest px-3 pt-4 mb-2">Operations &amp; Legal</div>

      <button data-tab="reports" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-graph-up-arrow text-base"></i> Reports &amp; CSV
      </button>

      <button data-tab="support" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-headset text-base"></i> Support Desk
      </button>

      <button data-tab="promotions" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-ticket-perforated text-base"></i> Promo Coupons
      </button>

      <button data-tab="settings" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-gear text-base"></i> System Settings
      </button>

      <button data-tab="audit-logs" class="admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all">
        <i class="bi bi-shield-check text-base"></i> Audit Trail
      </button>
    </aside>

    <!-- Main Content Work Area -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">

      <!-- ================= 1. DASHBOARD VIEW PANEL ================= -->
      <div id="panel-dashboard" class="admin-view-panel space-y-6">
        
        <!-- Pending Documents Alert -->
        <div id="stat-pending-docs-banner" class="hidden p-4 rounded-xl bg-yellow-brand/10 border border-yellow-brand text-yellow-brand flex items-center justify-between text-xs font-bold">
          <div class="flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-base"></i>
            <span><strong id="stat-pending-docs-count">0</strong> Customer Driving Licenses Pending Verification</span>
          </div>
          <button onclick="AdminApp.switchTab('bookings')" class="btn-rnr-primary text-xs py-1 px-3 font-black">Review Documents</button>
        </div>

        <!-- Live Summary Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Collected Revenue</span>
            <span id="stat-total-revenue" class="text-xl font-black text-emerald-400">₹0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Outstanding Bal.</span>
            <span id="stat-outstanding" class="text-xl font-black text-yellow-brand">₹0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Total Bookings</span>
            <span id="stat-total-bookings" class="text-xl font-black text-white">0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Pending Review</span>
            <span id="stat-pending-bookings" class="text-xl font-black text-red-400">0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">On Rental Active</span>
            <span id="stat-active-rentals" class="text-xl font-black text-blue-400">0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Available Fleet</span>
            <span id="stat-available-fleet" class="text-xl font-black text-white">0 / 0</span>
          </div>
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <span class="text-[11px] text-zinc-500 font-bold block mb-1 uppercase">Maintenance</span>
            <span id="stat-maintenance-fleet" class="text-xl font-black text-orange-400">0</span>
          </div>
        </div>

        <!-- Monthly Trends & Quick Insights -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 rnr-card p-5 border-zinc-800">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-extrabold text-white text-base flex items-center gap-2">
                <i class="bi bi-graph-up text-yellow-brand"></i> Revenue Trends (Last 6 Months)
              </h3>
              <button onclick="AdminApp.exportReport('revenue')" class="text-xs text-yellow-brand hover:underline font-bold">
                <i class="bi bi-download mr-1"></i> Export CSV
              </button>
            </div>
            <div id="dash-trends-container" class="space-y-2">
              <!-- Rendered dynamically -->
            </div>
          </div>

          <!-- Quick Action Operations Panel -->
          <div class="rnr-card p-5 border-zinc-800 flex flex-col justify-between">
            <div>
              <h3 class="font-extrabold text-white text-base mb-3 flex items-center gap-2">
                <i class="bi bi-lightning-charge text-yellow-brand"></i> Quick Operations
              </h3>
              <div class="space-y-2 text-xs">
                <button onclick="AdminApp.openManualBookingModal()" class="w-full text-left p-3 rounded-lg bg-zinc-900 border border-yellow-brand/50 hover:bg-yellow-brand/10 flex items-center justify-between text-yellow-brand font-bold">
                  <span>+ Create Manual Booking (Counter / Walk-in)</span>
                  <i class="bi bi-plus-circle text-yellow-brand"></i>
                </button>
                <button onclick="AdminApp.switchTab('settings')" class="w-full text-left p-3 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-yellow-brand flex items-center justify-between">
                  <span>Change Homepage Hero Picture</span>
                  <i class="bi bi-image text-yellow-brand"></i>
                </button>
                <button onclick="AdminApp.switchTab('bookings')" class="w-full text-left p-3 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-yellow-brand flex items-center justify-between">
                  <span>Manage Active Reservations</span>
                  <i class="bi bi-chevron-right text-zinc-500"></i>
                </button>
                <button onclick="AdminApp.switchTab('handover')" class="w-full text-left p-3 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-yellow-brand flex items-center justify-between">
                  <span>Record Pickup / Return Inspection</span>
                  <i class="bi bi-chevron-right text-zinc-500"></i>
                </button>
                <button onclick="AdminApp.openAddVehicleModal()" class="w-full text-left p-3 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-yellow-brand flex items-center justify-between">
                  <span>Add New Scooter to Fleet</span>
                  <i class="bi bi-plus-circle text-yellow-brand"></i>
                </button>
              </div>
            </div>
            <div class="pt-4 text-xs text-zinc-500 border-t border-zinc-800 mt-4">
              System Time: <strong class="text-zinc-300 font-mono"><?= date('Y-m-d H:i') ?> IST</strong>
            </div>
          </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="rnr-card p-5 border-zinc-800">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-extrabold text-white text-base">Recent Customer Bookings</h3>
            <button onclick="AdminApp.switchTab('bookings')" class="text-xs font-bold text-yellow-brand hover:underline">View All</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase">
                  <th class="py-2.5 px-3">Booking ID</th>
                  <th class="py-2.5 px-3">Customer</th>
                  <th class="py-2.5 px-3">Vehicle</th>
                  <th class="py-2.5 px-3">Pickup Time</th>
                  <th class="py-2.5 px-3">Status</th>
                  <th class="py-2.5 px-3">Total</th>
                  <th class="py-2.5 px-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody id="dash-recent-bookings">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ================= 2. BOOKINGS VIEW PANEL ================= -->
      <div id="panel-bookings" class="admin-view-panel hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-black text-white">Booking Management</h2>
            <p class="text-xs text-zinc-400">Manage status workflows, physical vehicle assignments, and customer document approvals.</p>
          </div>
          <div class="flex items-center gap-2">
            <button onclick="AdminApp.openManualBookingModal()" class="btn-rnr-primary text-xs px-4 py-2 font-black flex items-center gap-1.5 shadow-lg">
              <i class="bi bi-plus-lg"></i> New Manual Booking
            </button>
            <button onclick="AdminApp.exportReport('bookings')" class="btn-rnr-secondary text-xs px-4 py-2 border-yellow-brand text-yellow-brand">
              <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Export Bookings CSV
            </button>
          </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="rnr-card p-4 bg-zinc-900 flex flex-wrap items-center gap-4 text-xs">
          <div class="flex-1 min-w-[200px]">
            <input type="text" id="admin-booking-filter-search" oninput="AdminApp.loadBookings()" placeholder="Search code, customer name or phone..." class="rnr-input text-xs">
          </div>
          <div class="w-48">
            <select id="admin-booking-filter-status" onchange="AdminApp.loadBookings()" class="rnr-input text-xs">
              <option value="">All Statuses</option>
              <option value="pending_approval">Pending Approval</option>
              <option value="pending_booking">Pending Booking</option>
              <option value="pending_payment">Pending Payment</option>
              <option value="pending_document_verification">Pending Docs</option>
              <option value="booking_confirmed">Booking Confirmed</option>
              <option value="vehicle_assigned">Vehicle Assigned</option>
              <option value="rental_active">Rental Active</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <button onclick="AdminApp.loadBookings()" class="btn-rnr-primary text-xs px-4 py-2 font-bold">Filter</button>
        </div>

        <!-- Bookings Data Table -->
        <div class="rnr-card border-zinc-800 overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase bg-zinc-950/60">
                <th class="py-3 px-3">Booking ID</th>
                <th class="py-3 px-3">Customer</th>
                <th class="py-3 px-3">Booked / Assigned</th>
                <th class="py-3 px-3">Schedule</th>
                <th class="py-3 px-3">Status</th>
                <th class="py-3 px-3">Financials</th>
                <th class="py-3 px-3">Docs</th>
                <th class="py-3 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="admin-bookings-table-body">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ================= 3. PAYMENTS VIEW PANEL (SECTION 13) ================= -->
      <div id="panel-payments" class="admin-view-panel hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-black text-white">Payments &amp; Ledger Module</h2>
            <p class="text-xs text-zinc-400">Immutable transaction ledger for booking advances, cash handovers, UPI, and refunds.</p>
          </div>
          <button onclick="AdminApp.exportReport('revenue')" class="btn-rnr-primary text-xs px-4 py-2 font-black">
            <i class="bi bi-download mr-1"></i> Export Revenue Report
          </button>
        </div>

        <!-- Revenue by Method Breakdown -->
        <div class="rnr-card p-5 border-zinc-800">
          <h3 class="font-bold text-white text-base mb-4">Recent Payment Transaction Ledger</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase">
                  <th class="py-3 px-3">Date</th>
                  <th class="py-3 px-3">Method</th>
                  <th class="py-3 px-3">Payment Type</th>
                  <th class="py-3 px-3">Txn Count</th>
                  <th class="py-3 px-3 text-right">Amount</th>
                </tr>
              </thead>
              <tbody id="admin-revenue-breakdown">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ================= 4. FLEET VEHICLES VIEW PANEL ================= -->
      <div id="panel-vehicles" class="admin-view-panel hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-black text-white">Vehicle Fleet Management</h2>
            <p class="text-xs text-zinc-400">Inventory control, rates, insurance dates, and operational statuses.</p>
          </div>
          <button onclick="AdminApp.openAddVehicleModal()" class="btn-rnr-primary text-xs px-4 py-2 font-black">
            <i class="bi bi-plus-lg mr-1"></i> Add New Vehicle
          </button>
        </div>

        <!-- Vehicles Table -->
        <div class="rnr-card border-zinc-800 overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase bg-zinc-950/60">
                <th class="py-3 px-3">Photo</th>
                <th class="py-3 px-3">Vehicle</th>
                <th class="py-3 px-3">Reg. Number</th>
                <th class="py-3 px-3">Category</th>
                <th class="py-3 px-3">Daily Rate</th>
                <th class="py-3 px-3">Security Deposit</th>
                <th class="py-3 px-3">Status</th>
                <th class="py-3 px-3">Hub Location</th>
                <th class="py-3 px-3 text-right">Manage</th>
              </tr>
            </thead>
            <tbody id="admin-vehicles-table-body">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ================= 5. HANDOVER & RETURN CHECKLIST (SECTION 16) ================= -->
      <div id="panel-handover" class="admin-view-panel hidden space-y-6">
        <div>
          <h2 class="text-2xl font-black text-white">Vehicle Handover &amp; Return Checklists</h2>
          <p class="text-xs text-zinc-400">Inspect fuel levels, odometer, damages, and accessories before departure and upon return.</p>
        </div>

        <div class="rnr-card p-6 border-zinc-800 max-w-2xl bg-zinc-900">
          <form id="form-handover-checklist" onsubmit="AdminApp.submitHandoverChecklist(event)" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Select Active Booking *</label>
              <select id="ho-booking-select" required class="rnr-input text-xs"></select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Checklist Type *</label>
                <select id="ho-type" class="rnr-input text-xs">
                  <option value="pickup">Pickup Departure Checklist</option>
                  <option value="return">Return Arrival Checklist</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Odometer Reading (KM) *</label>
                <input type="number" id="ho-odo" required placeholder="e.g. 4520" class="rnr-input text-xs">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Fuel Level *</label>
                <select id="ho-fuel" class="rnr-input text-xs">
                  <option value="Full (100%)">Full (100%)</option>
                  <option value="75%">75% Fuel</option>
                  <option value="50%">50% Fuel</option>
                  <option value="25%">25% Fuel</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Sanitized Helmets Supplied</label>
                <input type="number" id="ho-helmets" value="2" min="1" max="2" class="rnr-input text-xs">
              </div>
            </div>

            <div>
              <label class="block font-bold text-zinc-300 mb-1">Condition &amp; Scratch Notes</label>
              <textarea id="ho-notes" rows="2" placeholder="Record any pre-existing scratches, tire condition, or notes..." class="rnr-input text-xs"></textarea>
            </div>

            <!-- Extra charges on return -->
            <div class="p-3.5 rounded-lg bg-zinc-950 border border-zinc-800 space-y-3">
              <span class="font-bold text-yellow-brand block">Return Adjustment Charges (if applicable):</span>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-zinc-400 mb-1">Cleaning Fee (₹)</label>
                  <input type="number" id="ho-clean-fee" value="0" class="rnr-input text-xs">
                </div>
                <div>
                  <label class="block text-zinc-400 mb-1">Fuel Deficit Fee (₹)</label>
                  <input type="number" id="ho-fuel-fee" value="0" class="rnr-input text-xs">
                </div>
              </div>
            </div>

            <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black">
              Record Inspection Checklist <i class="bi bi-check-circle-fill"></i>
            </button>
          </form>
        </div>
      </div>

      <!-- ================= 6. REPORTS VIEW PANEL ================= -->
      <div id="panel-reports" class="admin-view-panel hidden space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-black text-white">Fleet &amp; Revenue Reports</h2>
            <p class="text-xs text-zinc-400">Download Excel-compatible CSV exports for auditing and accounting.</p>
          </div>
          <div class="flex gap-2">
            <button onclick="AdminApp.exportReport('bookings')" class="btn-rnr-secondary text-xs px-3 py-2 border-yellow-brand text-yellow-brand font-bold">
              <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Bookings CSV
            </button>
            <button onclick="AdminApp.exportReport('revenue')" class="btn-rnr-primary text-xs px-3 py-2 font-bold">
              <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Revenue CSV
            </button>
          </div>
        </div>

        <div class="rnr-card p-5 border-zinc-800">
          <h3 class="font-bold text-white text-base mb-4">Vehicle Earnings &amp; Utilization</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase">
                  <th class="py-2.5 px-3">Vehicle</th>
                  <th class="py-2.5 px-3">Registration</th>
                  <th class="py-2.5 px-3">Trips Taken</th>
                  <th class="py-2.5 px-3 text-right">Total Earnings</th>
                </tr>
              </thead>
              <tbody id="rep-vehicles-body">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ================= 7. SUPPORT INBOX PANEL ================= -->
      <div id="panel-support" class="admin-view-panel hidden space-y-6">
        <div>
          <h2 class="text-2xl font-black text-white">Customer Support Tickets Inbox</h2>
          <p class="text-xs text-zinc-400">Respond to customer inquiries and booking complaints.</p>
        </div>

        <div id="admin-tickets-list" class="space-y-4">
          <!-- Rendered dynamically -->
        </div>
      </div>

      <!-- ================= 8. PROMO COUPONS PANEL ================= -->
      <div id="panel-promotions" class="admin-view-panel hidden space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-black text-white">Promotions &amp; Discounts</h2>
            <p class="text-xs text-zinc-400">Configure promotional discount codes for concerts, weekends, and festivals.</p>
          </div>
        </div>

        <div class="rnr-card p-5 border-zinc-800 max-w-lg bg-zinc-900">
          <h3 class="font-bold text-white text-sm mb-3">Create New Coupon Code</h3>
          <form onsubmit="event.preventDefault(); AdminApp.addPromotion();" class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Coupon Code * (e.g. FESTIVAL15)</label>
              <input type="text" id="promo-code" required class="rnr-input font-mono uppercase text-xs">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Discount Type *</label>
                <select id="promo-type" class="rnr-input text-xs">
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed">Fixed Amount (₹)</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Discount Value *</label>
                <input type="number" id="promo-val" required step="0.5" class="rnr-input text-xs">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Start Date *</label>
                <input type="date" id="promo-start" value="<?= date('Y-m-d') ?>" class="rnr-input text-xs">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Expiry Date *</label>
                <input type="date" id="promo-exp" value="<?= date('Y-12-31') ?>" class="rnr-input text-xs">
              </div>
            </div>
            <button type="button" onclick="API.post('/admin/promotions', {
              code: document.getElementById('promo-code').value,
              discount_type: document.getElementById('promo-type').value,
              discount_value: document.getElementById('promo-val').value,
              start_date: document.getElementById('promo-start').value,
              expiry_date: document.getElementById('promo-exp').value
            }).then(r=>{ if(r.success) { API.toast('Promo created!', 'success'); AdminApp.switchTab('dashboard'); } })" class="btn-rnr-primary w-full py-2.5 text-xs font-bold mt-2">
              Save Promo Coupon
            </button>
          </form>
        </div>
      </div>

      <!-- ================= 9. SETTINGS PANEL ================= -->
      <div id="panel-settings" class="admin-view-panel hidden space-y-6">
        <div>
          <h2 class="text-2xl font-black text-white">System Configuration</h2>
          <p class="text-xs text-zinc-400">Configure advance payment %, Pay Later policy, tax, and company contact details.</p>
        </div>

        <div class="rnr-card p-6 border-zinc-800 max-w-xl bg-zinc-900">
          <form onsubmit="AdminApp.saveSettings(event)" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Company / Platform Name</label>
              <input type="text" id="set-site-name" class="rnr-input text-xs">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Contact Phone</label>
                <input type="text" id="set-site-phone" class="rnr-input text-xs">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Contact Email</label>
                <input type="email" id="set-site-email" class="rnr-input text-xs">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Advance Payment Percentage (%)</label>
                <input type="number" id="set-advance-pct" min="10" max="100" class="rnr-input text-xs">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Allow Pay Later Reservations</label>
                <select id="set-pay-later-enabled" class="rnr-input text-xs">
                  <option value="1">Enabled</option>
                  <option value="0">Disabled</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn-rnr-primary py-2.5 px-6 text-xs font-bold">Save System Settings</button>
          </form>
        </div>

        <!-- Homepage Hero Picture & Content Manager -->
        <div class="rnr-card p-6 border-zinc-800 bg-zinc-900 mt-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 border-b border-zinc-800 pb-3">
            <div>
              <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                <i class="bi bi-image text-yellow-brand"></i> Homepage Hero Card & Picture Manager
              </h3>
              <p class="text-xs text-zinc-400 mt-0.5">Change the featured scooter picture, badge, headline, and details displayed on the homepage hero section.</p>
            </div>
            <a href="./" target="_blank" class="text-xs text-yellow-brand hover:underline font-bold flex items-center gap-1">
              View Live Homepage <i class="bi bi-box-arrow-up-right text-[10px]"></i>
            </a>
          </div>

          <form id="form-hero-settings" onsubmit="AdminApp.saveHeroSettings(event)" class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
            <!-- Live Preview Column -->
            <div class="lg:col-span-5 flex flex-col items-center">
              <span class="text-zinc-400 font-bold mb-2 self-start flex items-center gap-1.5">
                <i class="bi bi-eye"></i> Live Hero Preview (Home Page View):
              </span>
              <div class="w-full max-w-sm rnr-card p-4 bg-zinc-950 border border-zinc-800 rounded-2xl relative shadow-xl">
                <div class="relative w-full h-48 rounded-xl overflow-hidden bg-black flex items-center justify-center border border-zinc-900">
                  <img id="hero-preview-img" src="assets/images/vehicles/activa6g.jpg" class="w-full h-full object-contain p-2 transition-all">
                  <div class="absolute top-2 left-2">
                    <span id="hero-preview-badge" class="px-2.5 py-0.5 rounded-full bg-yellow-brand text-black font-extrabold text-[10px] tracking-wide uppercase">BESTSELLER</span>
                  </div>
                </div>
                <div class="mt-3 flex items-start justify-between">
                  <div>
                    <h4 id="hero-preview-title" class="font-extrabold text-white text-base leading-tight">Honda Activa 6G Sport</h4>
                    <p id="hero-preview-subtitle" class="text-xs text-yellow-brand font-bold mt-0.5">From ₹499 / day (₹49/hr)</p>
                  </div>
                  <button type="button" class="px-3 py-1 bg-yellow-brand text-black font-black text-xs rounded-lg uppercase cursor-default">Book</button>
                </div>
                <div class="mt-3 pt-3 border-t border-zinc-800 flex items-center justify-between text-[10px] text-zinc-400">
                  <span class="flex items-center gap-1"><i class="bi bi-geo-alt-fill text-yellow-brand"></i> <span id="hero-preview-tagleft">Kamarkuchi Hub (Sonapur)</span></span>
                  <span class="flex items-center gap-1"><i class="bi bi-clock-history text-yellow-brand"></i> <span id="hero-preview-tagright">Instant 5-Min Handover</span></span>
                </div>
              </div>
            </div>

            <!-- Edit Controls Column -->
            <div class="lg:col-span-7 space-y-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">
                  Upload New Hero Picture <span class="text-yellow-brand">(JPG, PNG, WebP)</span>
                </label>
                <input type="file" id="hero-image-file" accept="image/*" onchange="AdminApp.onHeroFileSelected(this)" class="rnr-input text-xs cursor-pointer">
                <p class="text-[11px] text-zinc-500 mt-1">Directly replaces the scooter image on the Rome & Ride home page. Max file size: 5MB.</p>
              </div>

              <div>
                <label class="block font-bold text-zinc-300 mb-1">Or Quick Pick From Fleet Vehicles</label>
                <select id="hero-vehicle-picker" onchange="AdminApp.onHeroVehicleSelected(this)" class="rnr-input text-xs">
                  <option value="">-- Select fleet vehicle to autofill image & info --</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-zinc-300 mb-1">Badge Text (e.g. BESTSELLER, HOT DEAL)</label>
                  <input type="text" id="hero-set-badge" oninput="document.getElementById('hero-preview-badge').innerText = this.value || 'BESTSELLER'" class="rnr-input text-xs" placeholder="BESTSELLER">
                </div>
                <div>
                  <label class="block font-bold text-zinc-300 mb-1">Hero Title (Vehicle / Headline)</label>
                  <input type="text" id="hero-set-title" oninput="document.getElementById('hero-preview-title').innerText = this.value || 'Honda Activa 6G Sport'" class="rnr-input text-xs" placeholder="Honda Activa 6G Sport">
                </div>
              </div>

              <div>
                <label class="block font-bold text-zinc-300 mb-1">Hero Subtitle / Rate Highlight</label>
                <input type="text" id="hero-set-subtitle" oninput="document.getElementById('hero-preview-subtitle').innerText = this.value || 'From ₹499 / day (₹49/hr)'" class="rnr-input text-xs" placeholder="From ₹499 / day (₹49/hr)">
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-zinc-300 mb-1">Left Tagline / Location</label>
                  <input type="text" id="hero-set-tagleft" oninput="document.getElementById('hero-preview-tagleft').innerText = this.value || 'Kamarkuchi Hub (Sonapur)'" class="rnr-input text-xs" placeholder="Kamarkuchi Hub (Sonapur)">
                </div>
                <div>
                  <label class="block font-bold text-zinc-300 mb-1">Right Tagline / Advantage</label>
                  <input type="text" id="hero-set-tagright" oninput="document.getElementById('hero-preview-tagright').innerText = this.value || 'Instant 5-Min Handover'" class="rnr-input text-xs" placeholder="Instant 5-Min Handover">
                </div>
              </div>

              <input type="hidden" id="hero-image-existing-path" value="">

              <div class="pt-2 flex items-center gap-3">
                <button type="submit" id="btn-save-hero-settings" class="btn-rnr-primary py-2.5 px-6 text-xs font-black flex items-center gap-2">
                  <i class="bi bi-cloud-arrow-up-fill"></i> Save & Apply Homepage Picture
                </button>
                <button type="button" onclick="AdminApp.resetHeroDefaults()" class="btn-rnr-secondary py-2.5 px-4 text-xs font-bold text-zinc-400">
                  Reset Defaults
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- ================= 10. AUDIT TRAIL PANEL ================= -->
      <div id="panel-audit-logs" class="admin-view-panel hidden space-y-6">
        <div>
          <h2 class="text-2xl font-black text-white">Administrative Audit Trail</h2>
          <p class="text-xs text-zinc-400">Immutable chronological record of administrative actions, overrides, and financial entries.</p>
        </div>

        <div class="rnr-card border-zinc-800 overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase bg-zinc-950/60">
                <th class="py-2.5 px-3">Timestamp</th>
                <th class="py-2.5 px-3">Staff / User</th>
                <th class="py-2.5 px-3">Action</th>
                <th class="py-2.5 px-3">Target Entity</th>
                <th class="py-2.5 px-3">Payload / Changes</th>
                <th class="py-2.5 px-3">IP Address</th>
              </tr>
            </thead>
            <tbody id="admin-audit-logs-body">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>

  <!-- ================= ADMIN MODALS ================= -->

  <!-- 1. Manage Booking Detail Modal -->
  <div id="admin-booking-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-3xl bg-zinc-950 border-zinc-800 shadow-2xl relative max-h-[92vh] overflow-y-auto">
      <button onclick="AdminApp.closeBookingModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      <div id="admin-booking-modal-body">
        <!-- Rendered dynamically -->
      </div>
    </div>
  </div>

  <!-- 1b. Create Manual Booking Modal (Walk-in / Phone / Counter) -->
  <div id="modal-manual-booking" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-2xl bg-zinc-950 border-yellow-brand/50 shadow-2xl relative max-h-[92vh] overflow-y-auto">
      <button type="button" onclick="AdminApp.closeManualBookingModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      
      <div class="flex items-center gap-3 mb-4 pb-3 border-b border-zinc-800">
        <div class="w-10 h-10 rounded-xl bg-yellow-brand text-black flex items-center justify-center font-black text-lg">
          <i class="bi bi-person-plus-fill"></i>
        </div>
        <div>
          <h3 class="text-lg font-black text-white">Create Manual Booking</h3>
          <p class="text-xs text-zinc-400">Record a walk-in, phone, or counter reservation with immediate fleet assignment and counter payment.</p>
        </div>
      </div>

      <form id="form-manual-booking" onsubmit="AdminApp.submitManualBooking(event)" class="space-y-4 text-xs">
        
        <!-- Customer Details -->
        <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
          <h4 class="font-bold text-yellow-brand uppercase text-[11px] tracking-wide flex items-center gap-1.5">
            <i class="bi bi-person-badge"></i> 1. Customer Information
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Customer Full Name *</label>
              <input type="text" id="manual-book-cust-name" required placeholder="e.g. Rahul Sharma" class="rnr-input text-xs">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Phone Number * (10 digits)</label>
              <input type="tel" id="manual-book-cust-phone" required placeholder="e.g. 9876543210" class="rnr-input text-xs font-mono">
            </div>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Email Address (Optional)</label>
            <input type="email" id="manual-book-cust-email" placeholder="customer@example.com" class="rnr-input text-xs">
          </div>
        </div>

        <!-- Vehicle Selection & Schedule -->
        <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
          <h4 class="font-bold text-yellow-brand uppercase text-[11px] tracking-wide flex items-center gap-1.5">
            <i class="bi bi-scooter"></i> 2. Vehicle & Schedule
          </h4>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Select Available Vehicle *</label>
            <select id="manual-book-vehicle" required onchange="AdminApp.onManualVehicleChange()" class="rnr-input text-xs font-medium">
              <option value="">-- Choose scooter or motorcycle from fleet --</option>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Pickup Date & Time *</label>
              <input type="datetime-local" id="manual-book-pickup" required onchange="AdminApp.calculateManualBookingTotals()" class="rnr-input text-xs">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Return Date & Time *</label>
              <input type="datetime-local" id="manual-book-return" required onchange="AdminApp.calculateManualBookingTotals()" class="rnr-input text-xs">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Pickup Location Hub</label>
              <select id="manual-book-pickup-loc" class="rnr-input text-xs">
                <option value="1">Central Station Hub (Guwahati Railway Station)</option>
                <option value="2">Airport Express Hub (LGBI Airport)</option>
                <option value="3">City Center Hub (GS Road, Christian Basti)</option>
                <option value="4">University & Tech Hub (Jalukbari)</option>
                <option value="5">Kamarkuchi Hub (Sonapur)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Return Location Hub</label>
              <select id="manual-book-return-loc" class="rnr-input text-xs">
                <option value="1">Central Station Hub (Guwahati Railway Station)</option>
                <option value="2">Airport Express Hub (LGBI Airport)</option>
                <option value="3">City Center Hub (GS Road, Christian Basti)</option>
                <option value="4">University & Tech Hub (Jalukbari)</option>
                <option value="5">Kamarkuchi Hub (Sonapur)</option>
              </select>
            </div>
          </div>
          <div id="manual-book-duration-badge" class="text-xs text-zinc-400 font-mono">
            Duration: <span id="manual-book-days-label" class="font-bold text-white">1 Day(s)</span> (<span id="manual-book-hours-label">24</span> hrs)
          </div>
        </div>

        <!-- Financial Breakdown & Security Deposit -->
        <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
          <h4 class="font-bold text-yellow-brand uppercase text-[11px] tracking-wide flex items-center justify-between">
            <span class="flex items-center gap-1.5"><i class="bi bi-currency-rupee"></i> 3. Pricing & Security Deposit</span>
            <span class="text-[10px] text-zinc-400 font-normal">All amounts editable</span>
          </h4>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Daily Rate (₹)</label>
              <input type="number" id="manual-book-rate" step="1" oninput="AdminApp.calculateManualBookingTotals()" class="rnr-input text-xs font-mono font-bold">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Rental Charge (₹)</label>
              <input type="number" id="manual-book-rental" step="1" oninput="AdminApp.calculateManualBookingTotals(true)" class="rnr-input text-xs font-mono">
            </div>
            <div>
              <label class="block font-bold text-yellow-brand mb-1">Security Deposit (₹)</label>
              <input type="number" id="manual-book-deposit" step="1" value="1000" oninput="AdminApp.calculateManualBookingTotals(true)" class="rnr-input text-xs font-mono font-bold text-yellow-brand border-yellow-brand/50">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Discount (₹)</label>
              <input type="number" id="manual-book-discount" step="1" value="0" oninput="AdminApp.calculateManualBookingTotals(true)" class="rnr-input text-xs font-mono">
            </div>
          </div>

          <div class="p-2.5 rounded-lg bg-black border border-zinc-800 flex items-center justify-between">
            <div class="text-[11px] text-zinc-400">
              Tax (GST 5%): <strong id="manual-book-tax-label" class="text-zinc-200">₹0.00</strong>
            </div>
            <div class="text-right">
              <span class="text-[10px] text-zinc-400 uppercase font-bold">Total Booking Amount:</span>
              <div id="manual-book-total-label" class="text-base font-black text-yellow-brand font-mono">₹0.00</div>
            </div>
          </div>
        </div>

        <!-- Optional Counter Payment & Initial Status -->
        <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
          <h4 class="font-bold text-yellow-brand uppercase text-[11px] tracking-wide flex items-center gap-1.5">
            <i class="bi bi-wallet2"></i> 4. Counter Payment & Booking Status
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Amount Paid Now (₹)</label>
              <input type="number" id="manual-book-paid-now" step="1" value="0" class="rnr-input text-xs font-mono font-bold text-emerald-400">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Payment Method</label>
              <select id="manual-book-pay-method" class="rnr-input text-xs">
                <option value="cash">Cash at Counter</option>
                <option value="upi">Counter UPI / QR Code</option>
                <option value="card">POS Card Machine</option>
                <option value="bank_transfer">Direct Bank Transfer</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Initial Booking Status</label>
              <select id="manual-book-status" class="rnr-input text-xs">
                <option value="booking_confirmed" selected>Booking Confirmed</option>
                <option value="vehicle_assigned">Vehicle Assigned</option>
                <option value="rental_active">Rental Active (Instant Ride)</option>
                <option value="pending_approval">Pending Approval</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Payment Ref / Transaction ID</label>
              <input type="text" id="manual-book-pay-ref" placeholder="Cash Slip # or UPI UTR" class="rnr-input text-xs">
            </div>
            <div>
              <label class="block font-bold text-zinc-300 mb-1">Internal Staff Notes</label>
              <input type="text" id="manual-book-notes" placeholder="Walk-in guest, helmet issued, etc." class="rnr-input text-xs">
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-2 flex items-center justify-end gap-3">
          <button type="button" onclick="AdminApp.closeManualBookingModal()" class="btn-rnr-secondary text-xs px-4 py-2.5">
            Cancel
          </button>
          <button type="submit" id="btn-submit-manual-booking" class="btn-rnr-primary text-xs px-6 py-2.5 font-black flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i> Create &amp; Confirm Booking
          </button>
        </div>

      </form>
    </div>
  </div>

  <!-- 2. Manual Payment Record Modal -->
  <div id="modal-manual-payment" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-md bg-zinc-950 border-yellow-brand/40 shadow-2xl relative">
      <button onclick="AdminApp.closeManualPaymentModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl">&times;</button>
      <h3 class="text-lg font-black text-white mb-4"><i class="bi bi-cash mr-1 text-yellow-brand"></i> Record Counter Payment</h3>
      
      <form onsubmit="AdminApp.submitManualPayment(event)" class="space-y-3 text-xs">
        <input type="hidden" id="manual-pay-booking-id">
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Amount to Record (₹) *</label>
          <input type="number" id="manual-pay-amount" required step="0.5" class="rnr-input font-bold text-base text-yellow-brand">
        </div>
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Payment Method *</label>
          <select id="manual-pay-method" class="rnr-input">
            <option value="cash">Cash at Counter</option>
            <option value="upi">Counter UPI / QR Code</option>
            <option value="card">POS Card Machine</option>
            <option value="bank_transfer">Direct Bank Transfer (NEFT/IMPS)</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Payment Type *</label>
          <select id="manual-pay-type" class="rnr-input">
            <option value="partial_payment">Partial Balance Payment</option>
            <option value="booking_advance">Booking Advance</option>
            <option value="full_payment">Full Settlement</option>
            <option value="security_deposit">Security Deposit Handover</option>
            <option value="additional_charges">Extra Mileage / Cleaning Charge</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Staff Notes / Cash Receipt Number</label>
          <input type="text" id="manual-pay-notes" placeholder="Receipt # or Transaction ID" class="rnr-input">
        </div>
        <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black mt-3">
          Log Payment Transaction <i class="bi bi-check2"></i>
        </button>
      </form>
    </div>
  </div>

  <!-- 3. Add Vehicle Modal -->
  <div id="modal-add-vehicle" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-2xl bg-zinc-950 border-zinc-800 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button onclick="AdminApp.closeAddVehicleModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl">&times;</button>
      <h3 class="text-xl font-black text-white mb-4">Add Vehicle to Fleet</h3>

      <form id="form-add-vehicle" onsubmit="AdminApp.submitAddVehicle(event)" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Vehicle Name *</label>
            <input type="text" name="name" required placeholder="e.g. Honda Activa 6G Sport" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Brand *</label>
            <input type="text" name="brand" required placeholder="e.g. Honda" class="rnr-input">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Model *</label>
            <input type="text" name="model" required placeholder="Activa 6G" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Category *</label>
            <select name="category_id" class="rnr-input">
              <option value="1">Scooters</option>
              <option value="2">Electric Scooters</option>
              <option value="3">Premium Scooters</option>
              <option value="4">Bikes &amp; Cruisers</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Registration # *</label>
            <input type="text" name="reg_number" required placeholder="AS-01-BK-9999" class="rnr-input uppercase font-mono">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Daily Rate (₹) *</label>
            <input type="number" name="daily_rate" required placeholder="499" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Hourly Rate (₹)</label>
            <input type="number" name="hourly_rate" placeholder="49" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Security Deposit (₹)</label>
            <input type="number" name="deposit_amount" value="1000" class="rnr-input">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Fuel Type</label>
            <select name="fuel_type" class="rnr-input">
              <option value="Petrol">Petrol</option>
              <option value="Electric">Electric</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Transmission</label>
            <select name="transmission" class="rnr-input">
              <option value="Automatic">Automatic</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Hub Location *</label>
            <select name="pickup_location_id" class="rnr-input">
              <option value="1">Central Station Hub</option>
              <option value="2">Airport Express Hub</option>
              <option value="3">City Center Hub</option>
              <option value="4">University Hub</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-zinc-300 mb-1">Upload Photo</label>
          <input type="file" name="primary_image" accept="image/*" class="rnr-input">
        </div>

        <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black">
          Add Vehicle to Fleet <i class="bi bi-plus-lg"></i>
        </button>
      </form>
    </div>
  </div>

  <!-- 3b. Edit Vehicle Modal -->
  <div id="modal-edit-vehicle" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-2xl bg-zinc-950 border-yellow-brand/40 shadow-2xl relative max-h-[92vh] overflow-y-auto">
      <button onclick="AdminApp.closeEditVehicleModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      <div class="flex items-center gap-2 mb-4">
        <span class="badge-status bg-yellow-brand text-black font-extrabold text-[10px]">FLEET INVENTORY</span>
        <h3 class="text-xl font-black text-white">Edit Vehicle Details</h3>
      </div>

      <form id="form-edit-vehicle" onsubmit="AdminApp.submitEditVehicle(event)" class="space-y-4 text-xs">
        <input type="hidden" id="edit-vehicle-id" name="id">

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Vehicle Name *</label>
            <input type="text" id="edit-veh-name" name="name" required class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Registration # *</label>
            <input type="text" id="edit-veh-reg" name="reg_number" required class="rnr-input font-mono uppercase text-yellow-brand font-bold">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Brand *</label>
            <input type="text" id="edit-veh-brand" name="brand" required class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Model *</label>
            <input type="text" id="edit-veh-model" name="model" required class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Category *</label>
            <select id="edit-veh-category" name="category_id" class="rnr-input">
              <option value="1">Scooters</option>
              <option value="2">Electric Scooters</option>
              <option value="3">Premium Scooters</option>
              <option value="4">Bikes &amp; Cruisers</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Daily Rate (₹) *</label>
            <input type="number" id="edit-veh-daily-rate" name="daily_rate" required step="1" class="rnr-input font-bold text-white">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Hourly Rate (₹)</label>
            <input type="number" id="edit-veh-hourly-rate" name="hourly_rate" step="1" class="rnr-input font-bold text-white">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Security Deposit (₹)</label>
            <input type="number" id="edit-veh-deposit" name="deposit_amount" step="1" class="rnr-input font-bold text-yellow-brand">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Availability Status *</label>
            <select id="edit-veh-status" name="availability_status" class="rnr-input font-bold">
              <option value="available">Available</option>
              <option value="reserved">Reserved</option>
              <option value="booked">Booked</option>
              <option value="assigned">Assigned</option>
              <option value="rental_active">Rental Active</option>
              <option value="maintenance">Maintenance</option>
              <option value="out_of_service">Out of Service</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Fuel Type</label>
            <select id="edit-veh-fuel" name="fuel_type" class="rnr-input">
              <option value="Petrol">Petrol</option>
              <option value="Electric">Electric</option>
              <option value="Hybrid">Hybrid</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Transmission</label>
            <select id="edit-veh-transmission" name="transmission" class="rnr-input">
              <option value="Automatic">Automatic</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Engine / Battery</label>
            <input type="text" id="edit-veh-engine" name="engine_capacity" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Mileage / Range</label>
            <input type="text" id="edit-veh-mileage" name="mileage" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Condition</label>
            <select id="edit-veh-condition" name="condition_status" class="rnr-input">
              <option value="excellent">Excellent</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Hub Location</label>
            <select id="edit-veh-location" name="pickup_location_id" class="rnr-input">
              <option value="1">Central Station Hub</option>
              <option value="2">Airport Express Hub</option>
              <option value="3">City Center Hub</option>
              <option value="4">University Hub</option>
            </select>
          </div>
          <div class="flex items-center gap-2 pt-6">
            <input type="checkbox" id="edit-veh-featured" name="is_featured" value="1" class="w-4 h-4 accent-yellow-400 cursor-pointer">
            <label for="edit-veh-featured" class="font-bold text-white cursor-pointer">Show on Featured Banner</label>
          </div>
        </div>

        <!-- Photo Update -->
        <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center gap-4">
          <img id="edit-veh-photo-preview" src="assets/images/vehicles/activa6g.jpg" class="w-20 h-14 object-cover rounded border border-zinc-700">
          <div class="flex-1">
            <label class="block font-bold text-zinc-300 mb-1">Replace Photo (Optional)</label>
            <input type="file" name="primary_image" accept="image/*" class="rnr-input text-xs cursor-pointer">
          </div>
        </div>

        <div>
          <label class="block font-bold text-zinc-300 mb-1">Internal Notes</label>
          <input type="text" id="edit-veh-notes" name="notes" placeholder="Service notes, tracker ID, etc." class="rnr-input">
        </div>

        <div class="pt-2 flex justify-end gap-3">
          <button type="button" onclick="AdminApp.closeEditVehicleModal()" class="btn-rnr-secondary text-xs px-4">Cancel</button>
          <button type="submit" id="btn-save-vehicle-edit" class="btn-rnr-primary text-xs px-6 font-black py-2.5">
            Save Changes <i class="bi bi-check2"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. Dedicated Admin Login Gate Modal -->
  <div id="admin-auth-gate-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-md bg-zinc-950 border-yellow-brand/50 shadow-2xl relative">
      <div class="text-center mb-6">
        <img src="assets/images/logo.jpg" class="h-12 mx-auto rounded border border-yellow-brand/40 mb-3">
        <h3 class="text-xl font-black text-white">Operations Center Login</h3>
        <p class="text-xs text-zinc-400 mt-1">Please authenticate with staff or administrator credentials.</p>
      </div>

      <form id="form-admin-direct-login" onsubmit="AdminApp.handleAdminLogin(event)" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Email or Phone Number *</label>
          <input type="text" id="admin-login-id" required placeholder="admin@romeandride.com" value="admin@romeandride.com" class="rnr-input font-medium">
        </div>
        <div>
          <label class="block font-bold text-zinc-300 mb-1">Password *</label>
          <input type="password" id="admin-login-pass" required placeholder="••••••••" value="admin123" class="rnr-input font-medium">
        </div>
        <button type="submit" id="btn-admin-gate-submit" class="btn-rnr-primary w-full py-3 text-xs font-black">
          Unlock Operations Dashboard <i class="bi bi-shield-lock-fill ml-1"></i>
        </button>
      </form>

      <div class="mt-4 pt-4 border-t border-zinc-800 text-[11px] text-zinc-400 space-y-1.5">
        <div class="font-bold text-white flex items-center justify-between">
          <span>Demo Staff Accounts:</span>
          <button type="button" onclick="document.getElementById('admin-login-id').value='admin@romeandride.com'; document.getElementById('admin-login-pass').value='admin123';" class="text-yellow-brand hover:underline font-bold text-[10px]">Autofill Admin</button>
        </div>
        <div><strong class="text-yellow-brand">Super Admin:</strong> admin@romeandride.com / admin123</div>
        <div><strong class="text-zinc-300">Booking Desk:</strong> booking@romeandride.com / manager123</div>
        <div><strong class="text-zinc-300">Finance Desk:</strong> payment@romeandride.com / manager123</div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/js/api.js"></script>
  <script src="assets/js/auth.js"></script>
  <script src="assets/js/admin.js"></script>
</body>
</html>
