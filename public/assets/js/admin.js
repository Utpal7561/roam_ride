/**
 * Rome & Ride — Admin Operations Dashboard Controller
 * Full RBAC, Fleet, Bookings, Payments, Handover & Reports
 */

const AdminApp = {
  currentTab: 'dashboard',
  bookings: [],
  vehicles: [],
  payments: [],
  customers: [],

  async init() {
    if (this.checkAdminAuth()) {
      await this.loadDashboardData();
    }
    this.bindNavigation();
  },

  checkAdminAuth() {
    const user = Auth.getUser();
    const gateModal = document.getElementById('admin-auth-gate-modal');
    if (!Auth.isLoggedIn() || !Auth.isAdmin()) {
      if (gateModal) gateModal.classList.remove('hidden');
      return false;
    } else {
      if (gateModal) gateModal.classList.add('hidden');
      const userDisplay = document.getElementById('admin-user-display');
      if (userDisplay) userDisplay.textContent = `${user.name} (${user.role.toUpperCase()})`;
      return true;
    }
  },

  async handleAdminLogin(e) {
    e.preventDefault();
    const id = document.getElementById('admin-login-id')?.value;
    const pass = document.getElementById('admin-login-pass')?.value;
    const btn = document.getElementById('btn-admin-gate-submit');

    if (!id || !pass) {
      API.toast('Please enter both login and password.', 'warning');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Authenticating...';
    }

    try {
      const ok = await Auth.login(id, pass);
      if (ok && Auth.isAdmin()) {
        document.getElementById('admin-auth-gate-modal')?.classList.add('hidden');
        const user = Auth.getUser();
        const userDisplay = document.getElementById('admin-user-display');
        if (userDisplay) userDisplay.textContent = `${user.name} (${user.role.toUpperCase()})`;
        API.toast(`Welcome to Operations Control, ${user.name}!`, 'success');
        await this.loadDashboardData();
        await this.loadVehicles();
      } else if (ok && !Auth.isAdmin()) {
        API.toast('Access Denied: Customer accounts cannot access the operations center.', 'error');
        Auth.logout(false);
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Unlock Operations Dashboard <i class="bi bi-shield-lock-fill ml-1"></i>';
      }
    }
  },

  bindNavigation() {
    document.querySelectorAll('.admin-nav-item').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const tab = btn.dataset.tab;
        this.switchTab(tab);
      });
    });

    // Mobile drawer toggle
    const toggleBtn = document.getElementById('admin-drawer-toggle');
    const drawer = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-sidebar-overlay');

    if (toggleBtn && drawer) {
      toggleBtn.addEventListener('click', () => {
        drawer.classList.toggle('-translate-x-full');
        overlay?.classList.toggle('hidden');
      });
      overlay?.addEventListener('click', () => {
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }
  },

  switchTab(tab) {
    this.currentTab = tab;

    // Update active nav button styling
    document.querySelectorAll('.admin-nav-item').forEach(btn => {
      if (btn.dataset.tab === tab) {
        btn.className = 'admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold bg-yellow-brand text-black shadow-md transition-all';
      } else {
        btn.className = 'admin-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-all';
      }
    });

    // Hide all view panels
    document.querySelectorAll('.admin-view-panel').forEach(panel => {
      panel.classList.add('hidden');
    });

    // Show target view panel
    const target = document.getElementById(`panel-${tab}`);
    if (target) {
      target.classList.remove('hidden');
    }

    // Close mobile drawer if open
    document.getElementById('admin-sidebar')?.classList.add('-translate-x-full');
    document.getElementById('admin-sidebar-overlay')?.classList.add('hidden');

    // Route actions
    if (tab === 'dashboard') this.loadDashboardData();
    else if (tab === 'bookings') this.loadBookings();
    else if (tab === 'vehicles') this.loadVehicles();
    else if (tab === 'payments') this.loadPayments();
    else if (tab === 'handover') this.loadHandoverModule();
    else if (tab === 'documents') this.loadDocumentsModule();
    else if (tab === 'reports') this.loadReportsModule();
    else if (tab === 'support') this.loadSupportModule();
    else if (tab === 'promotions') this.loadPromotionsModule();
    else if (tab === 'settings') this.loadSettingsModule();
    else if (tab === 'customers') this.loadCustomersModule();
    else if (tab === 'audit-logs') this.loadAuditLogs();
  },

  /**
   * 1. Dashboard Tab
   */
  async loadDashboardData() {
    try {
      const res = await API.get('/admin/dashboard');
      if (!res.success) return;

      const d = res.data;

      // Update counters
      document.getElementById('stat-total-revenue').textContent = `₹${d.financials.total_collected.toLocaleString('en-IN')}`;
      document.getElementById('stat-outstanding').textContent = `₹${d.financials.outstanding_balance.toLocaleString('en-IN')}`;
      document.getElementById('stat-total-bookings').textContent = d.bookings.total;
      document.getElementById('stat-pending-bookings').textContent = d.bookings.pending;
      document.getElementById('stat-active-rentals').textContent = d.bookings.active_rentals;
      document.getElementById('stat-available-fleet').textContent = `${d.vehicles.available} / ${d.vehicles.total}`;
      document.getElementById('stat-maintenance-fleet').textContent = d.vehicles.maintenance;

      // Pending documents alert banner
      const docBanner = document.getElementById('stat-pending-docs-banner');
      if (docBanner) {
        if (d.pending_docs_count > 0) {
          docBanner.classList.remove('hidden');
          document.getElementById('stat-pending-docs-count').textContent = d.pending_docs_count;
        } else {
          docBanner.classList.add('hidden');
        }
      }

      // Render monthly trends table or simple bars
      const trendsContainer = document.getElementById('dash-trends-container');
      if (trendsContainer && d.monthly_trends) {
        trendsContainer.innerHTML = d.monthly_trends.map(t => `
          <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-900 border border-zinc-800 text-xs">
            <span class="font-bold text-white">${t.month}</span>
            <span class="text-zinc-400">${t.booking_count} Bookings</span>
            <span class="font-bold text-yellow-brand">₹${parseFloat(t.revenue).toLocaleString('en-IN')}</span>
          </div>
        `).join('');
      }

      // Recent Bookings Table
      const recentContainer = document.getElementById('dash-recent-bookings');
      if (recentContainer && d.recent_bookings) {
        recentContainer.innerHTML = d.recent_bookings.map(b => `
          <tr class="border-b border-zinc-800/80 hover:bg-zinc-800/30 text-xs">
            <td class="py-3 px-3 font-mono font-bold text-yellow-brand">${b.booking_code}</td>
            <td class="py-3 px-3 text-white font-semibold">${b.customer_name}<div class="text-zinc-500 text-[10px]">${b.customer_phone}</div></td>
            <td class="py-3 px-3 text-zinc-300">${b.vehicle_name}</td>
            <td class="py-3 px-3 text-zinc-400">${b.pickup_datetime.substring(0, 16)}</td>
            <td class="py-3 px-3"><span class="badge-status badge-${b.booking_status}">${b.booking_status.replace(/_/g, ' ')}</span></td>
            <td class="py-3 px-3 font-bold text-white">₹${b.total_amount}</td>
            <td class="py-3 px-3 text-right">
              <button onclick="AdminApp.openBookingModal(${b.id})" class="text-yellow-brand hover:underline font-bold">Manage</button>
            </td>
          </tr>
        `).join('');
      }

    } catch (e) {
      console.error('Admin dashboard load error:', e);
    }
  },

  /**
   * 2. Bookings Management Tab
   */
  async loadBookings() {
    try {
      const status = document.getElementById('admin-booking-filter-status')?.value || '';
      const search = document.getElementById('admin-booking-filter-search')?.value || '';

      const res = await API.get('/admin/bookings', { status, search });
      if (!res.success) return;

      this.bookings = res.data;
      const tbody = document.getElementById('admin-bookings-table-body');
      if (!tbody) return;

      if (this.bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-zinc-500">No bookings match criteria.</td></tr>';
        return;
      }

      tbody.innerHTML = this.bookings.map(b => `
        <tr class="border-b border-zinc-800/80 hover:bg-zinc-800/40 text-xs transition-colors">
          <td class="py-3.5 px-3 font-mono font-bold text-yellow-brand">${b.booking_code}</td>
          <td class="py-3.5 px-3">
            <div class="font-bold text-white">${b.customer_name}</div>
            <div class="text-zinc-400 text-[11px]"><i class="bi bi-telephone mr-1"></i>${b.customer_phone}</div>
          </td>
          <td class="py-3.5 px-3">
            <div class="text-white font-medium">${b.vehicle_name}</div>
            <div class="text-[11px] text-zinc-400">Assigned: <strong class="text-yellow-brand">${b.assigned_vehicle_reg || 'Unassigned'}</strong></div>
          </td>
          <td class="py-3.5 px-3 text-zinc-300">
            <div>Pick: ${b.pickup_datetime.substring(0, 16)}</div>
            <div class="text-zinc-500 text-[11px]">Ret: ${b.return_datetime.substring(0, 16)}</div>
          </td>
          <td class="py-3.5 px-3">
            <span class="badge-status badge-${b.booking_status}">${b.booking_status.replace(/_/g, ' ')}</span>
            <div class="mt-1"><span class="badge-status badge-${b.payment_status} text-[9px]">${b.payment_status}</span></div>
          </td>
          <td class="py-3.5 px-3 font-bold text-white">
            <div>₹${b.total_amount}</div>
            <div class="text-[10px] text-emerald-400">Paid: ₹${b.amount_paid}</div>
            <div class="text-[10px] text-yellow-brand">Bal: ₹${b.remaining_balance}</div>
          </td>
          <td class="py-3.5 px-3">
            <span class="badge-status badge-${b.document_verification_status} text-[10px]">${b.document_verification_status}</span>
          </td>
          <td class="py-3.5 px-3 text-right">
            <button onclick="AdminApp.openBookingModal(${b.id})" class="btn-rnr-secondary text-xs px-2.5 py-1.5 text-yellow-brand hover:border-yellow-brand">
              <i class="bi bi-pencil-square mr-1"></i> Action
            </button>
          </td>
        </tr>
      `).join('');

    } catch (e) {
      console.error('Bookings load error:', e);
    }
  },

  async openBookingModal(bookingId) {
    try {
      const res = await API.get(`/bookings/${bookingId}`);
      if (!res.success) return;

      const b = res.data;
      const modal = document.getElementById('admin-booking-modal');
      const body = document.getElementById('admin-booking-modal-body');

      const vehicleFormatted = `${b.vehicle_name || 'Vehicle'} (${b.category_name || 'Scooter'})`;
      const datesFormatted = this.formatBookingDatesForWhatsApp(b.pickup_datetime, b.return_datetime, b.total_days);
      const returnDateFormatted = this.formatReturnDateForWhatsApp(b.return_datetime);
      const paidAmountVal = (parseFloat(b.amount_paid) > 0) ? b.amount_paid : (b.rental_charge || b.total_amount);
      const depositVal = b.deposit_amount || 1000;

      body.innerHTML = `
        <div class="space-y-6 text-xs">
          <!-- Top Overview -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-sm font-black text-yellow-brand">${b.booking_code}</span>
                <span class="badge-status badge-${b.booking_status}">${b.booking_status.replace(/_/g, ' ')}</span>
                <span class="badge-status badge-${b.payment_status}">${b.payment_status}</span>
              </div>
              <h3 class="text-base font-extrabold text-white mt-1">${b.vehicle_name}</h3>
              <p class="text-zinc-400 text-xs">Customer: <strong class="text-white">${b.customer_name}</strong> (${b.customer_phone})</p>
            </div>
            <div class="text-right">
              <div class="text-sm font-black text-white">Total: ₹${b.total_amount}</div>
              <div class="text-emerald-400 font-bold">Paid: ₹${b.amount_paid}</div>
              <div class="text-yellow-brand font-bold">Remaining: ₹${b.remaining_balance}</div>
            </div>
          </div>

          <!-- Workflow Status Controls -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <h4 class="font-bold text-white text-sm mb-3"><i class="bi bi-sliders mr-1 text-yellow-brand"></i> Update Booking Status</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <select id="modal-status-select" class="rnr-input text-xs">
                <option value="pending_approval" ${b.booking_status === 'pending_approval' ? 'selected' : ''}>Pending Approval (New Request)</option>
                <option value="pending_booking" ${b.booking_status === 'pending_booking' ? 'selected' : ''}>Pending Booking</option>
                <option value="pending_payment" ${b.booking_status === 'pending_payment' ? 'selected' : ''}>Pending Payment</option>
                <option value="pending_document_verification" ${b.booking_status === 'pending_document_verification' ? 'selected' : ''}>Pending Document Verification</option>
                <option value="documents_approved" ${b.booking_status === 'documents_approved' ? 'selected' : ''}>Documents Approved</option>
                <option value="booking_confirmed" ${b.booking_status === 'booking_confirmed' ? 'selected' : ''}>Booking Confirmed</option>
                <option value="vehicle_assigned" ${b.booking_status === 'vehicle_assigned' ? 'selected' : ''}>Vehicle Assigned</option>
                <option value="ready_for_pickup" ${b.booking_status === 'ready_for_pickup' ? 'selected' : ''}>Ready for Pickup</option>
                <option value="rental_active" ${b.booking_status === 'rental_active' ? 'selected' : ''}>Rental Active</option>
                <option value="returned" ${b.booking_status === 'returned' ? 'selected' : ''}>Returned</option>
                <option value="completed" ${b.booking_status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="cancelled" ${b.booking_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                <option value="rejected" ${b.booking_status === 'rejected' ? 'selected' : ''}>Rejected</option>
              </select>
              <input type="text" id="modal-status-notes" placeholder="Internal or handover notes..." class="rnr-input text-xs">
              <button onclick="AdminApp.submitStatusUpdate(${b.id})" class="btn-rnr-primary text-xs font-bold py-2">Update Status</button>
            </div>
          </div>

          <!-- Vehicle Assignment Control -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <h4 class="font-bold text-white text-sm mb-3"><i class="bi bi-bicycle mr-1 text-yellow-brand"></i> Assign / Swap Physical Vehicle</h4>
            <div class="flex gap-3 items-center">
              <select id="modal-vehicle-assign-select" class="rnr-input text-xs flex-1">
                ${AdminApp.vehicles.map(v => `
                  <option value="${v.id}" ${b.assigned_vehicle_id == v.id ? 'selected' : ''}>
                    ${v.name} (${v.reg_number}) — Status: ${v.availability_status}
                  </option>
                `).join('')}
              </select>
              <button onclick="AdminApp.submitVehicleAssignment(${b.id})" class="btn-rnr-secondary text-xs px-4 py-2 text-yellow-brand hover:border-yellow-brand">Assign Vehicle</button>
            </div>
          </div>

          <!-- 💰 SECURITY DEPOSIT & FINANCIAL LEDGER CONTROL -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-yellow-brand/40 shadow-lg">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-bold text-white text-sm flex items-center gap-2">
                <i class="bi bi-shield-lock-fill text-yellow-brand text-base"></i> Security Deposit &amp; Booking Financials
              </h4>
              <span class="badge-status bg-yellow-brand/20 text-yellow-brand border border-yellow-brand/40 text-[10px]">Adjustable Deposit</span>
            </div>
            <p class="text-zinc-400 text-xs mb-3">Handle and edit the refundable security deposit for this booking (e.g. ₹2000.00). Total booking value and remaining balance will automatically recalculate.</p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-zinc-950 p-3 rounded-lg border border-zinc-800 text-[11px] mb-3">
              <div>
                <span class="text-zinc-400 block">Rental Charges:</span>
                <span class="font-bold text-white text-xs">₹${parseFloat(b.rental_charge || 0).toFixed(2)}</span>
              </div>
              <div>
                <span class="text-zinc-400 block">Security Deposit:</span>
                <span class="font-bold text-yellow-brand text-xs">₹${parseFloat(b.deposit_amount || 0).toFixed(2)}</span>
              </div>
              <div>
                <span class="text-zinc-400 block">Total Booking Value:</span>
                <span class="font-bold text-white text-xs">₹${parseFloat(b.total_amount || 0).toFixed(2)}</span>
              </div>
              <div>
                <span class="text-zinc-400 block">Remaining Balance:</span>
                <span class="font-bold text-emerald-400 text-xs">₹${parseFloat(b.remaining_balance || 0).toFixed(2)}</span>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-end gap-3">
              <div class="flex-1 w-full">
                <label class="block text-zinc-300 font-bold text-[11px] mb-1">
                  Adjust Security Deposit (₹)
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-2.5 text-zinc-400 font-bold">₹</span>
                  <input type="number" id="modal-booking-deposit-input" value="${b.deposit_amount}" step="50" min="0" placeholder="e.g. 2000.00" class="rnr-input font-bold text-sm text-yellow-brand pl-8">
                </div>
              </div>
              <button onclick="AdminApp.submitUpdateDeposit(${b.id})" class="btn-rnr-primary text-xs font-black py-2.5 px-6 whitespace-nowrap w-full sm:w-auto">
                <i class="bi bi-save-fill mr-1"></i> Save Security Deposit
              </button>
            </div>
          </div>

          <!-- 🛵 WHATSAPP BOOKING CONFIRMATION GENERATOR (SECTION 2) -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-emerald-500/50 shadow-lg">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <i class="bi bi-whatsapp text-emerald-400 text-lg"></i>
                <h4 class="font-bold text-white text-sm">WhatsApp Booking Confirmation Generator</h4>
              </div>
              <span class="badge-status bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px]">Official Template</span>
            </div>
            <p class="text-zinc-400 text-xs mb-3">After payment is done, generate and dispatch the official confirmation message for this customer with pre-filled fleet specifications.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 mb-3 text-[11px]">
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Customer Name</label>
                <input type="text" id="wa-gen-name" value="${b.customer_name}" class="rnr-input text-xs py-1.5" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Vehicle &amp; Category</label>
                <input type="text" id="wa-gen-vehicle" value="${vehicleFormatted}" class="rnr-input text-xs py-1.5" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Booking Dates &amp; Duration</label>
                <input type="text" id="wa-gen-dates" value="${datesFormatted}" class="rnr-input text-xs py-1.5" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Rental Amount Paid (₹)</label>
                <input type="number" id="wa-gen-paid" value="${paidAmountVal}" class="rnr-input text-xs py-1.5 font-bold text-emerald-400" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Security Deposit (₹)</label>
                <input type="number" id="wa-gen-deposit" value="${depositVal}" class="rnr-input text-xs py-1.5 font-bold text-yellow-brand" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div>
                <label class="block text-zinc-400 font-bold mb-0.5">Last Recorded Odometer</label>
                <input type="text" id="wa-gen-odometer" value="242 KM" class="rnr-input text-xs py-1.5" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
              <div class="md:col-span-3">
                <label class="block text-zinc-400 font-bold mb-0.5">Security Deposit Return Date</label>
                <input type="text" id="wa-gen-returndate" value="${returnDateFormatted}" class="rnr-input text-xs py-1.5" oninput="AdminApp.updateWhatsAppMessagePreview()">
              </div>
            </div>

            <!-- Preview Textarea -->
            <div class="mb-3">
              <div class="flex items-center justify-between mb-1">
                <label class="text-zinc-300 font-bold text-[11px] flex items-center gap-1">
                  <i class="bi bi-chat-left-text text-yellow-brand"></i> Generated Message Preview:
                </label>
                <span class="text-[10px] text-zinc-500 font-mono">Format matches user template</span>
              </div>
              <textarea id="wa-gen-preview" rows="13" class="rnr-input font-mono text-xs w-full bg-zinc-950 text-zinc-200 border-zinc-700 leading-relaxed p-3" readonly></textarea>
            </div>

            <div class="flex flex-wrap gap-2 justify-end">
              <button onclick="AdminApp.copyWhatsAppMessage()" class="btn-rnr-secondary text-xs px-4 py-2 border-zinc-700 text-white hover:border-yellow-brand">
                <i class="bi bi-clipboard-check mr-1.5 text-yellow-brand"></i> Copy Message
              </button>
              <button onclick="AdminApp.sendCustomerWhatsApp('${b.customer_phone}')" class="btn-rnr-primary bg-emerald-500 hover:bg-emerald-600 text-black font-extrabold text-xs px-4 py-2">
                <i class="bi bi-whatsapp mr-1.5"></i> Send to Customer (+91 ${b.customer_phone})
              </button>
            </div>
          </div>

          <!-- Quick Action: Attach Confirmed Rental Agreement -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <h4 class="font-bold text-white text-sm mb-2"><i class="bi bi-file-earmark-arrow-up mr-1 text-yellow-brand"></i> Deliver Rental Documents to Customer Account</h4>
            <p class="text-zinc-400 text-xs mb-3">Attach the official 14-clause vehicle rental agreement, RC, or insurance so it instantly appears in the customer's "My Rental Documents" tab.</p>
            <div class="flex flex-wrap gap-2">
              <button onclick="AdminApp.generateAndAttachAgreement(${b.id})" class="btn-rnr-primary text-xs px-3 py-2">
                <i class="bi bi-file-earmark-check mr-1"></i> Auto-Generate 14-Clause Rental Agreement
              </button>
              <button onclick="AdminApp.openAttachDocumentModal(${b.id})" class="btn-rnr-secondary text-xs px-3 py-2">
                <i class="bi bi-upload mr-1"></i> Upload Custom Document
              </button>
            </div>
          </div>

          <!-- Customer Uploaded Documents for this Booking -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <h4 class="font-bold text-white text-sm mb-3"><i class="bi bi-person-badge mr-1 text-yellow-brand"></i> Customer Verification Documents</h4>
            <div class="space-y-2">
              ${(b.documents || []).length > 0 ? b.documents.map(d => `
                <div class="p-3 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                  <div>
                    <div class="font-bold text-white">${d.title}</div>
                    <div class="text-[11px] text-zinc-400">Type: ${d.doc_type} • Status: <span class="badge-status badge-${d.verification_status} text-[9px]">${d.verification_status}</span></div>
                  </div>
                  <div class="flex items-center gap-2">
                    <a href="${API.getBaseUrl()}/documents/${d.id}/download" target="_blank" class="btn-rnr-secondary text-[11px] px-2 py-1">View</a>
                    <button onclick="AdminApp.verifyCustomerDoc(${d.id}, 'approved')" class="bg-emerald-950 text-emerald-400 border border-emerald-600 px-2 py-1 rounded text-[11px] font-bold hover:bg-emerald-900">Approve</button>
                    <button onclick="AdminApp.verifyCustomerDoc(${d.id}, 'rejected')" class="bg-red-950 text-red-400 border border-red-600 px-2 py-1 rounded text-[11px] font-bold hover:bg-red-900">Reject</button>
                  </div>
                </div>
              `).join('') : '<div class="text-zinc-500">No customer documents uploaded for this booking yet.</div>'}
            </div>
          </div>

          <!-- Financial Payment Records Ledger -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-bold text-white text-sm"><i class="bi bi-cash-stack mr-1 text-yellow-brand"></i> Payment Transactions Ledger</h4>
              <button onclick="AdminApp.openManualPaymentModal(${b.id}, ${b.remaining_balance})" class="btn-rnr-primary text-xs px-2.5 py-1">Record Payment</button>
            </div>
            <div class="space-y-2">
              ${(b.payments || []).length > 0 ? b.payments.map(p => `
                <div class="p-2.5 rounded bg-zinc-950 border border-zinc-800 flex items-center justify-between text-xs">
                  <div>
                    <span class="font-mono text-yellow-brand font-bold mr-2">${p.payment_code}</span>
                    <span class="text-white">${p.payment_type.toUpperCase()} via ${p.payment_method.toUpperCase()}</span>
                    <div class="text-zinc-500 text-[10px]">${p.payment_date} • Ref: ${p.transaction_reference || 'N/A'}</div>
                  </div>
                  <div class="text-right">
                    <span class="font-black text-emerald-400">₹${p.amount}</span>
                    <div class="text-[10px] text-zinc-400">${p.payment_status}</div>
                  </div>
                </div>
              `).join('') : '<div class="text-zinc-500">No payment transactions recorded yet.</div>'}
            </div>
          </div>
        </div>
      `;

      modal.classList.remove('hidden');
      this.updateWhatsAppMessagePreview();
    } catch (e) {
      API.toast('Failed to load booking details.', 'error');
    }
  },

  updateWhatsAppMessagePreview() {
    const name = document.getElementById('wa-gen-name')?.value || 'Customer';
    const vehicle = document.getElementById('wa-gen-vehicle')?.value || 'Vehicle';
    const dates = document.getElementById('wa-gen-dates')?.value || 'Upcoming Dates';
    const paid = document.getElementById('wa-gen-paid')?.value || '1500';
    const deposit = document.getElementById('wa-gen-deposit')?.value || '1000';
    const odometer = document.getElementById('wa-gen-odometer')?.value || '242 KM';
    const returnDate = document.getElementById('wa-gen-returndate')?.value || 'Scheduled Return Date';

    // Format matches demo template precisely
    const message = 
`🛵 ROAM & RIDE | BOOKING CONFIRMATION

Dear ${name},

Thank you for choosing Roam & Ride! Your two-wheeler booking has been confirmed.

Booking Details

* Vehicle: ${vehicle}
* Booking Dates: ${dates}
* Rental Amount Paid: ₹${paid}
* Security Deposit: ₹${deposit}
* Last Recorded Odometer: ${odometer}

Deposit & Documents


* Security Deposit: ₹${deposit}
* The security deposit will be returned on ${returnDate}, subject to the vehicle’s return and standard inspection.

Pickup & Drop-off Location
📍 https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic

Please ensure the vehicle is returned in good condition.

We appreciate your trust in us and wish you a safe and enjoyable ride!

Thank you for riding with Roam & Ride. 🏁

Roam & Ride | Your Journey, Your Freedom.`;

    const previewEl = document.getElementById('wa-gen-preview');
    if (previewEl) {
      previewEl.value = message;
    }
  },

  copyWhatsAppMessage() {
    const previewEl = document.getElementById('wa-gen-preview');
    if (!previewEl || !previewEl.value) {
      API.toast('No message to copy.', 'warning');
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(previewEl.value).then(() => {
        API.toast('WhatsApp confirmation message copied to clipboard!', 'success');
      }).catch(() => {
        previewEl.select();
        document.execCommand('copy');
        API.toast('WhatsApp confirmation message copied to clipboard!', 'success');
      });
    } else {
      previewEl.select();
      document.execCommand('copy');
      API.toast('WhatsApp confirmation message copied to clipboard!', 'success');
    }
  },

  sendCustomerWhatsApp(phone) {
    const previewEl = document.getElementById('wa-gen-preview');
    const msg = previewEl?.value || '';
    if (!msg) {
      API.toast('Please generate message first.', 'warning');
      return;
    }

    let cleanPhone = (phone || '').replace(/\D/g, '');
    if (cleanPhone.length === 10) {
      cleanPhone = '91' + cleanPhone;
    }
    if (!cleanPhone) {
      API.toast('Customer phone number not available.', 'error');
      return;
    }

    const url = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
  },

  formatBookingDatesForWhatsApp(pickupStr, returnStr, days) {
    if (!pickupStr) return 'Dates as agreed';
    try {
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      const p = new Date(pickupStr.replace(/-/g, '/'));
      const r = returnStr ? new Date(returnStr.replace(/-/g, '/')) : p;
      const pDay = p.getDate();
      const rDay = r.getDate();
      const pMonth = months[p.getMonth()];
      const rMonth = months[r.getMonth()];

      const durationStr = (days && parseInt(days) > 1) ? ` ( ${days} days )` : ' ( 1 day )';

      if (pMonth === rMonth) {
        if (pDay === rDay) {
          return `${pDay} ${pMonth}${durationStr}`;
        }
        return `${pDay} & ${rDay} ${pMonth}${durationStr}`;
      } else {
        return `${pDay} ${pMonth} - ${rDay} ${rMonth}${durationStr}`;
      }
    } catch (e) {
      return `${pickupStr} to ${returnStr || ''} (${days || 1} days)`;
    }
  },

  formatReturnDateForWhatsApp(returnStr) {
    if (!returnStr) return 'Vehicle Return Inspection';
    try {
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      const r = new Date(returnStr.replace(/-/g, '/'));
      return `${r.getDate()} ${months[r.getMonth()]}`;
    } catch (e) {
      return returnStr.split(' ')[0] || returnStr;
    }
  },

  closeBookingModal() {
    document.getElementById('admin-booking-modal')?.classList.add('hidden');
  },

  async submitStatusUpdate(bookingId) {
    const status = document.getElementById('modal-status-select')?.value;
    const notes = document.getElementById('modal-status-notes')?.value;

    try {
      const res = await API.put(`/admin/bookings/${bookingId}/status`, { status, notes });
      if (res.success) {
        API.toast(res.message, 'success');
        this.openBookingModal(bookingId);
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async submitVehicleAssignment(bookingId) {
    const vehicleId = document.getElementById('modal-vehicle-assign-select')?.value;

    try {
      const res = await API.post(`/admin/bookings/${bookingId}/assign-vehicle`, { vehicle_id: vehicleId });
      if (res.success) {
        API.toast(res.message, 'success');
        this.openBookingModal(bookingId);
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async submitUpdateDeposit(bookingId) {
    const input = document.getElementById('modal-booking-deposit-input');
    const depositAmount = parseFloat(input?.value);

    if (isNaN(depositAmount) || depositAmount < 0) {
      API.toast('Please enter a valid security deposit amount.', 'warning');
      return;
    }

    try {
      const res = await API.put(`/admin/bookings/${bookingId}/deposit`, {
        deposit_amount: depositAmount
      });

      if (res.success) {
        API.toast(res.message, 'success');
        await this.openBookingModal(bookingId);
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async generateAndAttachAgreement(bookingId) {
    try {
      const res = await API.post(`/admin/bookings/${bookingId}/documents`, {
        doc_type: 'rental_agreement',
        title: 'Official Vehicle Rental Agreement',
        is_customer_visible: 1
      });
      if (res.success) {
        API.toast('Official agreement generated and placed in customer account!', 'success');
        this.openBookingModal(bookingId);
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async verifyCustomerDoc(docId, status) {
    let reason = null;
    if (status === 'rejected') {
      reason = prompt('Enter rejection reason for customer (e.g. Expired, Unclear image):');
      if (!reason) return;
    }

    try {
      const res = await API.post(`/admin/documents/${docId}/verify`, { status, rejection_reason: reason });
      if (res.success) {
        API.toast(`Document marked as ${status}.`, 'success');
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  /**
   * 3. Vehicle Fleet Tab
   */
  async loadVehicles() {
    try {
      const res = await API.get('/vehicles');
      if (!res.success) return;

      this.vehicles = res.data;
      const tbody = document.getElementById('admin-vehicles-table-body');
      if (!tbody) return;

      tbody.innerHTML = this.vehicles.map(v => `
        <tr class="border-b border-zinc-800/80 hover:bg-zinc-800/40 text-xs">
          <td class="py-3 px-3">
            <img src="${v.primary_image}" class="w-14 h-10 object-cover rounded border border-zinc-800">
          </td>
          <td class="py-3 px-3 font-bold text-white">${v.name}</td>
          <td class="py-3 px-3 font-mono font-bold text-yellow-brand">${v.reg_number || 'N/A'}</td>
          <td class="py-3 px-3 text-zinc-300">${v.category_name}</td>
          <td class="py-3 px-3 font-bold text-white">₹${v.daily_rate}/day</td>
          <td class="py-3 px-3 font-bold text-yellow-brand">₹${parseFloat(v.deposit_amount || 1000).toFixed(2)}</td>
          <td class="py-3 px-3">
            <span class="badge-status badge-${v.availability_status}">${v.availability_status}</span>
          </td>
          <td class="py-3 px-3 text-zinc-400">${v.location_name || 'Central Hub'}</td>
          <td class="py-3 px-3 text-right">
            <button onclick="AdminApp.openEditVehicleModal(${v.id})" class="text-zinc-400 hover:text-yellow-brand mr-2"><i class="bi bi-pencil"></i></button>
            <button onclick="AdminApp.deleteVehicle(${v.id})" class="text-zinc-400 hover:text-red-400"><i class="bi bi-trash"></i></button>
          </td>
        </tr>
      `).join('');

    } catch (e) {
      console.error('Vehicles load error:', e);
    }
  },

  openAddVehicleModal() {
    document.getElementById('modal-add-vehicle')?.classList.remove('hidden');
  },

  closeAddVehicleModal() {
    document.getElementById('modal-add-vehicle')?.classList.add('hidden');
  },

  openEditVehicleModal(vehicleId) {
    const v = this.vehicles.find(item => item.id == vehicleId);
    if (!v) {
      API.toast('Vehicle not found.', 'error');
      return;
    }

    document.getElementById('edit-vehicle-id').value = v.id;
    document.getElementById('edit-veh-name').value = v.name || '';
    document.getElementById('edit-veh-reg').value = v.reg_number || '';
    document.getElementById('edit-veh-brand').value = v.brand || '';
    document.getElementById('edit-veh-model').value = v.model || '';
    document.getElementById('edit-veh-category').value = v.category_id || 1;
    document.getElementById('edit-veh-daily-rate').value = v.daily_rate || 0;
    document.getElementById('edit-veh-hourly-rate').value = v.hourly_rate || 0;
    document.getElementById('edit-veh-deposit').value = v.deposit_amount || 1000;
    document.getElementById('edit-veh-status').value = v.availability_status || 'available';
    document.getElementById('edit-veh-fuel').value = v.fuel_type || 'Petrol';
    document.getElementById('edit-veh-transmission').value = v.transmission || 'Automatic';
    document.getElementById('edit-veh-engine').value = v.engine_capacity || '';
    document.getElementById('edit-veh-mileage').value = v.mileage || '';
    document.getElementById('edit-veh-condition').value = v.condition_status || 'excellent';
    document.getElementById('edit-veh-location').value = v.pickup_location_id || 1;
    document.getElementById('edit-veh-featured').checked = (v.is_featured == 1);
    document.getElementById('edit-veh-notes').value = v.notes || '';

    const photoPreview = document.getElementById('edit-veh-photo-preview');
    if (photoPreview) {
      photoPreview.src = v.primary_image || 'assets/images/vehicles/activa6g.jpg';
    }

    document.getElementById('modal-edit-vehicle')?.classList.remove('hidden');
  },

  closeEditVehicleModal() {
    document.getElementById('modal-edit-vehicle')?.classList.add('hidden');
  },

  async submitEditVehicle(e) {
    e.preventDefault();
    const id = document.getElementById('edit-vehicle-id')?.value;
    if (!id) return;

    const form = document.getElementById('form-edit-vehicle');
    const fd = new FormData(form);
    const btn = document.getElementById('btn-save-vehicle-edit');

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Saving...';
    }

    try {
      const res = await API.request(`/admin/vehicles/${id}`, {
        method: 'POST',
        body: fd
      });

      if (res.success) {
        API.toast('Vehicle details updated successfully!', 'success');
        this.closeEditVehicleModal();
        await this.loadVehicles();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Save Changes <i class="bi bi-check2"></i>';
      }
    }
  },

  async submitAddVehicle(e) {
    e.preventDefault();
    const form = document.getElementById('form-add-vehicle');
    const fd = new FormData(form);

    try {
      const res = await API.request('/admin/vehicles', { method: 'POST', body: fd });
      if (res.success) {
        API.toast('Vehicle added to fleet successfully!', 'success');
        form.reset();
        this.closeAddVehicleModal();
        this.loadVehicles();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async deleteVehicle(vehicleId) {
    if (confirm('Decommission this vehicle from active fleet?')) {
      try {
        const res = await API.delete(`/admin/vehicles/${vehicleId}`);
        if (res.success) {
          API.toast(res.message, 'info');
          this.loadVehicles();
        }
      } catch (err) {
        API.toast(err.message, 'error');
      }
    }
  },

  /**
   * 4. Dedicated Payment Module (Section 13)
   */
  async loadPayments() {
    try {
      const res = await API.get('/admin/reports/revenue');
      if (res.success) {
        const container = document.getElementById('admin-revenue-breakdown');
        if (container) {
          container.innerHTML = res.data.map(p => `
            <tr class="border-b border-zinc-800/80 text-xs">
              <td class="py-3 px-3 font-semibold text-white">${p.payment_date}</td>
              <td class="py-3 px-3 text-yellow-brand font-bold uppercase">${p.payment_method}</td>
              <td class="py-3 px-3 text-zinc-300">${p.payment_type.replace(/_/g, ' ')}</td>
              <td class="py-3 px-3 text-zinc-400">${p.transactions_count}</td>
              <td class="py-3 px-3 font-bold text-emerald-400 text-right">₹${parseFloat(p.total_amount).toLocaleString('en-IN')}</td>
            </tr>
          `).join('');
        }
      }
    } catch (e) {
      console.error('Payments load error:', e);
    }
  },

  openManualPaymentModal(bookingId, remainingBal = 0) {
    document.getElementById('manual-pay-booking-id').value = bookingId;
    document.getElementById('manual-pay-amount').value = remainingBal > 0 ? remainingBal : 500;
    document.getElementById('modal-manual-payment')?.classList.remove('hidden');
  },

  closeManualPaymentModal() {
    document.getElementById('modal-manual-payment')?.classList.add('hidden');
  },

  /**
   * 4b. Manual Booking Creation (Counter / Walk-in / Phone)
   */
  async openManualBookingModal() {
    // If vehicles aren't loaded yet, fetch them
    if (!this.vehicles || this.vehicles.length === 0) {
      await this.loadVehicles();
    }

    const vehSelect = document.getElementById('manual-book-vehicle');
    if (vehSelect && this.vehicles) {
      vehSelect.innerHTML = '<option value="">-- Choose scooter or motorcycle from fleet --</option>' +
        this.vehicles.map(v => `
          <option value="${v.id}" data-rate="${v.daily_rate}" data-deposit="${v.deposit_amount || 1000}" data-image="${v.primary_image || ''}">
            ${v.name} (${v.reg_number}) — ₹${parseFloat(v.daily_rate).toFixed(0)}/day [Deposit: ₹${parseFloat(v.deposit_amount || 1000).toFixed(0)}]
          </option>
        `).join('');
    }

    // Set default pickup (now + 30m) and return (tomorrow + 30m)
    const now = new Date();
    now.setMinutes(now.getMinutes() + 30);
    const tomorrow = new Date(now.getTime() + 24 * 3600 * 1000);

    const pad = (n) => String(n).padStart(2, '0');
    const formatDt = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

    const pickupInput = document.getElementById('manual-book-pickup');
    const returnInput = document.getElementById('manual-book-return');
    if (pickupInput && (!pickupInput.value || pickupInput.value === '')) pickupInput.value = formatDt(now);
    if (returnInput && (!returnInput.value || returnInput.value === '')) returnInput.value = formatDt(tomorrow);

    this.calculateManualBookingTotals();
    document.getElementById('modal-manual-booking')?.classList.remove('hidden');
  },

  closeManualBookingModal() {
    document.getElementById('modal-manual-booking')?.classList.add('hidden');
  },

  onManualVehicleChange() {
    const sel = document.getElementById('manual-book-vehicle');
    const opt = sel?.options[sel.selectedIndex];
    if (opt && opt.value) {
      const rate = parseFloat(opt.dataset.rate || 0);
      const deposit = parseFloat(opt.dataset.deposit || 1000);
      document.getElementById('manual-book-rate').value = rate;
      document.getElementById('manual-book-deposit').value = deposit;
    }
    this.calculateManualBookingTotals();
  },

  calculateManualBookingTotals(skipRentalCalc = false) {
    const pickupVal = document.getElementById('manual-book-pickup')?.value;
    const returnVal = document.getElementById('manual-book-return')?.value;

    let hours = 24;
    let days = 1;

    if (pickupVal && returnVal) {
      const p = new Date(pickupVal);
      const r = new Date(returnVal);
      const diffMs = r - p;
      if (diffMs > 0) {
        hours = Math.max(1, Math.ceil(diffMs / (1000 * 3600)));
        days = Math.max(1, Math.ceil(hours / 24));
      }
    }

    const daysLbl = document.getElementById('manual-book-days-label');
    const hoursLbl = document.getElementById('manual-book-hours-label');
    if (daysLbl) daysLbl.innerText = `${days} Day(s)`;
    if (hoursLbl) hoursLbl.innerText = `${hours}`;

    const dailyRate = parseFloat(document.getElementById('manual-book-rate')?.value || 0);
    const rentalInput = document.getElementById('manual-book-rental');

    if (!skipRentalCalc && rentalInput) {
      rentalInput.value = (days * dailyRate).toFixed(2);
    }

    const rental = parseFloat(rentalInput?.value || 0);
    const discount = parseFloat(document.getElementById('manual-book-discount')?.value || 0);
    const deposit = parseFloat(document.getElementById('manual-book-deposit')?.value || 0);

    const taxable = Math.max(0, rental - discount);
    const tax = taxable * 0.05; // 5% GST
    const total = taxable + tax + deposit;

    const taxLbl = document.getElementById('manual-book-tax-label');
    const totalLbl = document.getElementById('manual-book-total-label');
    if (taxLbl) taxLbl.innerText = `₹${tax.toFixed(2)}`;
    if (totalLbl) totalLbl.innerText = `₹${total.toFixed(2)}`;
  },

  async submitManualBooking(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-manual-booking');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Creating Reservation...';
    }

    try {
      const payload = {
        customer_name: document.getElementById('manual-book-cust-name').value.trim(),
        customer_phone: document.getElementById('manual-book-cust-phone').value.trim(),
        customer_email: document.getElementById('manual-book-cust-email').value.trim() || null,
        vehicle_id: document.getElementById('manual-book-vehicle').value,
        pickup_datetime: document.getElementById('manual-book-pickup').value,
        return_datetime: document.getElementById('manual-book-return').value,
        pickup_location_id: document.getElementById('manual-book-pickup-loc').value,
        return_location_id: document.getElementById('manual-book-return-loc').value,
        daily_rate: document.getElementById('manual-book-rate').value,
        rental_charge: document.getElementById('manual-book-rental').value,
        deposit_amount: document.getElementById('manual-book-deposit').value,
        discount_amount: document.getElementById('manual-book-discount').value,
        amount_paid_now: document.getElementById('manual-book-paid-now').value,
        payment_method: document.getElementById('manual-book-pay-method').value,
        payment_reference: document.getElementById('manual-book-pay-ref').value.trim(),
        booking_status: document.getElementById('manual-book-status').value,
        internal_notes: document.getElementById('manual-book-notes').value.trim()
      };

      const res = await API.post('/admin/bookings/manual', payload);
      if (res.success) {
        API.toast(`Manual booking #${res.data.booking_code} created successfully!`, 'success');
        document.getElementById('form-manual-booking')?.reset();
        this.closeManualBookingModal();
        await this.loadBookings();
        await this.loadDashboardData();
        if (res.data.booking_id) {
          this.openBookingModal(res.data.booking_id);
        }
      }
    } catch (err) {
      API.toast(err.message, 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Create &amp; Confirm Booking';
      }
    }
  },

  async submitManualPayment(e) {
    e.preventDefault();
    const bookingId = document.getElementById('manual-pay-booking-id').value;
    const amount = document.getElementById('manual-pay-amount').value;
    const method = document.getElementById('manual-pay-method').value;
    const type = document.getElementById('manual-pay-type').value;
    const notes = document.getElementById('manual-pay-notes').value;

    try {
      const res = await API.post('/admin/payments/manual', {
        booking_id: bookingId,
        amount,
        payment_method: method,
        payment_type: type,
        notes
      });

      if (res.success) {
        API.toast('Payment recorded in immutable ledger!', 'success');
        this.closeManualPaymentModal();
        this.openBookingModal(bookingId);
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  /**
   * 5. Handover & Return Checklists (Section 16)
   */
  loadHandoverModule() {
    // Populate bookings ready for pickup or active for return
    const pickSel = document.getElementById('ho-booking-select');
    if (pickSel && this.bookings) {
      pickSel.innerHTML = '<option value="">Select Booking...</option>' + 
        this.bookings.map(b => `
          <option value="${b.id}">${b.booking_code} — ${b.customer_name} (${b.vehicle_name}) [Status: ${b.booking_status}]</option>
        `).join('');
    }
  },

  async submitHandoverChecklist(e) {
    e.preventDefault();
    const bookingId = document.getElementById('ho-booking-select')?.value;
    const type = document.getElementById('ho-type')?.value;
    const odo = document.getElementById('ho-odo')?.value;
    const fuel = document.getElementById('ho-fuel')?.value;
    const notes = document.getElementById('ho-notes')?.value;
    const cleanFee = document.getElementById('ho-clean-fee')?.value || 0;
    const fuelFee = document.getElementById('ho-fuel-fee')?.value || 0;

    if (!bookingId || !odo) {
      API.toast('Please select a booking and enter odometer reading.', 'warning');
      return;
    }

    try {
      const res = await API.post('/admin/handover', {
        booking_id: bookingId,
        handover_type: type,
        odometer_reading: odo,
        fuel_level: fuel,
        condition_notes: notes,
        cleaning_charges: cleanFee,
        fuel_charges: fuelFee
      });

      if (res.success) {
        API.toast(res.message, 'success');
        document.getElementById('form-handover-checklist')?.reset();
        this.loadBookings();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  /**
   * 6. Reports Module
   */
  async loadReportsModule() {
    try {
      const [vRep, bRep] = await Promise.all([
        API.get('/admin/reports/vehicles'),
        API.get('/admin/reports/bookings')
      ]);

      if (vRep.success) {
        const vContainer = document.getElementById('rep-vehicles-body');
        if (vContainer) {
          vContainer.innerHTML = vRep.data.map(r => `
            <tr class="border-b border-zinc-800 text-xs">
              <td class="py-2.5 px-3 font-bold text-white">${r.name}</td>
              <td class="py-2.5 px-3 font-mono text-yellow-brand">${r.reg_number}</td>
              <td class="py-2.5 px-3 text-zinc-400">${r.total_trips} trips</td>
              <td class="py-2.5 px-3 font-bold text-emerald-400 text-right">₹${parseFloat(r.total_earnings).toLocaleString('en-IN')}</td>
            </tr>
          `).join('');
        }
      }
    } catch (e) {
      console.error('Reports load error:', e);
    }
  },

  exportReport(type) {
    const url = `${API.getBaseUrl()}/admin/reports/${type}?export=csv`;
    window.open(url, '_blank');
  },

  /**
   * 7. Support Inbox
   */
  async loadSupportModule() {
    try {
      const res = await API.get('/support/tickets');
      if (!res.success) return;

      const container = document.getElementById('admin-tickets-list');
      if (!container) return;

      if (res.data.length === 0) {
        container.innerHTML = '<div class="p-8 text-center text-zinc-500">No support tickets found.</div>';
        return;
      }

      container.innerHTML = res.data.map(t => `
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 text-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="font-mono font-bold text-yellow-brand">${t.ticket_code}</span>
            <span class="badge-status badge-${t.status}">${t.status}</span>
          </div>
          <h4 class="font-bold text-white text-sm mb-1">${t.subject}</h4>
          <p class="text-zinc-300 mb-2">${t.message}</p>
          <div class="text-zinc-500 text-[11px] mb-3">
            From: <strong>${t.customer_name}</strong> (${t.customer_phone}) • Category: ${t.category}
          </div>
          ${t.admin_reply ? `
            <div class="p-2.5 rounded bg-zinc-950 border border-zinc-800/80 text-emerald-400 mb-3">
              <strong>Admin Response:</strong> ${t.admin_reply}
            </div>
          ` : `
            <div class="flex gap-2">
              <input type="text" id="ticket-reply-${t.id}" placeholder="Type reply to customer..." class="rnr-input text-xs">
              <button onclick="AdminApp.submitTicketReply(${t.id})" class="btn-rnr-primary text-xs px-3">Reply</button>
            </div>
          `}
        </div>
      `).join('');

    } catch (e) {
      console.error('Support tickets load error:', e);
    }
  },

  async submitTicketReply(ticketId) {
    const replyInput = document.getElementById(`ticket-reply-${ticketId}`);
    const reply = replyInput?.value.trim();
    if (!reply) return;

    try {
      const res = await API.put(`/admin/support/tickets/${ticketId}/reply`, { admin_reply: reply, status: 'resolved' });
      if (res.success) {
        API.toast('Reply sent to customer!', 'success');
        this.loadSupportModule();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  /**
   * 8. Settings Module
   */
  async loadSettingsModule() {
    try {
      await this.loadHeroSettings();

      const res = await API.get('/admin/settings');
      if (!res.success) return;

      const settings = res.data;
      if (settings.general) {
        document.getElementById('set-site-name').value = settings.general.site_name || '';
        document.getElementById('set-site-phone').value = settings.general.site_phone || '';
        document.getElementById('set-site-email').value = settings.general.site_email || '';
      }
      if (settings.booking) {
        document.getElementById('set-advance-pct').value = settings.booking.advance_percentage || '25';
        document.getElementById('set-pay-later-enabled').value = settings.booking.pay_later_enabled || '1';
      }
    } catch (e) {
      console.error('Settings load error:', e);
    }
  },

  async saveSettings(e) {
    e.preventDefault();
    const data = {
      site_name: document.getElementById('set-site-name').value,
      site_phone: document.getElementById('set-site-phone').value,
      site_email: document.getElementById('set-site-email').value,
      advance_percentage: document.getElementById('set-advance-pct').value,
      pay_later_enabled: document.getElementById('set-pay-later-enabled').value,
    };

    try {
      const res = await API.post('/admin/settings', data);
      if (res.success) {
        API.toast('Settings saved successfully.', 'success');
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  /**
   * 8b. Homepage Hero Picture & Content Manager
   */
  async loadHeroSettings() {
    try {
      // If vehicles aren't loaded yet, load them
      if (!this.vehicles || this.vehicles.length === 0) {
        await this.loadVehicles();
      }

      // Populate hero vehicle picker dropdown
      const picker = document.getElementById('hero-vehicle-picker');
      if (picker && this.vehicles) {
        picker.innerHTML = '<option value="">-- Choose from fleet to autofill image & info --</option>' +
          this.vehicles.map(v => `
            <option value="${v.id}" data-name="${v.name}" data-rate="${v.daily_rate}" data-image="${v.primary_image || ''}">
              ${v.name} (${v.reg_number}) — ₹${parseFloat(v.daily_rate).toFixed(0)}/day
            </option>
          `).join('');
      }

      const res = await API.get('/settings');
      if (res.success && res.data) {
        const s = res.data;
        const img = s.hero_image || 'assets/images/vehicles/activa6g.jpg';
        const badge = s.hero_badge || 'BESTSELLER';
        const title = s.hero_title || 'Honda Activa 6G Sport';
        const subtitle = s.hero_subtitle || 'From ₹499 / day (₹49/hr)';
        const tagLeft = s.hero_tagline_left || 'Kamarkuchi Hub (Sonapur)';
        const tagRight = s.hero_tagline_right || 'Instant 5-Min Handover';

        const prevImg = document.getElementById('hero-preview-img');
        const prevBadge = document.getElementById('hero-preview-badge');
        const prevTitle = document.getElementById('hero-preview-title');
        const prevSub = document.getElementById('hero-preview-subtitle');
        const prevTagL = document.getElementById('hero-preview-tagleft');
        const prevTagR = document.getElementById('hero-preview-tagright');

        if (prevImg) prevImg.src = img;
        if (prevBadge) prevBadge.innerText = badge;
        if (prevTitle) prevTitle.innerText = title;
        if (prevSub) prevSub.innerText = subtitle;
        if (prevTagL) prevTagL.innerText = tagLeft;
        if (prevTagR) prevTagR.innerText = tagRight;

        const setBadge = document.getElementById('hero-set-badge');
        const setTitle = document.getElementById('hero-set-title');
        const setSub = document.getElementById('hero-set-subtitle');
        const setTagL = document.getElementById('hero-set-tagleft');
        const setTagR = document.getElementById('hero-set-tagright');
        const setPath = document.getElementById('hero-image-existing-path');

        if (setBadge) setBadge.value = badge;
        if (setTitle) setTitle.value = title;
        if (setSub) setSub.value = subtitle;
        if (setTagL) setTagL.value = tagLeft;
        if (setTagR) setTagR.value = tagRight;
        if (setPath) setPath.value = img;
      }
    } catch (e) {
      console.error('Error loading hero settings:', e);
    }
  },

  onHeroFileSelected(input) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = (e) => {
        const prevImg = document.getElementById('hero-preview-img');
        if (prevImg) prevImg.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  },

  onHeroVehicleSelected(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
      const name = opt.dataset.name;
      const rate = opt.dataset.rate;
      const img = opt.dataset.image;

      if (img) {
        const prevImg = document.getElementById('hero-preview-img');
        if (prevImg) prevImg.src = img;
        const setPath = document.getElementById('hero-image-existing-path');
        if (setPath) setPath.value = img;
      }
      if (name) {
        const setTitle = document.getElementById('hero-set-title');
        if (setTitle) setTitle.value = name;
        const prevTitle = document.getElementById('hero-preview-title');
        if (prevTitle) prevTitle.innerText = name;
      }
      if (rate) {
        const sub = `From ₹${parseFloat(rate).toFixed(0)} / day`;
        const setSub = document.getElementById('hero-set-subtitle');
        if (setSub) setSub.value = sub;
        const prevSub = document.getElementById('hero-preview-subtitle');
        if (prevSub) prevSub.innerText = sub;
      }
    }
  },

  resetHeroDefaults() {
    const defaults = {
      img: 'assets/images/vehicles/activa6g.jpg',
      badge: 'BESTSELLER',
      title: 'Honda Activa 6G Sport',
      subtitle: 'From ₹499 / day (₹49/hr)',
      tagLeft: 'Kamarkuchi Hub (Sonapur)',
      tagRight: 'Instant 5-Min Handover'
    };

    const prevImg = document.getElementById('hero-preview-img');
    const prevBadge = document.getElementById('hero-preview-badge');
    const prevTitle = document.getElementById('hero-preview-title');
    const prevSub = document.getElementById('hero-preview-subtitle');
    const prevTagL = document.getElementById('hero-preview-tagleft');
    const prevTagR = document.getElementById('hero-preview-tagright');

    if (prevImg) prevImg.src = defaults.img;
    if (prevBadge) prevBadge.innerText = defaults.badge;
    if (prevTitle) prevTitle.innerText = defaults.title;
    if (prevSub) prevSub.innerText = defaults.subtitle;
    if (prevTagL) prevTagL.innerText = defaults.tagLeft;
    if (prevTagR) prevTagR.innerText = defaults.tagRight;

    document.getElementById('hero-set-badge').value = defaults.badge;
    document.getElementById('hero-set-title').value = defaults.title;
    document.getElementById('hero-set-subtitle').value = defaults.subtitle;
    document.getElementById('hero-set-tagleft').value = defaults.tagLeft;
    document.getElementById('hero-set-tagright').value = defaults.tagRight;
    document.getElementById('hero-image-existing-path').value = defaults.img;
    const fileInp = document.getElementById('hero-image-file');
    if (fileInp) fileInp.value = '';
  },

  async saveHeroSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-hero-settings');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Saving Hero Content...';
    }

    try {
      const fd = new FormData();
      const fileInput = document.getElementById('hero-image-file');
      if (fileInput && fileInput.files[0]) {
        fd.append('hero_image_file', fileInput.files[0]);
      } else {
        const existingPath = document.getElementById('hero-image-existing-path')?.value;
        if (existingPath) {
          fd.append('hero_image', existingPath);
        }
      }

      fd.append('hero_badge', document.getElementById('hero-set-badge')?.value || 'BESTSELLER');
      fd.append('hero_title', document.getElementById('hero-set-title')?.value || 'Honda Activa 6G Sport');
      fd.append('hero_subtitle', document.getElementById('hero-set-subtitle')?.value || 'From ₹499 / day (₹49/hr)');
      fd.append('hero_tagline_left', document.getElementById('hero-set-tagleft')?.value || 'Kamarkuchi Hub (Sonapur)');
      fd.append('hero_tagline_right', document.getElementById('hero-set-tagright')?.value || 'Instant 5-Min Handover');

      const res = await API.request('/admin/settings/hero', {
        method: 'POST',
        body: fd
      });

      if (res.success) {
        API.toast('Homepage hero picture & content updated successfully!', 'success');
        if (res.data?.hero_image) {
          const prevImg = document.getElementById('hero-preview-img');
          if (prevImg) prevImg.src = res.data.hero_image;
          const setPath = document.getElementById('hero-image-existing-path');
          if (setPath) setPath.value = res.data.hero_image;
        }
      }
    } catch (err) {
      API.toast(err.message, 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill"></i> Save & Apply Homepage Picture';
      }
    }
  },

  /**
   * 9. Audit Logs Tab
   */
  async loadAuditLogs() {
    try {
      const res = await API.get('/admin/audit-logs');
      if (!res.success) return;

      const container = document.getElementById('admin-audit-logs-body');
      if (!container) return;

      container.innerHTML = res.data.map(l => `
        <tr class="border-b border-zinc-800 text-xs">
          <td class="py-2.5 px-3 text-zinc-400">${l.created_at}</td>
          <td class="py-2.5 px-3 font-bold text-white">${l.user_name || 'System'}<span class="text-zinc-500 text-[10px] ml-1">(${l.user_role || 'sys'})</span></td>
          <td class="py-2.5 px-3 font-mono font-bold text-yellow-brand">${l.action}</td>
          <td class="py-2.5 px-3 text-zinc-300">${l.entity_type} #${l.entity_id || '-'}</td>
          <td class="py-2.5 px-3 text-zinc-500 truncate max-w-xs">${l.new_values || l.old_values || '-'}</td>
          <td class="py-2.5 px-3 text-zinc-400 font-mono text-[10px]">${l.ip_address}</td>
        </tr>
      `).join('');
    } catch (e) {
      console.error('Audit logs load error:', e);
    }
  }
};

window.AdminApp = AdminApp;
document.addEventListener('DOMContentLoaded', () => AdminApp.init());
