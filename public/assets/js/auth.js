/**
 * Rome & Ride — Authentication Controller
 */

const Auth = {
  user: null,

  init() {
    this.loadUserFromStorage();
    this.verifyToken();
    this.bindEvents();
    this.updateUI();
  },

  loadUserFromStorage() {
    const stored = localStorage.getItem('rnr_user');
    if (stored) {
      try {
        this.user = JSON.parse(stored);
      } catch (e) {
        this.user = null;
      }
    }
  },

  async verifyToken() {
    const token = API.getToken();
    if (!token) {
      this.user = null;
      this.updateUI();
      return;
    }

    try {
      const res = await API.get('/auth/me');
      if (res.success && res.data) {
        this.user = res.data;
        localStorage.setItem('rnr_user', JSON.stringify(this.user));
        this.updateUI();
      }
    } catch (e) {
      // Token expired or invalid
      this.logout(false);
    }
  },

  isLoggedIn() {
    return !!this.user && !!API.getToken();
  },

  isAdmin() {
    return this.user && ['super_admin', 'admin', 'booking_manager', 'payment_manager', 'vehicle_manager', 'staff'].includes(this.user.role);
  },

  getUser() {
    return this.user;
  },

  async login(identifier, password) {
    try {
      const res = await API.post('/auth/login', {
        login: identifier,
        password: password
      });

      if (res.success && res.data) {
        this.user = res.data.user;
        API.setToken(res.data.token);
        localStorage.setItem('rnr_user', JSON.stringify(this.user));
        API.toast(`Welcome back, ${this.user.name}!`, 'success');
        this.closeModal();
        this.updateUI();

        // If admin/staff, offer redirect to admin dashboard
        if (this.isAdmin() && !window.location.pathname.includes('/admin')) {
          setTimeout(() => {
            if (confirm('You are logged in with Staff/Admin privileges. Open Admin Dashboard?')) {
              window.location.href = window.location.pathname.includes('/public') ? 'admin' : 'public/admin';
            }
          }, 600);
        }

        return true;
      }
    } catch (err) {
      API.toast(err.message, 'error');
      return false;
    }
  },

  async register(name, phone, email, password, address) {
    try {
      const res = await API.post('/auth/register', {
        name,
        phone,
        email,
        password,
        address
      });

      if (res.success && res.data) {
        this.user = res.data.user;
        API.setToken(res.data.token);
        localStorage.setItem('rnr_user', JSON.stringify(this.user));
        API.toast('Registration successful! Welcome to Rome & Ride.', 'success');
        this.closeModal();
        this.updateUI();
        return true;
      }
    } catch (err) {
      API.toast(err.message, 'error');
      return false;
    }
  },

  async logout(notify = true) {
    try {
      await API.post('/auth/logout');
    } catch (e) {}

    this.user = null;
    API.setToken(null);
    localStorage.removeItem('rnr_user');
    this.updateUI();

    if (notify) {
      API.toast('Logged out successfully.', 'info');
      // If on admin or account page, return to home
      if (window.location.pathname.includes('/admin') || window.location.hash.includes('account')) {
        window.location.href = './';
      }
    }
  },

  openModal(tab = 'login') {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    this.switchTab(tab);
  },

  closeModal() {
    const modal = document.getElementById('auth-modal');
    if (modal) modal.classList.add('hidden');
  },

  switchTab(tab) {
    const loginForm = document.getElementById('login-form-container');
    const registerForm = document.getElementById('register-form-container');
    const tabLogin = document.getElementById('tab-btn-login');
    const tabRegister = document.getElementById('tab-btn-register');

    if (tab === 'login') {
      loginForm?.classList.remove('hidden');
      registerForm?.classList.add('hidden');
      tabLogin?.classList.add('border-yellow-brand', 'text-yellow-brand');
      tabLogin?.classList.remove('text-zinc-400', 'border-transparent');
      tabRegister?.classList.remove('border-yellow-brand', 'text-yellow-brand');
      tabRegister?.classList.add('text-zinc-400', 'border-transparent');
    } else {
      loginForm?.classList.add('hidden');
      registerForm?.classList.remove('hidden');
      tabRegister?.classList.add('border-yellow-brand', 'text-yellow-brand');
      tabRegister?.classList.remove('text-zinc-400', 'border-transparent');
      tabLogin?.classList.remove('border-yellow-brand', 'text-yellow-brand');
      tabLogin?.classList.add('text-zinc-400', 'border-transparent');
    }
  },

  updateUI() {
    const userButtons = document.querySelectorAll('.auth-user-btn');
    const guestButtons = document.querySelectorAll('.auth-guest-btn');
    const userNameDisplays = document.querySelectorAll('.auth-user-name');
    const adminLinks = document.querySelectorAll('.auth-admin-link');

    if (this.isLoggedIn()) {
      guestButtons.forEach(el => el.classList.add('hidden'));
      userButtons.forEach(el => el.classList.remove('hidden'));
      userNameDisplays.forEach(el => el.textContent = this.user.name);

      if (this.isAdmin()) {
        adminLinks.forEach(el => el.classList.remove('hidden'));
      } else {
        adminLinks.forEach(el => el.classList.add('hidden'));
      }
    } else {
      guestButtons.forEach(el => el.classList.remove('hidden'));
      userButtons.forEach(el => el.classList.add('hidden'));
      adminLinks.forEach(el => el.classList.add('hidden'));
    }
  },

  bindEvents() {
    // Modal tab switching
    document.getElementById('tab-btn-login')?.addEventListener('click', () => this.switchTab('login'));
    document.getElementById('tab-btn-register')?.addEventListener('click', () => this.switchTab('register'));

    // Login submit
    document.getElementById('form-login')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const loginVal = document.getElementById('login-identifier')?.value;
      const passVal = document.getElementById('login-password')?.value;
      if (!loginVal || !passVal) {
        API.toast('Please enter your phone/email and password.', 'warning');
        return;
      }
      await this.login(loginVal, passVal);
    });

    // Register submit
    document.getElementById('form-register')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('reg-name')?.value;
      const phone = document.getElementById('reg-phone')?.value;
      const email = document.getElementById('reg-email')?.value;
      const password = document.getElementById('reg-password')?.value;
      const address = document.getElementById('reg-address')?.value;

      if (!name || !phone || !password) {
        API.toast('Full Name, Phone number, and Password are required.', 'warning');
        return;
      }
      await this.register(name, phone, email, password, address);
    });
  }
};

window.Auth = Auth;
document.addEventListener('DOMContentLoaded', () => Auth.init());
