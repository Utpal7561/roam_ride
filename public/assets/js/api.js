/**
 * Rome & Ride — API Client
 */

const API = {
  // Base endpoint
  getBaseUrl() {
    const baseEl = document.querySelector('base');
    if (baseEl && baseEl.getAttribute('href')) {
      const baseHref = baseEl.getAttribute('href');
      return baseHref.replace(/\/$/, '') + '/api/v1';
    }
    const loc = window.location;
    const path = loc.pathname.replace(/\/public(\/.*)?$/, '/public');
    return path.replace(/\/$/, '') + '/api/v1';
  },

  getToken() {
    return localStorage.getItem('rnr_token') || null;
  },

  setToken(token) {
    if (token) {
      localStorage.setItem('rnr_token', token);
    } else {
      localStorage.removeItem('rnr_token');
    }
  },

  async request(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : `${this.getBaseUrl()}${endpoint}`;
    const token = this.getToken();

    const headers = {
      'Accept': 'application/json',
      ...(options.headers || {})
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    if (options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      // Handle 429 Rate Limit Exceeded
      if (response.status === 429) {
        const retryAfter = response.headers.get('Retry-After') || 60;
        const msg = `Too many requests! Rate limit exceeded. Please wait ${retryAfter} seconds.`;
        this.toast(msg, 'error');
        throw new Error(msg);
      }

      // Check if download response
      const disposition = response.headers.get('Content-Disposition');
      if (disposition && disposition.includes('attachment')) {
        return response.blob();
      }

      const json = await response.json();

      if (!response.ok) {
        const errMsg = json.message || 'Request failed';
        throw new Error(errMsg);
      }

      return json;
    } catch (err) {
      console.error(`API Error on ${endpoint}:`, err);
      throw err;
    }
  },

  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = query ? `${endpoint}?${query}` : endpoint;
    return this.request(url, { method: 'GET' });
  },

  post(endpoint, data = {}) {
    return this.request(endpoint, { method: 'POST', body: data });
  },

  put(endpoint, data = {}) {
    return this.request(endpoint, { method: 'PUT', body: data });
  },

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  },

  toast(message, type = 'info') {
    let container = document.getElementById('rnr-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'rnr-toast-container';
      container.className = 'fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 max-w-sm pointer-events-none';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColors = {
      success: 'bg-emerald-950 border-emerald-500 text-emerald-200',
      error: 'bg-red-950 border-red-500 text-red-200',
      warning: 'bg-yellow-950 border-yellow-500 text-yellow-200',
      info: 'bg-zinc-900 border-zinc-700 text-white'
    };

    const icons = {
      success: 'bi-check-circle-fill text-emerald-400',
      error: 'bi-exclamation-triangle-fill text-red-400',
      warning: 'bi-exclamation-circle-fill text-yellow-400',
      info: 'bi-info-circle-fill text-yellow-brand'
    };

    toast.className = `pointer-events-auto flex items-center gap-3 p-3.5 rounded-lg border shadow-xl text-sm transition-all duration-300 transform translate-y-3 opacity-0 ${bgColors[type] || bgColors.info}`;
    toast.innerHTML = `
      <i class="bi ${icons[type] || icons.info} text-lg"></i>
      <div class="flex-1 font-medium">${message}</div>
      <button class="opacity-60 hover:opacity-100 ml-2" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
      toast.classList.remove('translate-y-3', 'opacity-0');
    });

    // Auto remove
    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-y-3');
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }
};
