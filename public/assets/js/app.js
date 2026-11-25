/**
 * Rome & Ride — Customer Website Main Application
 */

const App = {
  vehicles: [],
  categories: [],
  locations: [],
  activeFilter: 'all',

  async init() {
    this.registerServiceWorker();
    this.setupPWAInstall();
    await this.loadInitialData();
    this.bindEvents();
    this.handleRoute();
  },

  async loadInitialData() {
    try {
      const [vRes, cRes, lRes, sRes] = await Promise.all([
        API.get('/vehicles'),
        API.get('/categories'),
        API.get('/locations'),
        API.get('/settings')
      ]);

      if (vRes.success) {
        this.vehicles = vRes.data;
        window.AppVehicles = this.vehicles;
        this.renderVehicles(this.vehicles);
        this.renderFeaturedVehicles(this.vehicles.filter(v => v.is_featured == 1));
      }

      if (cRes.success) {
        this.categories = cRes.data;
        this.renderCategoryPills(this.categories);
        this.populateCategoryDropdowns(this.categories);
      }

      if (lRes.success) {
        this.locations = lRes.data;
        this.populateLocationDropdowns(this.locations);
      }

      if (sRes && sRes.success && sRes.data) {
        const s = sRes.data;
        const heroImg = document.getElementById('home-hero-img');
        const heroBadge = document.getElementById('home-hero-badge');
        const heroTitle = document.getElementById('home-hero-title');
        const heroSub = document.getElementById('home-hero-subtitle');
        const heroTagL = document.getElementById('home-hero-tagleft');
        const heroTagR = document.getElementById('home-hero-tagright');

        if (heroImg && s.hero_image) heroImg.src = s.hero_image;
        if (heroBadge && s.hero_badge) heroBadge.textContent = s.hero_badge;
        if (heroTitle && s.hero_title) heroTitle.textContent = s.hero_title;
        if (heroSub && s.hero_subtitle) heroSub.textContent = s.hero_subtitle;
        if (heroTagL && s.hero_tagline_left) heroTagL.innerHTML = `<i class="bi bi-geo-alt-fill text-yellow-brand mr-1"></i>${s.hero_tagline_left}`;
        if (heroTagR && s.hero_tagline_right) heroTagR.innerHTML = `<i class="bi bi-clock-history text-yellow-brand mr-1"></i>${s.hero_tagline_right}`;
      }
    } catch (e) {
      console.error('Initial data load error:', e);
    }
  },

  renderVehicles(vehicles) {
    const grid = document.getElementById('vehicles-grid');
    if (!grid) return;

    if (vehicles.length === 0) {
      grid.innerHTML = `
        <div class="col-span-full py-16 text-center">
          <div class="w-16 h-16 rounded-full bg-zinc-800 text-yellow-brand flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="bi bi-search"></i>
          </div>
          <h3 class="text-xl font-bold text-white mb-2">No Vehicles Match Your Search</h3>
          <p class="text-sm text-zinc-400 mb-6">Try clearing filters or adjusting your price and dates.</p>
          <button onclick="App.resetFilters()" class="btn-rnr-secondary text-sm">Reset All Filters</button>
        </div>
      `;
      return;
    }

    grid.innerHTML = vehicles.map(v => `
      <div class="rnr-card overflow-hidden flex flex-col group">
        <!-- Vehicle Image & Badges -->
        <div class="relative bg-zinc-950 aspect-[16/10] overflow-hidden flex items-center justify-center border-b border-zinc-800">
          <img src="${v.primary_image}" alt="${v.name}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
          <div class="absolute top-3 left-3 flex gap-2">
            <span class="badge-status badge-confirmed text-[10px]"><i class="bi bi-lightning-charge-fill"></i> ${v.category_name}</span>
            ${v.is_featured == 1 ? '<span class="badge-status bg-yellow-brand text-black font-extrabold text-[10px]">FEATURED</span>' : ''}
          </div>
          <div class="absolute bottom-3 right-3 bg-black/85 backdrop-blur-md px-2.5 py-1 rounded text-xs font-semibold text-zinc-300 border border-zinc-800">
            <i class="bi bi-geo-alt-fill text-yellow-brand mr-1"></i>${v.location_city || 'Guwahati'}
          </div>
        </div>

        <!-- Details -->
        <div class="p-5 flex-1 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold text-yellow-brand uppercase tracking-wider mb-1">${v.brand}</div>
            <h3 class="font-extrabold text-white text-lg group-hover:text-yellow-brand transition-colors">${v.name}</h3>
            
            <!-- Specs Grid -->
            <div class="grid grid-cols-3 gap-2 my-4 py-3 border-y border-zinc-800/80 text-xs text-zinc-400">
              <div class="flex items-center gap-1.5 truncate">
                <i class="bi bi-speedometer2 text-yellow-brand"></i>
                <span>${v.engine_capacity}</span>
              </div>
              <div class="flex items-center gap-1.5 truncate">
                <i class="bi bi-gear-fill text-yellow-brand"></i>
                <span>${v.transmission}</span>
              </div>
              <div class="flex items-center gap-1.5 truncate">
                <i class="bi bi-fuel-pump-fill text-yellow-brand"></i>
                <span>${v.fuel_type}</span>
              </div>
            </div>

            <!-- Features list -->
            <div class="flex flex-wrap gap-2 mb-4 text-[11px] text-zinc-400">
              <span class="bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded"><i class="bi bi-shield-check text-emerald-400 mr-1"></i>Helmet x${v.helmet_included}</span>
              <span class="bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded"><i class="bi bi-ev-front text-yellow-brand mr-1"></i>${v.mileage}</span>
              <span class="bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded"><i class="bi bi-wallet2 text-zinc-400 mr-1"></i>Dep. ₹${v.deposit_amount}</span>
            </div>
          </div>

          <!-- Price & CTA -->
          <div class="pt-3 border-t border-zinc-800/60 flex items-center justify-between gap-3">
            <div>
              <div class="text-2xl font-black text-white">₹${v.daily_rate}<span class="text-xs font-normal text-zinc-400"> /day</span></div>
              ${v.hourly_rate > 0 ? `<div class="text-[11px] text-zinc-400">or ₹${v.hourly_rate}/hr</div>` : ''}
            </div>
            <div class="flex gap-2">
              <button onclick="App.openVehicleDetails(${v.id})" class="btn-rnr-secondary text-xs px-3 py-2">Details</button>
              <button onclick="BookingWizard.open(App.vehicles.find(item=>item.id==${v.id}))" class="btn-rnr-primary text-xs px-4 py-2">Ride Now</button>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  },

  renderFeaturedVehicles(featured) {
    const container = document.getElementById('featured-scooters-list');
    if (!container) return;

    container.innerHTML = featured.slice(0, 4).map(v => `
      <div class="rnr-card p-4 hover:border-yellow-brand transition-all flex flex-col justify-between">
        <div class="relative rounded-lg overflow-hidden bg-zinc-950 mb-4 border border-zinc-800 aspect-[16/10]">
          <img src="${v.primary_image}" alt="${v.name}" class="w-full h-full object-cover">
          <span class="absolute top-2 left-2 badge-status bg-yellow-brand text-black font-extrabold text-[9px]">POPULAR</span>
        </div>
        <div>
          <div class="text-[11px] font-bold text-yellow-brand uppercase">${v.brand}</div>
          <h4 class="font-bold text-white text-base truncate mb-1">${v.name}</h4>
          <p class="text-xs text-zinc-400 mb-3">${v.engine_capacity} • ${v.fuel_type} • ${v.transmission}</p>
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-zinc-800">
          <div>
            <span class="text-xl font-black text-white">₹${v.daily_rate}</span>
            <span class="text-xs text-zinc-400">/day</span>
          </div>
          <button onclick="BookingWizard.open(App.vehicles.find(item=>item.id==${v.id}))" class="btn-rnr-primary text-xs px-3 py-1.5">Book</button>
        </div>
      </div>
    `).join('');
  },

  renderCategoryPills(categories) {
    const container = document.getElementById('category-filter-pills');
    if (!container) return;

    let html = `
      <button onclick="App.filterByCategory('all', this)" class="category-pill px-4 py-2 rounded-full text-xs font-bold transition-all bg-yellow-brand text-black shadow-md whitespace-nowrap">
        All Rides (${this.vehicles.length})
      </button>
    `;

    categories.forEach(cat => {
      html += `
        <button onclick="App.filterByCategory('${cat.slug}', this)" class="category-pill px-4 py-2 rounded-full text-xs font-bold transition-all bg-zinc-900 border border-zinc-800 text-zinc-300 hover:border-yellow-brand whitespace-nowrap flex items-center gap-1.5">
          <i class="bi ${cat.icon || 'bi-scooter'} text-yellow-brand"></i>
          ${cat.name} (${cat.vehicle_count || 0})
        </button>
      `;
    });

    container.innerHTML = html;
  },

  populateCategoryDropdowns(categories) {
    const sel = document.getElementById('search-category-select');
    if (sel) {
      categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.slug;
        opt.textContent = c.name;
        sel.appendChild(opt);
      });
    }
  },

  populateLocationDropdowns(locations) {
    const pickSel = document.getElementById('search-pickup-loc');
    const bwPickSel = document.getElementById('bw-pickup-loc');
    const bwRetSel = document.getElementById('bw-return-loc');

    locations.forEach(loc => {
      [pickSel, bwPickSel, bwRetSel].forEach(s => {
        if (s) {
          const opt = document.createElement('option');
          opt.value = loc.id;
          opt.textContent = `${loc.name} (${loc.city})`;
          s.appendChild(opt);
        }
      });
    });
  },

  filterByCategory(slug, btnElement) {
    this.activeFilter = slug;

    // Update button states
    document.querySelectorAll('.category-pill').forEach(btn => {
      btn.className = 'category-pill px-4 py-2 rounded-full text-xs font-bold transition-all bg-zinc-900 border border-zinc-800 text-zinc-300 hover:border-yellow-brand whitespace-nowrap';
    });
    if (btnElement) {
      btnElement.className = 'category-pill px-4 py-2 rounded-full text-xs font-bold transition-all bg-yellow-brand text-black shadow-md whitespace-nowrap';
    }

    if (slug === 'all') {
      this.renderVehicles(this.vehicles);
    } else {
      const filtered = this.vehicles.filter(v => v.category_slug === slug);
      this.renderVehicles(filtered);
    }
  },

  applySearchAndFilters() {
    const searchTerm = document.getElementById('filter-search')?.value.toLowerCase().trim() || '';
    const fuelType = document.getElementById('filter-fuel')?.value || '';
    const maxPrice = parseFloat(document.getElementById('filter-max-price')?.value) || 999999;
    const sortVal = document.getElementById('filter-sort')?.value || 'popular';

    let list = this.vehicles.filter(v => {
      const matchCat = (this.activeFilter === 'all' || v.category_slug === this.activeFilter);
      const matchSearch = (!searchTerm || v.name.toLowerCase().includes(searchTerm) || v.brand.toLowerCase().includes(searchTerm));
      const matchFuel = (!fuelType || v.fuel_type === fuelType);
      const matchPrice = (parseFloat(v.daily_rate) <= maxPrice);
      return matchCat && matchSearch && matchFuel && matchPrice;
    });

    // Sorting
    if (sortVal === 'price_low') {
      list.sort((a, b) => parseFloat(a.daily_rate) - parseFloat(b.daily_rate));
    } else if (sortVal === 'price_high') {
      list.sort((a, b) => parseFloat(b.daily_rate) - parseFloat(a.daily_rate));
    } else if (sortVal === 'newest') {
      list.sort((a, b) => b.year - a.year);
    }

    this.renderVehicles(list);
  },

  resetFilters() {
    const searchInput = document.getElementById('filter-search');
    const fuelInput = document.getElementById('filter-fuel');
    const maxPriceInput = document.getElementById('filter-max-price');
    const priceDisplay = document.getElementById('price-range-display');

    if (searchInput) searchInput.value = '';
    if (fuelInput) fuelInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '3000';
    if (priceDisplay) priceDisplay.textContent = '₹3000';

    this.filterByCategory('all');
  },

  async openVehicleDetails(vehicleId) {
    try {
      const res = await API.get(`/vehicles/${vehicleId}`);
      if (!res.success || !res.data) return;

      const v = res.data;
      const modal = document.getElementById('vehicle-details-modal');
      const body = document.getElementById('vehicle-details-content');

      body.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Gallery & Image -->
          <div>
            <div class="rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 mb-4 aspect-[16/10]">
              <img id="vd-main-img" src="${v.primary_image}" alt="${v.name}" class="w-full h-full object-cover">
            </div>
            <div class="flex gap-2 overflow-x-auto pb-2">
              <img src="${v.primary_image}" onclick="document.getElementById('vd-main-img').src=this.src" class="w-16 h-12 object-cover rounded border border-yellow-brand cursor-pointer">
              ${(v.gallery || []).map(img => `
                <img src="${img.image_url}" onclick="document.getElementById('vd-main-img').src=this.src" class="w-16 h-12 object-cover rounded border border-zinc-800 hover:border-yellow-brand cursor-pointer">
              `).join('')}
            </div>
            
            <!-- Rental Rules & Cancellation Policy -->
            <div class="mt-6 p-4 rounded-xl bg-zinc-900 border border-zinc-800">
              <h4 class="font-bold text-white text-sm mb-2 flex items-center gap-2">
                <i class="bi bi-shield-lock-fill text-yellow-brand"></i> Rental Terms &amp; Policies
              </h4>
              <p class="text-xs text-zinc-300 leading-relaxed mb-3">${v.rental_rules || 'Valid license required. Safe riding gears mandatory.'}</p>
              <h5 class="font-semibold text-xs text-yellow-brand mb-1">Cancellation Policy:</h5>
              <p class="text-xs text-zinc-400">${v.cancellation_policy || 'Free cancellation up to 6 hours prior to pickup.'}</p>
            </div>
          </div>

          <!-- Specs & Booking Side Panel -->
          <div class="flex flex-col justify-between">
            <div>
              <div class="text-xs font-bold text-yellow-brand uppercase tracking-wider">${v.brand}</div>
              <h2 class="text-2xl font-black text-white mt-1">${v.name}</h2>
              <div class="flex items-center gap-3 my-3">
                <span class="badge-status badge-confirmed">${v.availability_status.toUpperCase()}</span>
                <span class="text-xs text-zinc-400"><i class="bi bi-geo-alt-fill text-yellow-brand mr-1"></i>${v.location_name || 'Central Hub'}</span>
              </div>

              <!-- Price Box -->
              <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-between my-4">
                <div>
                  <div class="text-xs text-zinc-400">Daily Rental</div>
                  <div class="text-3xl font-black text-white">₹${v.daily_rate}</div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-zinc-400">Security Deposit</div>
                  <div class="text-lg font-bold text-yellow-brand">₹${v.deposit_amount} <span class="text-[10px] text-zinc-400">(Refundable)</span></div>
                </div>
              </div>

              <!-- Key Specs Table -->
              <h4 class="font-bold text-white text-sm mb-3">Specifications</h4>
              <div class="grid grid-cols-2 gap-3 text-xs mb-6">
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-speedometer mr-1"></i>Engine:</span>
                  <span class="font-semibold text-white">${v.engine_capacity}</span>
                </div>
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-fuel-pump mr-1"></i>Fuel:</span>
                  <span class="font-semibold text-white">${v.fuel_type}</span>
                </div>
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-gear mr-1"></i>Transmission:</span>
                  <span class="font-semibold text-white">${v.transmission}</span>
                </div>
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-lightning-charge mr-1"></i>Mileage/Range:</span>
                  <span class="font-semibold text-white">${v.mileage}</span>
                </div>
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-people mr-1"></i>Capacity:</span>
                  <span class="font-semibold text-white">${v.passenger_capacity} Riders</span>
                </div>
                <div class="p-2.5 rounded bg-zinc-900/80 border border-zinc-800/80 flex items-center justify-between">
                  <span class="text-zinc-400"><i class="bi bi-shield-check mr-1"></i>Helmets:</span>
                  <span class="font-semibold text-white">${v.helmet_included} Included</span>
                </div>
              </div>

              <div class="text-xs text-zinc-400 mb-6">
                <strong class="text-white">Included Accessories:</strong> ${v.accessories || 'Mobile Holder, Tool kit, 2 Sanitized Helmets'}
              </div>
            </div>

            <!-- Action Button -->
            <div class="pt-4 border-t border-zinc-800 flex gap-3">
              <button onclick="App.closeVehicleDetails(); BookingWizard.open(App.vehicles.find(item=>item.id==${v.id}))" class="btn-rnr-primary flex-1 py-3 text-sm">
                Book This Ride <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      `;

      modal.classList.remove('hidden');
    } catch (err) {
      API.toast('Failed to load vehicle details.', 'error');
    }
  },

  closeVehicleDetails() {
    const modal = document.getElementById('vehicle-details-modal');
    if (modal) modal.classList.add('hidden');
  },

  /**
   * Customer Account Navigation & Sections
   */
  async openCustomerAccount(section = 'dashboard') {
    if (!Auth.isLoggedIn()) {
      Auth.openModal('login');
      return;
    }

    const modal = document.getElementById('customer-account-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    this.switchAccountTab(section);
  },

  closeCustomerAccount() {
    const modal = document.getElementById('customer-account-modal');
    if (modal) modal.classList.add('hidden');
  },

  openTermsModal() {
    const modal = document.getElementById('modal-rental-terms');
    if (modal) modal.classList.remove('hidden');
  },

  closeTermsModal() {
    const modal = document.getElementById('modal-rental-terms');
    if (modal) modal.classList.add('hidden');
  },

  async switchAccountTab(tab) {
    const tabs = ['dashboard', 'bookings', 'documents', 'profile'];
    tabs.forEach(t => {
      const el = document.getElementById(`acc-content-${t}`);
      const btn = document.getElementById(`acc-tab-${t}`);
      if (el) {
        if (t === tab) el.classList.remove('hidden');
        else el.classList.add('hidden');
      }
      if (btn) {
        if (t === tab) {
          btn.className = 'w-full text-left px-4 py-3 rounded-lg text-sm font-bold bg-yellow-brand text-black flex items-center gap-3 transition-colors';
        } else {
          btn.className = 'w-full text-left px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 flex items-center gap-3 transition-colors';
        }
      }
    });

    if (tab === 'dashboard' || tab === 'bookings') {
      await this.loadCustomerBookings();
    } else if (tab === 'documents') {
      await this.loadCustomerDocuments();
    } else if (tab === 'profile') {
      this.loadCustomerProfileForm();
    }
  },

  async loadCustomerBookings() {
    try {
      const res = await API.get('/bookings');
      if (!res.success) return;

      const bookings = res.data;

      // Update Dashboard Counters
      const total = bookings.length;
      const upcoming = bookings.filter(b => ['booking_confirmed', 'vehicle_assigned', 'ready_for_pickup'].includes(b.booking_status)).length;
      const active = bookings.filter(b => b.booking_status === 'rental_active').length;
      const pendingPay = bookings.filter(b => b.payment_status === 'pending' || b.payment_status === 'partial').length;
      const completed = bookings.filter(b => b.booking_status === 'completed').length;

      document.getElementById('acc-stat-total').textContent = total;
      document.getElementById('acc-stat-upcoming').textContent = upcoming;
      document.getElementById('acc-stat-active').textContent = active;
      document.getElementById('acc-stat-pending-pay').textContent = pendingPay;
      document.getElementById('acc-stat-completed').textContent = completed;

      // Render Bookings List
      const container = document.getElementById('acc-bookings-list');
      const allContainer = document.getElementById('acc-all-bookings-list');
      if (!container && !allContainer) return;

      if (bookings.length === 0) {
        const emptyHtml = '<div class="p-8 text-center text-zinc-400">No bookings found yet. Explore our fleet and book your first ride!</div>';
        if (container) container.innerHTML = emptyHtml;
        if (allContainer) allContainer.innerHTML = emptyHtml;
        return;
      }

      const listHtml = bookings.map(b => `
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <img src="${b.vehicle_image || 'assets/images/vehicles/activa6g.jpg'}" class="w-20 h-16 object-cover rounded-lg border border-zinc-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-bold text-yellow-brand">${b.booking_code}</span>
                <span class="badge-status badge-${b.booking_status}">${b.booking_status.replace(/_/g, ' ')}</span>
              </div>
              <h4 class="font-bold text-white text-base mt-1">${b.vehicle_name}</h4>
              <div class="text-xs text-zinc-400 flex items-center gap-3 mt-1">
                <span><i class="bi bi-calendar3 mr-1"></i>${b.pickup_datetime.substring(0, 16)}</span>
                <span>•</span>
                <span><i class="bi bi-geo-alt mr-1"></i>${b.pickup_location_name}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between md:justify-end gap-6 pt-3 md:pt-0 border-t md:border-t-0 border-zinc-800">
            <div class="text-right">
              <div class="text-xs text-zinc-400">Total / Paid</div>
              <div class="text-sm font-bold text-white">₹${b.total_amount} / <span class="${parseFloat(b.amount_paid) > 0 ? 'text-emerald-400' : 'text-zinc-400'}">₹${b.amount_paid}</span></div>
              <div class="text-xs ${parseFloat(b.remaining_balance) > 0 ? 'text-yellow-brand' : 'text-emerald-400'}">Balance: ₹${b.remaining_balance}</div>
            </div>
            <div class="flex gap-2">
              <button onclick="App.viewBookingDetailsModal(${b.id})" class="btn-rnr-secondary text-xs px-3 py-2">View</button>
              ${['pending_booking', 'pending_approval', 'pending_payment'].includes(b.booking_status) ? `<button onclick="App.cancelBooking(${b.id})" class="btn-rnr-secondary text-xs px-2 py-2 text-red-400 hover:text-red-300" title="Cancel Booking"><i class="bi bi-x-circle"></i></button>` : ''}
            </div>
          </div>
        </div>
      `).join('');

      if (container) container.innerHTML = listHtml;
      if (allContainer) allContainer.innerHTML = listHtml;

    } catch (e) {
      console.error('Customer bookings load failed:', e);
    }
  },

  /**
   * Critical Requirement: SECTION 8 — My Rental Documents
   */
  async loadCustomerDocuments() {
    try {
      const res = await API.get('/documents');
      if (!res.success) return;

      const docs = res.data;
      const container = document.getElementById('acc-documents-list');
      if (!container) return;

      if (docs.length === 0) {
        container.innerHTML = `
          <div class="p-8 text-center text-zinc-400">
            <i class="bi bi-folder-x text-3xl text-zinc-600 block mb-2"></i>
            No confirmed rental documents yet.<br>
            Once your booking is confirmed by admin, your Vehicle Rental Agreement, Booking Confirmation, Registration Certificate, and Insurance will automatically appear here!
          </div>
        `;
        return;
      }

      container.innerHTML = docs.map(d => `
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-zinc-800 text-yellow-brand flex items-center justify-center text-lg">
              <i class="bi bi-file-earmark-pdf"></i>
            </div>
            <div>
              <h5 class="font-bold text-white text-sm">${d.title}</h5>
              <div class="text-xs text-zinc-400 flex items-center gap-3 mt-0.5">
                <span>Code: ${d.booking_code || 'N/A'}</span>
                <span>•</span>
                <span class="text-zinc-500">${(d.file_size / 1024).toFixed(1)} KB</span>
                <span>•</span>
                <span class="badge-status badge-confirmed text-[9px]"><i class="bi bi-check2"></i> Verified</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <a href="${API.getBaseUrl()}/documents/${d.id}/download" target="_blank" class="btn-rnr-secondary text-xs px-3 py-2 flex items-center gap-1.5">
              <i class="bi bi-eye"></i> View
            </a>
            <a href="${API.getBaseUrl()}/documents/${d.id}/download" download="${d.file_name}" class="btn-rnr-primary text-xs px-3 py-2 flex items-center gap-1.5">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `).join('');

    } catch (e) {
      console.error('Customer documents load error:', e);
    }
  },

  loadCustomerProfileForm() {
    const user = Auth.getUser();
    if (user) {
      document.getElementById('acc-prof-name').value = user.name || '';
      document.getElementById('acc-prof-phone').value = user.phone || '';
      document.getElementById('acc-prof-email').value = user.email || '';
      document.getElementById('acc-prof-address').value = user.address || '';
    }
  },

  async saveCustomerProfile(e) {
    e.preventDefault();
    const name = document.getElementById('acc-prof-name').value;
    const phone = document.getElementById('acc-prof-phone').value;
    const email = document.getElementById('acc-prof-email').value;
    const address = document.getElementById('acc-prof-address').value;
    const password = document.getElementById('acc-prof-pass').value;

    try {
      const res = await API.put('/auth/profile', {
        name, phone, email, address, password: password || undefined
      });
      if (res.success) {
        API.toast('Profile updated successfully!', 'success');
        Auth.user = res.data;
        localStorage.setItem('rnr_user', JSON.stringify(res.data));
        Auth.updateUI();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async viewBookingDetailsModal(bookingId) {
    try {
      const res = await API.get(`/bookings/${bookingId}`);
      if (!res.success) return;

      const b = res.data;
      const modal = document.getElementById('booking-details-modal');
      const body = document.getElementById('booking-details-modal-content');

      body.innerHTML = `
        <div class="space-y-6">
          <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
            <div>
              <span class="text-xs text-yellow-brand font-mono font-bold">${b.booking_code}</span>
              <h3 class="text-xl font-bold text-white">${b.vehicle_name}</h3>
            </div>
            <span class="badge-status badge-${b.booking_status} text-xs font-bold">${b.booking_status.replace(/_/g, ' ')}</span>
          </div>

          <!-- Schedule & Location -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800">
              <span class="text-zinc-500 font-semibold block mb-1">PICKUP LOCATION &amp; TIME</span>
              <div class="text-white font-bold">${b.pickup_location_name}</div>
              <div class="text-yellow-brand mt-1"><i class="bi bi-clock mr-1"></i>${b.pickup_datetime}</div>
            </div>
            <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800">
              <span class="text-zinc-500 font-semibold block mb-1">RETURN LOCATION &amp; TIME</span>
              <div class="text-white font-bold">${b.return_location_name}</div>
              <div class="text-yellow-brand mt-1"><i class="bi bi-clock mr-1"></i>${b.return_datetime}</div>
            </div>
          </div>

          <!-- Financial Breakdown -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 space-y-2 text-xs">
            <div class="flex justify-between text-zinc-400">
              <span>Rental Charge (${b.total_days} Days):</span>
              <span class="text-white font-bold">₹${b.rental_charge}</span>
            </div>
            <div class="flex justify-between text-zinc-400">
              <span>Security Deposit:</span>
              <span class="text-white font-bold">₹${b.deposit_amount}</span>
            </div>
            ${parseFloat(b.discount_amount) > 0 ? `
              <div class="flex justify-between text-emerald-400">
                <span>Discount (${b.promo_code}):</span>
                <span>-₹${b.discount_amount}</span>
              </div>
            ` : ''}
            <div class="flex justify-between text-zinc-400">
              <span>GST / Taxes:</span>
              <span class="text-white font-bold">₹${b.tax_amount}</span>
            </div>
            <div class="flex justify-between text-base font-extrabold text-white pt-2 border-t border-zinc-800">
              <span>Total Booking Amount:</span>
              <span class="text-yellow-brand">₹${b.total_amount}</span>
            </div>
            <div class="flex justify-between text-emerald-400 pt-1 font-semibold">
              <span>Amount Paid:</span>
              <span>₹${b.amount_paid}</span>
            </div>
            <div class="flex justify-between text-yellow-brand font-semibold">
              <span>Remaining Balance:</span>
              <span>₹${b.remaining_balance}</span>
            </div>
          </div>

          <!-- Offline Payment Status Notice -->
          <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <span class="font-bold text-white flex items-center gap-1.5">
                <i class="bi bi-info-circle text-yellow-brand"></i> Offline Payment &amp; Admin Verification
              </span>
              <span class="text-[11px] text-zinc-400 mt-0.5 block">
                Payments are collected offline and recorded from the Admin site by staff. Share your UPI / cash receipt with our operations team.
              </span>
            </div>
            <a href="https://wa.me/919395485880?text=Hi!%20I%20am%20sharing%20offline%20payment%20proof%20for%20Booking%20${encodeURIComponent(b.booking_code)}" target="_blank" class="btn-rnr-secondary text-xs px-3 py-2 text-emerald-400 border-emerald-500/40 hover:bg-emerald-500/10 whitespace-nowrap flex items-center justify-center gap-1.5 font-bold">
              <i class="bi bi-whatsapp"></i> WhatsApp Help
            </a>
          </div>

          <!-- Documents Attached -->
          <div>
            <h4 class="font-bold text-white text-sm mb-3">Rental Documents &amp; Certificates</h4>
            <div class="space-y-2">
              ${(b.documents || []).length > 0 ? b.documents.map(d => `
                <div class="p-2.5 rounded bg-zinc-900 border border-zinc-800 flex items-center justify-between text-xs">
                  <span class="text-white font-medium"><i class="bi bi-file-earmark-pdf text-yellow-brand mr-2"></i>${d.title}</span>
                  <a href="${API.getBaseUrl()}/documents/${d.id}/download" target="_blank" class="text-yellow-brand hover:underline font-bold">Download</a>
                </div>
              `).join('') : '<div class="text-xs text-zinc-500">Documents will appear once confirmed by staff.</div>'}
            </div>
          </div>

          <!-- Booking Timeline -->
          <div>
            <h4 class="font-bold text-white text-sm mb-3">Booking History Timeline</h4>
            <div class="space-y-2 text-xs">
              ${(b.timeline || []).map(t => `
                <div class="flex items-start gap-3 p-2 rounded bg-zinc-900/50">
                  <div class="w-2 h-2 rounded-full bg-yellow-brand mt-1.5"></div>
                  <div class="flex-1">
                    <span class="font-bold text-white">${t.status.toUpperCase().replace(/_/g, ' ')}</span>
                    <span class="text-zinc-500 ml-2">${t.created_at}</span>
                    <div class="text-zinc-400 mt-0.5">${t.notes || ''}</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;

      modal.classList.remove('hidden');
    } catch (e) {
      API.toast('Could not load booking details.', 'error');
    }
  },

  async payBookingBalance(bookingId, balance) {
    API.toast('All ride payments are offline. Please settle via WhatsApp or at pickup handover with Admin staff.', 'info');
  },

  async cancelBooking(bookingId) {
    const reason = prompt('Please enter the reason for booking cancellation:');
    if (reason !== null && reason.trim() !== '') {
      try {
        const res = await API.post(`/bookings/${bookingId}/cancel`, { reason });
        if (res.success) {
          API.toast('Booking cancelled successfully.', 'info');
          this.loadCustomerBookings();
        }
      } catch (err) {
        API.toast(err.message, 'error');
      }
    }
  },

  registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js').catch(err => {
        console.warn('SW registration note:', err);
      });
    }
  },

  setupPWAInstall() {
    let deferredPrompt;
    const installBanner = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-install-btn');

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      if (installBanner) installBanner.classList.remove('hidden');
    });

    if (installBtn) {
      installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
          if (installBanner) installBanner.classList.add('hidden');
        }
        deferredPrompt = null;
      });
    }
  },

  bindEvents() {
    // Search form on Hero
    document.getElementById('hero-booking-search-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const locId = document.getElementById('search-pickup-loc')?.value;
      const catSlug = document.getElementById('search-category-select')?.value;
      const pickDate = document.getElementById('search-pickup-date')?.value;
      const retDate = document.getElementById('search-return-date')?.value;

      if (catSlug && catSlug !== 'all') {
        this.filterByCategory(catSlug);
      }

      // Scroll smoothly to fleet section
      document.getElementById('fleet-section')?.scrollIntoView({ behavior: 'smooth' });
    });

    // Support Form submit
    document.getElementById('support-contact-form')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('sup-name')?.value;
      const phone = document.getElementById('sup-phone')?.value;
      const email = document.getElementById('sup-email')?.value;
      const category = document.getElementById('sup-cat')?.value;
      const subject = document.getElementById('sup-subject')?.value;
      const message = document.getElementById('sup-msg')?.value;

      try {
        const res = await API.post('/support/tickets', {
          customer_name: name,
          customer_phone: phone,
          customer_email: email,
          category,
          subject,
          message
        });

        if (res.success) {
          API.toast('Support inquiry submitted! Ticket: ' + res.data.ticket_code, 'success');
          document.getElementById('support-contact-form').reset();
        }
      } catch (err) {
        API.toast(err.message, 'error');
      }
    });

    // Profile form
    document.getElementById('acc-profile-form')?.addEventListener('submit', (e) => this.saveCustomerProfile(e));

    // Price range slider
    const priceSlider = document.getElementById('filter-max-price');
    const priceDisplay = document.getElementById('price-range-display');
    if (priceSlider && priceDisplay) {
      priceSlider.addEventListener('input', (e) => {
        priceDisplay.textContent = `₹${e.target.value}`;
        this.applySearchAndFilters();
      });
    }

    // Search and fuel filter triggers
    document.getElementById('filter-search')?.addEventListener('input', () => this.applySearchAndFilters());
    document.getElementById('filter-fuel')?.addEventListener('change', () => this.applySearchAndFilters());
    document.getElementById('filter-sort')?.addEventListener('change', () => this.applySearchAndFilters());
  },

  handleRoute() {
    const hash = window.location.hash;
    if (hash === '#vehicles') {
      document.getElementById('fleet-section')?.scrollIntoView({ behavior: 'smooth' });
    } else if (hash === '#account') {
      this.openCustomerAccount('dashboard');
    } else if (hash === '#documents') {
      this.openCustomerAccount('documents');
    }
  }
};

window.App = App;
document.addEventListener('DOMContentLoaded', () => App.init());
