/**
 * Rome & Ride — Multi-Step Booking Wizard
 * Steps 1 - 7 Fully Integrated
 */

const BookingWizard = {
  currentStep: 1,
  selectedVehicle: null,
  bookingData: {
    vehicle_id: null,
    pickup_date: '',
    pickup_time: '10:00',
    return_date: '',
    return_time: '18:00',
    pickup_location_id: 1,
    return_location_id: 1,
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    customer_notes: '',
    promo_code: '',
    discount_amount: 0,
    payment_option: 'offline', // Offline counter / WhatsApp payment
  },
  calculatedSummary: null,
  activeBookingResult: null,

  init() {
    this.initDefaultDates();
    this.bindEvents();
  },

  initDefaultDates() {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const pad = (n) => String(n).padStart(2, '0');
    const formatYMD = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    this.bookingData.pickup_date = formatYMD(today);
    this.bookingData.return_date = formatYMD(tomorrow);

    const pickupDateInput = document.getElementById('bw-pickup-date');
    const returnDateInput = document.getElementById('bw-return-date');
    if (pickupDateInput) pickupDateInput.value = this.bookingData.pickup_date;
    if (returnDateInput) returnDateInput.value = this.bookingData.return_date;
  },

  open(vehicle = null) {
    const modal = document.getElementById('booking-modal');
    if (!modal) return;

    if (vehicle) {
      this.selectedVehicle = vehicle;
      this.bookingData.vehicle_id = vehicle.id;
    }

    // Prefill customer info if logged in
    const user = Auth.getUser();
    if (user) {
      this.bookingData.customer_name = user.name;
      this.bookingData.customer_phone = user.phone;
      this.bookingData.customer_email = user.email || '';
    }

    modal.classList.remove('hidden');
    // If vehicle pre-selected, go to step 2 directly
    this.goToStep(vehicle ? 2 : 1);
  },

  close() {
    const modal = document.getElementById('booking-modal');
    if (modal) modal.classList.add('hidden');
  },

  goToStep(stepNumber) {
    this.currentStep = stepNumber;
    
    // Update step containers
    for (let i = 1; i <= 7; i++) {
      const stepEl = document.getElementById(`bw-step-${i}`);
      const indicator = document.getElementById(`bw-indicator-${i}`);
      
      if (stepEl) {
        if (i === stepNumber) {
          stepEl.classList.remove('hidden');
        } else {
          stepEl.classList.add('hidden');
        }
      }

      if (indicator) {
        if (i === stepNumber) {
          indicator.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-yellow-brand text-black font-black text-sm shadow-md';
        } else if (i < stepNumber) {
          indicator.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500 text-black font-black text-sm';
        } else {
          indicator.className = 'flex items-center justify-center w-8 h-8 rounded-full bg-zinc-800 text-zinc-400 font-bold text-sm border border-zinc-700';
        }
      }
    }

    // Update dynamic contents for the step
    if (stepNumber === 1) {
      this.renderVehicleSelectStep();
    } else if (stepNumber === 2) {
      this.renderRentalPeriodStep();
    } else if (stepNumber === 3) {
      this.renderCustomerInfoStep();
    } else if (stepNumber === 4) {
      this.renderLicenseUploadStep();
    } else if (stepNumber === 5) {
      this.calculateAndRenderSummary();
    } else if (stepNumber === 6) {
      this.renderPaymentSelectionStep();
    } else if (stepNumber === 7) {
      this.renderConfirmationStep();
    }
  },

  renderVehicleSelectStep() {
    const container = document.getElementById('bw-vehicles-list');
    if (!container) return;

    if (!window.AppVehicles || window.AppVehicles.length === 0) {
      container.innerHTML = '<div class="col-span-2 text-center text-zinc-400 py-8">Loading fleet...</div>';
      return;
    }

    container.innerHTML = window.AppVehicles.map(v => `
      <div onclick="BookingWizard.selectVehicleAndProceed(${v.id})" 
           class="rnr-card p-4 cursor-pointer hover:border-yellow-brand transition-all flex gap-4 items-center ${this.bookingData.vehicle_id === v.id ? 'border-yellow-brand bg-yellow-brand/5' : ''}">
        <img src="${v.primary_image}" alt="${v.name}" class="w-20 h-16 object-cover rounded-lg border border-zinc-800 bg-zinc-950">
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold text-yellow-brand uppercase">${v.brand}</div>
          <h4 class="font-bold text-white text-sm truncate">${v.name}</h4>
          <div class="text-xs text-zinc-400 mt-1 flex items-center gap-3">
            <span><i class="bi bi-fuel-pump mr-1"></i>${v.fuel_type}</span>
            <span><i class="bi bi-gear mr-1"></i>${v.transmission}</span>
          </div>
        </div>
        <div class="text-right">
          <div class="text-lg font-black text-yellow-brand">₹${v.daily_rate}</div>
          <div class="text-[11px] text-zinc-400">per day</div>
          <button class="mt-2 text-xs font-bold text-black bg-yellow-brand px-3 py-1 rounded">Select</button>
        </div>
      </div>
    `).join('');
  },

  selectVehicleAndProceed(vehicleId) {
    const v = window.AppVehicles.find(item => item.id == vehicleId);
    if (v) {
      this.selectedVehicle = v;
      this.bookingData.vehicle_id = v.id;
      this.goToStep(2);
    }
  },

  renderRentalPeriodStep() {
    const summaryCard = document.getElementById('bw-selected-vehicle-card');
    if (summaryCard && this.selectedVehicle) {
      summaryCard.innerHTML = `
        <div class="flex items-center gap-4 p-3 rounded-lg bg-zinc-900 border border-zinc-800">
          <img src="${this.selectedVehicle.primary_image}" class="w-16 h-12 object-cover rounded border border-zinc-800">
          <div class="flex-1">
            <h4 class="font-bold text-white text-sm">${this.selectedVehicle.name}</h4>
            <div class="text-xs text-yellow-brand font-semibold">₹${this.selectedVehicle.daily_rate} / day • Deposit: ₹${this.selectedVehicle.deposit_amount}</div>
          </div>
          <button onclick="BookingWizard.goToStep(1)" class="text-xs text-zinc-400 hover:text-yellow-brand underline">Change</button>
        </div>
      `;
    }
  },

  renderCustomerInfoStep() {
    const user = Auth.getUser();
    if (user) {
      document.getElementById('bw-cust-name').value = user.name || '';
      document.getElementById('bw-cust-phone').value = user.phone || '';
      document.getElementById('bw-cust-email').value = user.email || '';
    }
  },

  renderLicenseUploadStep() {
    // Shows status of license front and back
    const statusFront = document.getElementById('bw-license-front-status');
    const statusBack = document.getElementById('bw-license-back-status');

    if (this.bookingData.license_front_uploaded) {
      statusFront.innerHTML = '<span class="badge-status badge-confirmed"><i class="bi bi-check2"></i> File Ready</span>';
    }
    if (this.bookingData.license_back_uploaded) {
      statusBack.innerHTML = '<span class="badge-status badge-confirmed"><i class="bi bi-check2"></i> File Ready</span>';
    }
  },

  async calculateAndRenderSummary() {
    if (!this.selectedVehicle) return;

    const pickupDtStr = `${this.bookingData.pickup_date} ${this.bookingData.pickup_time}:00`;
    const returnDtStr = `${this.bookingData.return_date} ${this.bookingData.return_time}:00`;

    const pickup = new Date(pickupDtStr);
    const ret = new Date(returnDtStr);

    let diffHours = Math.round((ret - pickup) / (1000 * 60 * 60));
    if (diffHours <= 0) diffHours = 1;
    const diffDays = Math.max(1, Math.ceil(diffHours / 24));

    const dailyRate = parseFloat(this.selectedVehicle.daily_rate);
    const hourlyRate = parseFloat(this.selectedVehicle.hourly_rate);
    const deposit = parseFloat(this.selectedVehicle.deposit_amount);

    let rentalCharge = 0;
    if (diffHours < 24 && hourlyRate > 0) {
      rentalCharge = diffHours * hourlyRate;
    } else {
      rentalCharge = diffDays * dailyRate;
    }

    let discount = this.bookingData.discount_amount || 0;
    const taxable = Math.max(0, rentalCharge - discount);
    const tax = Math.round(taxable * 0.05 * 100) / 100;
    const total = Math.round((taxable + tax + deposit) * 100) / 100;

    // Advance 25% with min ₹300
    const advance = Math.min(total, Math.max(300, Math.round(total * 0.25)));

    this.calculatedSummary = {
      pickupDtStr,
      returnDtStr,
      diffDays,
      diffHours,
      dailyRate,
      rentalCharge,
      deposit,
      discount,
      tax,
      total,
      advance,
      remainingAfterAdvance: Math.max(0, total - advance)
    };

    // Render into step 5
    document.getElementById('bw-sum-vehicle-name').textContent = this.selectedVehicle.name;
    document.getElementById('bw-sum-duration').textContent = `${diffDays} Day(s) (${diffHours} Hours)`;
    document.getElementById('bw-sum-pickup').textContent = pickupDtStr;
    document.getElementById('bw-sum-return').textContent = returnDtStr;
    document.getElementById('bw-sum-rental-charge').textContent = `₹${rentalCharge.toFixed(2)}`;
    document.getElementById('bw-sum-deposit').textContent = `₹${deposit.toFixed(2)}`;
    document.getElementById('bw-sum-discount').textContent = discount > 0 ? `-₹${discount.toFixed(2)}` : '₹0.00';
    document.getElementById('bw-sum-tax').textContent = `₹${tax.toFixed(2)}`;
    document.getElementById('bw-sum-total').textContent = `₹${total.toFixed(2)}`;
    document.getElementById('bw-sum-advance-payable').textContent = `₹${advance.toFixed(2)}`;
    document.getElementById('bw-sum-remaining').textContent = `₹${(total - advance).toFixed(2)}`;
  },

  renderPaymentSelectionStep() {
    if (!this.calculatedSummary) return;

    if (document.getElementById('bw-pay-advance-amt')) {
      document.getElementById('bw-pay-advance-amt').textContent = `₹${this.calculatedSummary.advance.toFixed(2)}`;
    }
    if (document.getElementById('bw-pay-wa-advance-amt')) {
      document.getElementById('bw-pay-wa-advance-amt').textContent = `₹${this.calculatedSummary.advance.toFixed(2)}`;
    }
    if (document.getElementById('bw-pay-full-amt')) {
      document.getElementById('bw-pay-full-amt').textContent = `₹${this.calculatedSummary.total.toFixed(2)}`;
    }
  },

  renderConfirmationStep() {
    if (!this.activeBookingResult) return;

    const b = this.activeBookingResult.booking;
    document.getElementById('bw-conf-code').textContent = b.booking_code;
    document.getElementById('bw-conf-vehicle').textContent = b.vehicle_name || (this.selectedVehicle ? this.selectedVehicle.name : '');
    document.getElementById('bw-conf-dates').textContent = `${b.pickup_datetime} to ${b.return_datetime}`;
    document.getElementById('bw-conf-total').textContent = `₹${b.total_amount}`;
    document.getElementById('bw-conf-paid').textContent = `₹${b.amount_paid}`;
    document.getElementById('bw-conf-balance').textContent = `₹${b.remaining_balance}`;

    const statusEl = document.getElementById('bw-conf-status');
    if (statusEl) {
      statusEl.className = 'badge-status badge-' + (b.booking_status || 'pending_approval');
      statusEl.textContent = (b.booking_status || 'pending_approval').toUpperCase().replace(/_/g, ' ');
    }

    document.getElementById('bw-conf-docs-status').textContent = b.document_verification_status.toUpperCase().replace(/_/g, ' ');

    const waBtn = document.getElementById('bw-conf-whatsapp-btn');
    if (waBtn && this.lastWhatsAppUrl) {
      waBtn.href = this.lastWhatsAppUrl;
    }

    if (b.payment_deadline) {
      document.getElementById('bw-conf-deadline-wrap').classList.remove('hidden');
      document.getElementById('bw-conf-deadline').textContent = b.payment_deadline;
    } else {
      document.getElementById('bw-conf-deadline-wrap').classList.add('hidden');
    }
  },

  async applyPromoCode() {
    const code = document.getElementById('bw-promo-input')?.value.trim();
    if (!code) {
      API.toast('Please enter a coupon code.', 'warning');
      return;
    }

    try {
      const res = await API.post('/bookings/apply-promo', {
        code: code,
        amount: this.calculatedSummary ? this.calculatedSummary.rentalCharge : 500
      });

      if (res.success && res.data) {
        this.bookingData.promo_code = res.data.code;
        this.bookingData.discount_amount = parseFloat(res.data.discount);
        API.toast(`Coupon ${res.data.code} applied! Saved ₹${res.data.discount}`, 'success');
        this.calculateAndRenderSummary();
      }
    } catch (err) {
      API.toast(err.message, 'error');
    }
  },

  async submitFinalBooking() {
    if (!this.selectedVehicle) {
      API.toast('Please select a vehicle.', 'error');
      return;
    }

    // Required fields check
    const name = document.getElementById('bw-cust-name')?.value.trim();
    const phone = document.getElementById('bw-cust-phone')?.value.trim();
    const email = document.getElementById('bw-cust-email')?.value.trim();
    const notes = document.getElementById('bw-cust-notes')?.value.trim();

    if (!name || !phone) {
      API.toast('Customer Name and Phone number are required.', 'error');
      this.goToStep(3);
      return;
    }

    // Check Terms & Conditions agreement
    const termsAgree = document.getElementById('bw-terms-agree');
    if (termsAgree && !termsAgree.checked) {
      API.toast('Please agree to the Roam & Ride Rental Terms & Conditions before confirming.', 'warning');
      this.goToStep(5);
      return;
    }

    const pickupLocationId = document.getElementById('bw-pickup-loc')?.value || 1;
    const returnLocationId = document.getElementById('bw-return-loc')?.value || pickupLocationId;

    const chosenOption = this.bookingData.payment_option || 'whatsapp';
    // Map 'whatsapp' to 'advance' for database schema compatibility while retaining workflow
    const dbPaymentOption = (chosenOption === 'whatsapp') ? 'advance' : chosenOption;

    const payload = {
      vehicle_id: this.selectedVehicle.id,
      pickup_datetime: `${this.bookingData.pickup_date} ${this.bookingData.pickup_time}:00`,
      return_datetime: `${this.bookingData.return_date} ${this.bookingData.return_time}:00`,
      pickup_location_id: pickupLocationId,
      return_location_id: returnLocationId,
      customer_name: name,
      customer_phone: phone,
      customer_email: email || null,
      customer_notes: notes || null,
      promo_code: this.bookingData.promo_code || null,
      payment_option: dbPaymentOption,
    };

    const submitBtn = document.getElementById('bw-confirm-booking-btn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Processing & Redirecting...';
    }

    try {
      const res = await API.post('/bookings', payload);

      if (res.success && res.data) {
        this.activeBookingResult = res.data;
        const b = res.data.booking || {};
        const bCode = res.data.booking_code || b.booking_code || ('RR-' + res.data.booking_id);
        const vName = this.selectedVehicle.name;
        const vBrand = this.selectedVehicle.brand || '';
        const vModel = this.selectedVehicle.model || '';
        const vReg = this.selectedVehicle.reg_number || 'Assigned on Handover';
        const vCat = this.selectedVehicle.category_name || 'Scooter';
        const pickDt = payload.pickup_datetime;
        const retDt = payload.return_datetime;
        const daysStr = this.calculatedSummary ? `${this.calculatedSummary.diffDays} Day(s) (${this.calculatedSummary.diffHours} Hours)` : 'Rental Period';
        const rentalAmt = this.calculatedSummary ? this.calculatedSummary.rentalCharge : (b.rental_charge || 0);
        const depositAmt = this.calculatedSummary ? this.calculatedSummary.deposit : (b.deposit_amount || 1000);
        const totalAmt = res.data.total_amount || b.total_amount || (this.calculatedSummary ? this.calculatedSummary.total : 0);
        const advAmt = res.data.advance_required || b.advance_required || (this.calculatedSummary ? this.calculatedSummary.advance : 300);

        // Construct pre-filled WhatsApp payment & confirmation request
        const waText = 
`🛵 ROAM & RIDE - BOOKING & PAYMENT REQUEST

Hi! I want to pay / confirm a ride.

📋 BOOKING DETAILS
* Booking ID: ${bCode}
* Customer Name: ${name}
* Contact Phone: ${phone}

🏍️ FLEET DETAILS
* Vehicle: ${vName} (${vBrand} ${vModel})
* Category: ${vCat}
* Registration: ${vReg}

📅 RENTAL SCHEDULE
* Pickup: ${pickDt}
* Return: ${retDt}
* Duration: ${daysStr}
* Pickup Location: Kamarkuchi Hub (Sonapur)
📍 https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic

💰 PAYMENT DETAILS
* Rental Amount: ₹${rentalAmt}
* Security Deposit: ₹${depositAmt} (Refundable upon vehicle return)
* Total Booking Amount: ₹${totalAmt}

Please share the official UPI QR code / payment link to confirm my ride!`;

        const waUrl = `https://wa.me/919395485880?text=${encodeURIComponent(waText)}`;
        this.lastWhatsAppUrl = waUrl;

        API.toast('Booking reserved! Redirecting to WhatsApp...', 'success');

        // Automatically open WhatsApp payment conversation
        try {
          window.open(waUrl, '_blank');
        } catch (e) {
          console.warn('Popup blocked, available via WhatsApp button:', e);
        }

        // Upload any queued license documents
        await this.uploadPendingDocuments(res.data.booking_id);

        this.goToStep(7);
      }
    } catch (err) {
      API.toast(err.message, 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Confirm & Proceed to WhatsApp / Payment <i class="bi bi-arrow-right-circle-fill"></i>';
      }
    }
  },

  async processBookingPayment(bookingId, amount, type = 'booking_advance') {
    // Customer self-payment is disabled; payments are handled offline by Admin staff
    return false;
  },

  async uploadPendingDocuments(bookingId) {
    const frontFile = document.getElementById('bw-doc-front-file')?.files[0];
    const backFile = document.getElementById('bw-doc-back-file')?.files[0];

    if (frontFile) {
      const fd = new FormData();
      fd.append('file', frontFile);
      fd.append('booking_id', bookingId);
      fd.append('doc_type', 'driving_license_front');
      fd.append('title', 'Driving License (Front)');
      try {
        await API.request('/documents/upload', { method: 'POST', body: fd });
      } catch (e) {}
    }

    if (backFile) {
      const fd = new FormData();
      fd.append('file', backFile);
      fd.append('booking_id', bookingId);
      fd.append('doc_type', 'driving_license_back');
      fd.append('title', 'Driving License (Back)');
      try {
        await API.request('/documents/upload', { method: 'POST', body: fd });
      } catch (e) {}
    }
  },

  bindEvents() {
    // Step 2 date & time changes
    const bindDateChange = () => {
      this.bookingData.pickup_date = document.getElementById('bw-pickup-date')?.value || this.bookingData.pickup_date;
      this.bookingData.pickup_time = document.getElementById('bw-pickup-time')?.value || this.bookingData.pickup_time;
      this.bookingData.return_date = document.getElementById('bw-return-date')?.value || this.bookingData.return_date;
      this.bookingData.return_time = document.getElementById('bw-return-time')?.value || this.bookingData.return_time;
    };

    ['bw-pickup-date', 'bw-pickup-time', 'bw-return-date', 'bw-return-time'].forEach(id => {
      document.getElementById(id)?.addEventListener('change', bindDateChange);
    });

    // Option A vs Option B payment option radios
    document.querySelectorAll('input[name="payment_option_choice"]').forEach(radio => {
      radio.addEventListener('change', (e) => {
        this.bookingData.payment_option = e.target.value;
      });
    });

    // File input change indicators
    document.getElementById('bw-doc-front-file')?.addEventListener('change', (e) => {
      const statusFront = document.getElementById('bw-license-front-status');
      if (e.target.files.length > 0 && statusFront) {
        statusFront.innerHTML = `<span class="badge-status badge-confirmed"><i class="bi bi-file-earmark-check"></i> ${e.target.files[0].name}</span>`;
      }
    });

    document.getElementById('bw-doc-back-file')?.addEventListener('change', (e) => {
      const statusBack = document.getElementById('bw-license-back-status');
      if (e.target.files.length > 0 && statusBack) {
        statusBack.innerHTML = `<span class="badge-status badge-confirmed"><i class="bi bi-file-earmark-check"></i> ${e.target.files[0].name}</span>`;
      }
    });
  }
};

window.BookingWizard = BookingWizard;
document.addEventListener('DOMContentLoaded', () => BookingWizard.init());
