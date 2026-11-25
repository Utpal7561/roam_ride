<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Rome &amp; Ride — Pick. Ride. Explore.</title>
  <meta name="description" content="Premium scooter &amp; vehicle rentals for city exploration, concerts, weekend getaways, and daily commutes.">
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

$heroImage = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_image', 'assets/images/vehicles/activa6g.jpg') : 'assets/images/vehicles/activa6g.jpg';
$heroBadge = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_badge', 'BESTSELLER') : 'BESTSELLER';
$heroTitle = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_title', 'Honda Activa 6G Sport') : 'Honda Activa 6G Sport';
$heroSubtitle = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_subtitle', 'From ₹499 / day (₹49/hr)') : 'From ₹499 / day (₹49/hr)';
$heroTagLeft = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_tagline_left', 'Kamarkuchi Hub (Sonapur)') : 'Kamarkuchi Hub (Sonapur)';
$heroTagRight = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('hero_tagline_right', 'Instant 5-Min Handover') : 'Instant 5-Min Handover';
?>
  <base href="<?= htmlspecialchars($baseHref) ?>">
  <link rel="manifest" href="manifest.json">
  <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
  <link rel="apple-touch-icon" href="assets/images/logo.jpg">

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
<body class="bg-rnr-black text-white antialiased min-h-screen flex flex-col pb-16 md:pb-0">

  <!-- PWA Install Banner -->
  <div id="pwa-install-banner" class="hidden bg-yellow-brand text-black px-4 py-2 text-xs font-bold flex items-center justify-between sticky top-0 z-50">
    <div class="flex items-center gap-2">
      <i class="bi bi-phone-fill text-base"></i>
      <span>Install Rome &amp; Ride App for instant offline bookings!</span>
    </div>
    <div class="flex items-center gap-2">
      <button id="pwa-install-btn" class="bg-black text-yellow-brand px-3 py-1 rounded text-xs uppercase font-extrabold">Install</button>
      <button onclick="document.getElementById('pwa-install-banner').classList.add('hidden')" class="text-black text-base px-1">&times;</button>
    </div>
  </div>

  <!-- Header / Navigation -->
  <header class="sticky top-0 z-40 bg-rnr-black/95 backdrop-blur-md border-b border-zinc-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <!-- Logo Branding -->
      <a href="./" class="flex items-center gap-3">
        <img src="assets/images/logo.jpg" alt="Rome &amp; Ride" class="h-10 w-auto rounded border border-yellow-brand/50">
        <div>
          <span class="text-lg font-black tracking-wider text-white">ROME <span class="text-yellow-brand">&amp; RIDE</span></span>
          <span class="hidden sm:block text-[9px] font-extrabold text-yellow-brand tracking-widest uppercase">Pick • Ride • Explore</span>
        </div>
      </a>

      <!-- Desktop Nav Links -->
      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-zinc-300">
        <a href="#hero-search" class="hover:text-yellow-brand transition-colors">Book a Ride</a>
        <a href="#fleet-section" class="hover:text-yellow-brand transition-colors">Fleet</a>
        <a href="#why-us" class="hover:text-yellow-brand transition-colors">Why Rome &amp; Ride</a>
        <a href="#how-it-works" class="hover:text-yellow-brand transition-colors">How It Works</a>
        <a href="#promotions" class="hover:text-yellow-brand transition-colors">Special Offers</a>
        <a href="#support-section" class="hover:text-yellow-brand transition-colors">Support</a>
      </nav>

      <!-- User & Action Controls -->
      <div class="flex items-center gap-3">
        <!-- Admin Dashboard Link (visible for staff/admin) -->
        <a href="admin" class="auth-admin-link hidden btn-rnr-secondary text-xs px-3 py-1.5 border-yellow-brand text-yellow-brand">
          <i class="bi bi-speedometer2"></i> Admin Panel
        </a>

        <!-- Guest Login Button -->
        <button onclick="Auth.openModal('login')" class="auth-guest-btn btn-rnr-secondary text-xs px-3.5 py-1.5 flex items-center gap-1.5">
          <i class="bi bi-person-fill"></i> Sign In
        </button>

        <!-- Logged In User Account Pill -->
        <div class="auth-user-btn hidden relative group">
          <button onclick="App.openCustomerAccount('dashboard')" class="btn-rnr-secondary text-xs px-3 py-1.5 flex items-center gap-2 border-yellow-brand/40">
            <i class="bi bi-person-circle text-yellow-brand text-sm"></i>
            <span class="auth-user-name font-bold text-white max-w-[100px] truncate">My Account</span>
          </button>
        </div>

        <!-- Quick Ride CTA -->
        <button onclick="BookingWizard.open()" class="btn-rnr-primary text-xs px-3.5 py-2 font-black">
          Book Now
        </button>
      </div>
    </div>
  </header>

  <main class="flex-1">

    <!-- 1. Hero Section -->
    <section class="relative hero-pattern pt-10 pb-16 md:py-20 border-b border-zinc-800 overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
          
          <!-- Hero Text -->
          <div class="lg:col-span-7 space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-yellow-brand/10 border border-yellow-brand/30 text-yellow-brand text-xs font-black uppercase tracking-wider">
              <i class="bi bi-stars"></i> Guwahati's #1 Premium Scooter Rental
            </div>
            
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight tracking-tight">
              Your Ride. <br class="hidden sm:inline">Your Rules. <br>
              <span class="text-yellow-brand underline decoration-red-brand decoration-wavy decoration-2">Your City.</span>
            </h1>

            <p class="text-base sm:text-lg text-zinc-300 max-w-xl font-medium leading-relaxed">
              Pick your scooter. Book your ride. Explore without limits. Transparent daily &amp; hourly rentals with instant digital document delivery to your account.
            </p>

            <div class="flex flex-wrap gap-4 pt-2">
              <a href="#fleet-section" class="btn-rnr-primary px-6 py-3.5 text-sm font-black">
                Explore Vehicles <i class="bi bi-arrow-right"></i>
              </a>
              <button onclick="BookingWizard.open()" class="btn-rnr-secondary px-6 py-3.5 text-sm font-bold">
                Book Your Ride <i class="bi bi-bicycle text-yellow-brand"></i>
              </button>
            </div>

            <!-- Trust badges -->
            <div class="grid grid-cols-3 gap-4 pt-6 border-t border-zinc-800 text-xs">
              <div class="flex items-center gap-2 text-zinc-300">
                <i class="bi bi-shield-check text-yellow-brand text-lg"></i>
                <span>Sanitized Helmets Included</span>
              </div>
              <div class="flex items-center gap-2 text-zinc-300">
                <i class="bi bi-wallet2 text-emerald-400 text-lg"></i>
                <span>Zero Hidden Fees</span>
              </div>
              <div class="flex items-center gap-2 text-zinc-300">
                <i class="bi bi-file-earmark-check text-yellow-brand text-lg"></i>
                <span>Instant Digital Docs</span>
              </div>
            </div>
          </div>

          <!-- Hero Poster & Scooter Display -->
          <div class="lg:col-span-5 relative">
            <div class="relative rnr-card p-4 border-yellow-brand/40 glow-yellow-sm bg-gradient-to-br from-zinc-900 to-black">
              <div class="aspect-[4/3] rounded-xl overflow-hidden bg-zinc-950 relative border border-zinc-800">
                <img id="home-hero-img" src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($heroTitle) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
                  <div>
                    <span id="home-hero-badge" class="badge-status bg-yellow-brand text-black font-extrabold text-[10px]"><?= htmlspecialchars($heroBadge) ?></span>
                    <h3 id="home-hero-title" class="text-xl font-black text-white mt-1"><?= htmlspecialchars($heroTitle) ?></h3>
                    <p id="home-hero-subtitle" class="text-xs text-yellow-brand font-bold"><?= htmlspecialchars($heroSubtitle) ?></p>
                  </div>
                  <button id="home-hero-book-btn" onclick="BookingWizard.open(App.vehicles && App.vehicles.length ? App.vehicles[0] : null)" class="btn-rnr-primary text-xs px-3 py-1.5 font-black uppercase">
                    Book
                  </button>
                </div>
              </div>

              <!-- Quick Tagline Strip -->
              <div class="mt-4 flex items-center justify-between text-xs px-2 text-zinc-400">
                <span id="home-hero-tagleft"><i class="bi bi-geo-alt-fill text-yellow-brand mr-1"></i><?= htmlspecialchars($heroTagLeft) ?></span>
                <span id="home-hero-tagright"><i class="bi bi-clock-history text-yellow-brand mr-1"></i><?= htmlspecialchars($heroTagRight) ?></span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- 2. Quick Booking Search Widget -->
    <section id="hero-search" class="relative -mt-6 z-30 max-w-6xl mx-auto px-4">
      <div class="rnr-card p-6 bg-rnr-surface border-yellow-brand/30 shadow-2xl">
        <form id="hero-booking-search-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
          
          <!-- Pickup Location -->
          <div>
            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
              <i class="bi bi-geo-alt text-yellow-brand mr-1"></i> Pickup Location
            </label>
            <select id="search-pickup-loc" class="rnr-input text-xs font-semibold">
              <!-- Dynamically populated -->
            </select>
          </div>

          <!-- Category -->
          <div>
            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
              <i class="bi bi-grid text-yellow-brand mr-1"></i> Vehicle Category
            </label>
            <select id="search-category-select" class="rnr-input text-xs font-semibold">
              <option value="all">All Ride Categories</option>
            </select>
          </div>

          <!-- Pickup Date -->
          <div>
            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
              <i class="bi bi-calendar-event text-yellow-brand mr-1"></i> Pickup Date
            </label>
            <input type="date" id="search-pickup-date" class="rnr-input text-xs font-semibold">
          </div>

          <!-- Return Date -->
          <div>
            <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
              <i class="bi bi-calendar-check text-yellow-brand mr-1"></i> Return Date
            </label>
            <input type="date" id="search-return-date" class="rnr-input text-xs font-semibold">
          </div>

          <!-- Search Button -->
          <div>
            <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black">
              <i class="bi bi-search"></i> Search Fleet
            </button>
          </div>

        </form>
      </div>
    </section>

    <!-- 3. Featured Scooters Section -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6">
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
        <div>
          <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Top Rated Rides</span>
          <h2 class="text-3xl font-black text-white mt-1">Featured Scooters &amp; Bikes</h2>
        </div>
        <a href="#fleet-section" class="text-xs font-bold text-yellow-brand hover:underline flex items-center gap-1">
          View All Vehicles (${this.vehicles.length || 10}) <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div id="featured-scooters-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Rendered dynamically -->
      </div>
    </section>

    <!-- 4. Concert & Event Travel Promotion Banner (Referencing Poster Style) -->
    <section class="py-8 max-w-7xl mx-auto px-4 sm:px-6">
      <div class="rounded-2xl p-6 md:p-8 bg-gradient-to-r from-red-950/70 via-zinc-900 to-black border border-red-500/30 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 max-w-xl">
          <div class="badge-status bg-red-brand text-white font-black text-[10px] tracking-wider uppercase">
            <i class="bi bi-fire"></i> Concert &amp; Event Special
          </div>
          <h3 class="text-2xl md:text-3xl font-black text-white">Heading to a City Concert or Stadium Match?</h3>
          <p class="text-xs sm:text-sm text-zinc-300">
            Skip stadium cab surge pricing and traffic jams. Rent a Rome &amp; Ride EV or commuter scooter with instant pickup near train stations and bus hubs. Use code <strong class="text-yellow-brand font-mono">CONCERT20</strong> for 20% off!
          </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <button onclick="document.getElementById('bw-promo-input') ? document.getElementById('bw-promo-input').value='CONCERT20' : null; BookingWizard.open();" class="btn-rnr-accent text-xs px-5 py-3 font-bold whitespace-nowrap">
            Claim 20% Off Code <i class="bi bi-ticket-perforated"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- 5. Vehicle Fleet Listing & Filters Section -->
    <section id="fleet-section" class="py-16 border-t border-zinc-800 bg-zinc-950/40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        
        <div class="text-center max-w-2xl mx-auto mb-10">
          <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Our Fleet</span>
          <h2 class="text-3xl sm:text-4xl font-black text-white mt-1">Pick Your Perfect Ride</h2>
          <p class="text-xs sm:text-sm text-zinc-400 mt-2">Every vehicle is fully sanitized, insured, fitted with a mobile mount, and ready for exploration.</p>
        </div>

        <!-- Filter Controls Bar -->
        <div class="space-y-4 mb-8">
          <!-- Category Pills -->
          <div id="category-filter-pills" class="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
            <!-- Rendered dynamically -->
          </div>

          <!-- Secondary Filters Row (Search, Fuel, Price Range, Sort) -->
          <div class="rnr-card p-4 bg-zinc-900/90 flex flex-wrap items-center justify-between gap-4 text-xs">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
              <div class="relative">
                <i class="bi bi-search absolute left-3 top-2.5 text-zinc-500"></i>
                <input type="text" id="filter-search" placeholder="Search by model or brand (e.g. Activa, Vespa, Ather)..." class="rnr-input pl-9 text-xs">
              </div>
            </div>

            <!-- Fuel Type Filter -->
            <div class="w-36">
              <select id="filter-fuel" class="rnr-input text-xs">
                <option value="">All Fuel Types</option>
                <option value="Petrol">Petrol</option>
                <option value="Electric">Electric (EV)</option>
              </select>
            </div>

            <!-- Max Price Slider -->
            <div class="flex items-center gap-3">
              <span class="text-zinc-400 text-xs whitespace-nowrap">Max Price: <strong id="price-range-display" class="text-yellow-brand">₹3000</strong></span>
              <input type="range" id="filter-max-price" min="400" max="3000" step="100" value="3000" class="accent-yellow-400 cursor-pointer w-28">
            </div>

            <!-- Sort dropdown -->
            <div class="w-40">
              <select id="filter-sort" class="rnr-input text-xs">
                <option value="popular">Sort: Most Popular</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="newest">Year: Newest First</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Vehicles Grid -->
        <div id="vehicles-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Rendered dynamically by app.js -->
        </div>

      </div>
    </section>

    <!-- 6. Why Rome & Ride Section -->
    <section id="why-us" class="py-16 max-w-7xl mx-auto px-4 sm:px-6">
      <div class="text-center max-w-xl mx-auto mb-12">
        <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">The Rome &amp; Ride Advantage</span>
        <h2 class="text-3xl font-black text-white mt-1">Why Riders Choose Us</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rnr-card p-6 border-zinc-800 hover:border-yellow-brand transition-all">
          <div class="w-12 h-12 rounded-xl bg-yellow-brand/10 text-yellow-brand flex items-center justify-center text-2xl mb-4">
            <i class="bi bi-clock-history"></i>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">5-Minute Digital Handover</h3>
          <p class="text-xs text-zinc-400 leading-relaxed">No endless paperwork. Upload your license online and grab your keys right after stepping off your train or flight.</p>
        </div>

        <div class="rnr-card p-6 border-zinc-800 hover:border-yellow-brand transition-all">
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl mb-4">
            <i class="bi bi-file-earmark-lock"></i>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">Instant Digital Rental Docs</h3>
          <p class="text-xs text-zinc-400 leading-relaxed">Official rental agreements, RC, and insurance certificates are automatically delivered to your user account for instant roadside access.</p>
        </div>

        <div class="rnr-card p-6 border-zinc-800 hover:border-yellow-brand transition-all">
          <div class="w-12 h-12 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center text-2xl mb-4">
            <i class="bi bi-shield-shaded"></i>
          </div>
          <h3 class="text-lg font-bold text-white mb-2">Flexible Pay Later &amp; Advance</h3>
          <p class="text-xs text-zinc-400 leading-relaxed">Choose between paying just 25% advance to lock your scooter or reserve now with Pay Later. Clear cancellation and refund policies.</p>
        </div>
      </div>
    </section>

    <!-- 7. How Booking Works Section -->
    <section id="how-it-works" class="py-16 bg-zinc-950/60 border-y border-zinc-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-xl mx-auto mb-12">
          <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Simple Workflow</span>
          <h2 class="text-3xl font-black text-white mt-1">How It Works in 4 Steps</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-5 rounded-xl bg-zinc-900 border border-zinc-800 text-center">
            <div class="w-10 h-10 rounded-full bg-yellow-brand text-black font-black text-sm flex items-center justify-center mx-auto mb-3">1</div>
            <h4 class="font-bold text-white text-base mb-1">Pick Your Ride</h4>
            <p class="text-xs text-zinc-400">Browse scooters, sporty maxi-rides, or EVs with verified transparent daily rates.</p>
          </div>
          <div class="p-5 rounded-xl bg-zinc-900 border border-zinc-800 text-center">
            <div class="w-10 h-10 rounded-full bg-yellow-brand text-black font-black text-sm flex items-center justify-center mx-auto mb-3">2</div>
            <h4 class="font-bold text-white text-base mb-1">Upload License</h4>
            <p class="text-xs text-zinc-400">Submit your driving license front and back. Our desk verifies it in minutes.</p>
          </div>
          <div class="p-5 rounded-xl bg-zinc-900 border border-zinc-800 text-center">
            <div class="w-10 h-10 rounded-full bg-yellow-brand text-black font-black text-sm flex items-center justify-center mx-auto mb-3">3</div>
            <h4 class="font-bold text-white text-base mb-1">Choose Payment</h4>
            <p class="text-xs text-zinc-400">Pay 25% advance online via UPI/Card, or reserve with Pay Later option.</p>
          </div>
          <div class="p-5 rounded-xl bg-zinc-900 border border-zinc-800 text-center">
            <div class="w-10 h-10 rounded-full bg-yellow-brand text-black font-black text-sm flex items-center justify-center mx-auto mb-3">4</div>
            <h4 class="font-bold text-white text-base mb-1">Collect &amp; Ride</h4>
            <p class="text-xs text-zinc-400">Grab your keys, sanitized helmets, and view confirmed rental docs in your account!</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 8. Special Offers & Weekend Promos -->
    <section id="promotions" class="py-16 max-w-7xl mx-auto px-4 sm:px-6">
      <div class="text-center max-w-xl mx-auto mb-10">
        <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Ride More For Less</span>
        <h2 class="text-3xl font-black text-white mt-1">Active Promo Coupons</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-zinc-900 border border-dashed border-yellow-brand/50 flex flex-col justify-between">
          <div>
            <span class="badge-status bg-yellow-brand text-black font-black text-[10px]">ALL USERS</span>
            <div class="text-2xl font-black text-yellow-brand mt-2 font-mono">ROMERIDE100</div>
            <h4 class="font-bold text-white text-sm mt-1">Flat ₹100 Off on City Trips</h4>
            <p class="text-xs text-zinc-400 mt-2">Valid on bookings with minimum value of ₹499. Apply coupon during booking step 5.</p>
          </div>
          <button onclick="document.getElementById('bw-promo-input') ? document.getElementById('bw-promo-input').value='ROMERIDE100' : null; BookingWizard.open();" class="mt-4 btn-rnr-secondary text-xs w-full py-2 border-yellow-brand text-yellow-brand">Use Coupon</button>
        </div>

        <div class="p-6 rounded-2xl bg-zinc-900 border border-dashed border-red-500/50 flex flex-col justify-between">
          <div>
            <span class="badge-status bg-red-brand text-white font-black text-[10px]">CONCERTS &amp; EVENTS</span>
            <div class="text-2xl font-black text-red-brand mt-2 font-mono">CONCERT20</div>
            <h4 class="font-bold text-white text-sm mt-1">20% Off Event Rentals</h4>
            <p class="text-xs text-zinc-400 mt-2">Special rate for stadium, concerts, and festival attendees. Valid for full day rentals.</p>
          </div>
          <button onclick="document.getElementById('bw-promo-input') ? document.getElementById('bw-promo-input').value='CONCERT20' : null; BookingWizard.open();" class="mt-4 btn-rnr-secondary text-xs w-full py-2 border-red-500 text-red-400">Use Coupon</button>
        </div>

        <div class="p-6 rounded-2xl bg-zinc-900 border border-dashed border-blue-500/50 flex flex-col justify-between">
          <div>
            <span class="badge-status bg-blue-500 text-white font-black text-[10px]">WEEKENDS</span>
            <div class="text-2xl font-black text-blue-400 mt-2 font-mono">WEEKEND15</div>
            <h4 class="font-bold text-white text-sm mt-1">15% Off Friday to Sunday</h4>
            <p class="text-xs text-zinc-400 mt-2">Getaway discounts for highway riders, scenic routes, and weekend temple visits.</p>
          </div>
          <button onclick="document.getElementById('bw-promo-input') ? document.getElementById('bw-promo-input').value='WEEKEND15' : null; BookingWizard.open();" class="mt-4 btn-rnr-secondary text-xs w-full py-2 border-blue-500 text-blue-400">Use Coupon</button>
        </div>
      </div>
    </section>

    <!-- 9. Customer Reviews -->
    <section class="py-16 bg-zinc-950/40 border-t border-zinc-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-xl mx-auto mb-10">
          <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Real Rider Stories</span>
          <h2 class="text-3xl font-black text-white mt-1">Loved by Explorers</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
            <div class="flex text-yellow-brand text-sm gap-1">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
            <p class="text-xs text-zinc-300 italic leading-relaxed">"Rented the Activa 6G for 2 days. The pickup process was super quick, scooter was spotless and ran like a breeze! Highly recommend Rome &amp; Ride!"</p>
            <div class="font-bold text-white text-xs">— Rahul Verma (Central Station Hub)</div>
          </div>

          <div class="p-6 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
            <div class="flex text-yellow-brand text-sm gap-1">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
            <p class="text-xs text-zinc-300 italic leading-relaxed">"The Ather 450X was fantastic! Warp mode is pure joy in city traffic. Battery range was more than enough for my concert trip."</p>
            <div class="font-bold text-white text-xs">— Priya Bora (Airport Express Hub)</div>
          </div>

          <div class="p-6 rounded-xl bg-zinc-900 border border-zinc-800 space-y-3">
            <div class="flex text-yellow-brand text-sm gap-1">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
            <p class="text-xs text-zinc-300 italic leading-relaxed">"Took the Royal Enfield Hunter 350 to the highway. Super smooth engine, clean handover docs available right in my account. 10/10 service!"</p>
            <div class="font-bold text-white text-xs">— Amitav Goswami (GS Road Hub)</div>
          </div>
        </div>
      </div>
    </section>

    <!-- 10. FAQ Section -->
    <section id="faq" class="py-16 max-w-4xl mx-auto px-4 sm:px-6">
      <div class="text-center mb-10">
        <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Got Questions?</span>
        <h2 class="text-3xl font-black text-white mt-1">Frequently Asked Questions</h2>
      </div>

      <div class="space-y-4 text-xs sm:text-sm">
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <h4 class="font-bold text-white mb-1.5"><i class="bi bi-question-circle text-yellow-brand mr-2"></i> What documents are required to rent a vehicle?</h4>
          <p class="text-zinc-400 leading-relaxed">You only need a valid Government Photo ID (Aadhaar/Passport/Voter ID) and an original Driving License for 2-wheelers. You can upload them directly during the booking process.</p>
        </div>

        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <h4 class="font-bold text-white mb-1.5"><i class="bi bi-question-circle text-yellow-brand mr-2"></i> How do I get my vehicle rental agreement and RC documents?</h4>
          <p class="text-zinc-400 leading-relaxed">Once your booking is approved by staff, all legal documents including the Rental Agreement, Registration Certificate (RC), and Comprehensive Insurance certificate will instantly appear in the <strong>"My Rental Documents"</strong> tab inside your customer account.</p>
        </div>

        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <h4 class="font-bold text-white mb-1.5"><i class="bi bi-question-circle text-yellow-brand mr-2"></i> How does the Pay Later option work?</h4>
          <p class="text-zinc-400 leading-relaxed">If enabled, Pay Later lets you reserve your vehicle without paying immediately. You will be assigned a payment deadline (typically 4 hours prior to pickup) to settle your rental amount online or at the counter.</p>
        </div>

        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <h4 class="font-bold text-white mb-1.5"><i class="bi bi-question-circle text-yellow-brand mr-2"></i> Are helmets and phone mounts provided?</h4>
          <p class="text-zinc-400 leading-relaxed">Yes! Every scooter and bike rental includes sanitized ISI-certified helmets (for rider and pillion) and a sturdy mobile holder for turn-by-turn navigation.</p>
        </div>
      </div>
    </section>

    <!-- 11. Customer Support Inquiry Section -->
    <section id="support-section" class="py-16 bg-zinc-950 border-t border-zinc-800">
      <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="rnr-card p-6 md:p-8 border-zinc-800">
          <div class="text-center mb-8">
            <span class="text-xs font-bold text-yellow-brand uppercase tracking-widest">Customer Support Desk</span>
            <h3 class="text-2xl font-black text-white mt-1">Have a Question or Need Assistance?</h3>
            <p class="text-xs text-zinc-400 mt-1">Our support team is available 24/7. Submit a request or call our emergency hotline.</p>
          </div>

          <form id="support-contact-form" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Full Name *</label>
                <input type="text" id="sup-name" required placeholder="Your name" class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Phone Number *</label>
                <input type="tel" id="sup-phone" required placeholder="+91 98765 00000" class="rnr-input">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Email Address (Optional)</label>
                <input type="email" id="sup-email" placeholder="yourname@gmail.com" class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Inquiry Category</label>
                <select id="sup-cat" class="rnr-input">
                  <option value="general">General Inquiry</option>
                  <option value="booking_issue">Booking Assistance</option>
                  <option value="payment_issue">Payment / Refund Issue</option>
                  <option value="vehicle_issue">Roadside / Vehicle Assistance</option>
                  <option value="document_issue">Document Verification</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block font-bold text-zinc-300 mb-1">Subject *</label>
              <input type="text" id="sup-subject" required placeholder="Brief summary of your inquiry" class="rnr-input">
            </div>

            <div>
              <label class="block font-bold text-zinc-300 mb-1">Message *</label>
              <textarea id="sup-msg" rows="3" required placeholder="How can our operations team help you today?" class="rnr-input"></textarea>
            </div>

            <button type="submit" class="btn-rnr-primary w-full py-3 text-sm font-bold">
              Submit Support Request <i class="bi bi-send-fill"></i>
            </button>
          </form>

          <div class="mt-6 pt-4 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-4 text-xs text-zinc-400">
            <div>
              <i class="bi bi-telephone-fill text-yellow-brand mr-1"></i> Helpline: <a href="tel:+919876543210" class="text-white font-bold hover:underline">+91 98765 43210</a>
            </div>
            <div>
              <i class="bi bi-shield-fill-exclamation text-red-brand mr-1"></i> 24/7 Breakdown: <a href="tel:+919876599999" class="text-yellow-brand font-bold hover:underline">+91 98765 99999</a>
            </div>
            <div>
              <i class="bi bi-envelope-fill text-yellow-brand mr-1"></i> Email: <span class="text-white">support@romeandride.com</span>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="bg-black border-t border-zinc-800/80 py-12 text-zinc-400 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <img src="assets/images/logo.jpg" class="h-8 w-auto rounded border border-yellow-brand/40">
            <span class="text-white font-black text-base">ROME &amp; RIDE</span>
          </div>
          <p class="text-zinc-500 leading-relaxed">Guwahati's high-octane scooter and vehicle rental platform for concerts, daily commutes, and weekend explorations.</p>
          <div class="text-yellow-brand font-extrabold tracking-widest text-[11px]">PICK. RIDE. EXPLORE.</div>
        </div>

        <div>
          <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-[11px]">Quick Links</h4>
          <ul class="space-y-2">
            <li><a href="#fleet-section" class="hover:text-yellow-brand">Available Fleet</a></li>
            <li><a href="#hero-search" class="hover:text-yellow-brand">Quick Booking</a></li>
            <li><a href="#promotions" class="hover:text-yellow-brand">Concert &amp; Weekend Offers</a></li>
            <li><button type="button" onclick="App.openTermsModal()" class="hover:text-yellow-brand text-left">Rental Agreement Terms (14 Rules)</button></li>
            <li><a href="#faq" class="hover:text-yellow-brand">FAQ &amp; Policies</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-[11px]">Hub Locations</h4>
          <ul class="space-y-2 text-zinc-400">
            <li><strong class="text-yellow-brand">Kamarkuchi Hub, Sonapur</strong> (Main Pickup)</li>
            <li>Central Station Hub, Paltan Bazaar</li>
            <li>Airport Express Hub, LGBI Terminal</li>
            <li>City Center Hub, GS Road Dispur</li>
            <li>University Hub, Jalukbari</li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-bold mb-3 uppercase tracking-wider text-[11px]">Staff &amp; Management</h4>
          <p class="text-zinc-500 mb-3">Authorized personnel can access operations, bookings, fleet, and document verification.</p>
          <a href="admin" class="btn-rnr-secondary text-xs w-full py-2 border-yellow-brand text-yellow-brand">
            <i class="bi bi-shield-lock-fill mr-1"></i> Operations Login
          </a>
        </div>
      </div>

      <div class="pt-8 border-t border-zinc-800 text-center text-zinc-600 text-[11px]">
        &copy; 2026 Rome &amp; Ride Operations Ltd. All rights reserved. Built mobile-first.
      </div>
    </div>
  </footer>

  <!-- Mobile Bottom Sticky Navigation (Touch Friendly Minimum 44px) -->
  <div class="md:hidden mobile-sticky-bar flex items-center justify-around text-center text-xs">
    <a href="./" class="flex flex-col items-center gap-1 text-yellow-brand font-bold py-1">
      <i class="bi bi-house-door-fill text-lg"></i>
      <span class="text-[10px]">Home</span>
    </a>
    <a href="#fleet-section" class="flex flex-col items-center gap-1 text-zinc-400 hover:text-white py-1">
      <i class="bi bi-scooter text-lg"></i>
      <span class="text-[10px]">Fleet</span>
    </a>
    <button onclick="BookingWizard.open()" class="flex flex-col items-center gap-1 text-black font-extrabold bg-yellow-brand px-3 py-1.5 rounded-full shadow-lg">
      <i class="bi bi-plus-circle-fill text-lg"></i>
      <span class="text-[10px] leading-tight">Ride</span>
    </button>
    <button onclick="App.openCustomerAccount('documents')" class="flex flex-col items-center gap-1 text-zinc-400 hover:text-white py-1">
      <i class="bi bi-folder-check text-lg"></i>
      <span class="text-[10px]">My Docs</span>
    </button>
    <button onclick="Auth.isLoggedIn() ? App.openCustomerAccount('dashboard') : Auth.openModal('login')" class="flex flex-col items-center gap-1 text-zinc-400 hover:text-white py-1">
      <i class="bi bi-person-fill text-lg"></i>
      <span class="text-[10px]">Account</span>
    </button>
  </div>

  <!-- ================= MODALS ================= -->

  <!-- 1. AUTH MODAL (Login / Register) -->
  <div id="auth-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-md bg-zinc-950 border-yellow-brand/40 shadow-2xl relative">
      <button onclick="Auth.closeModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-xl">&times;</button>
      
      <div class="text-center mb-6">
        <img src="assets/images/logo.jpg" class="h-10 mx-auto rounded border border-yellow-brand/40 mb-2">
        <h3 class="text-xl font-black text-white">Rome &amp; Ride Account</h3>
        <p class="text-xs text-zinc-400">Access your rides, payments, and confirmed rental documents</p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-zinc-800 mb-6">
        <button id="tab-btn-login" class="flex-1 py-2 text-xs font-bold border-b-2 border-yellow-brand text-yellow-brand transition-colors">Sign In</button>
        <button id="tab-btn-register" class="flex-1 py-2 text-xs font-bold border-b-2 border-transparent text-zinc-400 transition-colors">New Rider Register</button>
      </div>

      <!-- Login Form -->
      <div id="login-form-container">
        <form id="form-login" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Phone Number or Email</label>
            <input type="text" id="login-identifier" required placeholder="+91 98765 43220 or rahul@gmail.com" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Password</label>
            <input type="password" id="login-password" required placeholder="••••••••" class="rnr-input">
          </div>
          <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black">
            Sign In to My Account
          </button>
        </form>
        <div class="mt-4 p-3 rounded bg-zinc-900 border border-zinc-800 text-[11px] text-zinc-400">
          <strong class="text-yellow-brand">Demo Customer:</strong> rahul@gmail.com / customer123<br>
          <strong class="text-yellow-brand">Demo Admin:</strong> admin@romeandride.com / admin123
        </div>
      </div>

      <!-- Register Form -->
      <div id="register-form-container" class="hidden">
        <form id="form-register" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Full Name *</label>
            <input type="text" id="reg-name" required placeholder="John Doe" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Phone Number *</label>
            <input type="tel" id="reg-phone" required placeholder="+91 98765 00000" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Email Address (Optional)</label>
            <input type="email" id="reg-email" placeholder="john@example.com" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Create Password * (min 6 chars)</label>
            <input type="password" id="reg-password" required minlength="6" placeholder="••••••••" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Address / City</label>
            <input type="text" id="reg-address" placeholder="Guwahati, Assam" class="rnr-input">
          </div>
          <button type="submit" class="btn-rnr-primary w-full py-3 text-xs font-black mt-2">
            Create Rider Account
          </button>
        </form>
      </div>

    </div>
  </div>

  <!-- 2. VEHICLE DETAILS MODAL -->
  <div id="vehicle-details-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-4xl bg-zinc-950 border-zinc-800 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button onclick="App.closeVehicleDetails()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      <div id="vehicle-details-content">
        <!-- Rendered dynamically -->
      </div>
    </div>
  </div>

  <!-- 3. MULTI-STEP BOOKING WIZARD (STEPS 1 - 7) -->
  <div id="booking-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-3xl bg-zinc-950 border-yellow-brand/40 shadow-2xl relative max-h-[92vh] overflow-y-auto flex flex-col justify-between">
      
      <!-- Modal Header & Stepper Indicators -->
      <div class="border-b border-zinc-800 pb-4 mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <span class="badge-status bg-yellow-brand text-black font-black text-[10px]">BOOKING FLOW</span>
            <h3 class="text-lg font-black text-white">Reserve Your Scooter</h3>
          </div>
          <button onclick="BookingWizard.close()" class="text-zinc-400 hover:text-white text-2xl">&times;</button>
        </div>

        <!-- 7 Step Indicators -->
        <div class="flex items-center justify-between relative px-2 text-xs overflow-x-auto">
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-1" class="w-7 h-7 rounded-full bg-yellow-brand text-black font-black flex items-center justify-center text-xs">1</div>
            <span class="text-[9px] text-zinc-400">Vehicle</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-2" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">2</div>
            <span class="text-[9px] text-zinc-400">Dates</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-3" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">3</div>
            <span class="text-[9px] text-zinc-400">Rider</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-4" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">4</div>
            <span class="text-[9px] text-zinc-400">License</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-5" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">5</div>
            <span class="text-[9px] text-zinc-400">Summary</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-6" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">6</div>
            <span class="text-[9px] text-zinc-400">Payment</span>
          </div>
          <div class="h-0.5 w-6 bg-zinc-800"></div>
          <div class="flex flex-col items-center gap-1">
            <div id="bw-indicator-7" class="w-7 h-7 rounded-full bg-zinc-800 text-zinc-400 font-bold flex items-center justify-center text-xs">7</div>
            <span class="text-[9px] text-zinc-400">Done</span>
          </div>
        </div>
      </div>

      <!-- Step 1: Select Vehicle -->
      <div id="bw-step-1" class="space-y-4">
        <h4 class="font-bold text-white text-base">Step 1 — Choose Your Scooter</h4>
        <div id="bw-vehicles-list" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[50vh] overflow-y-auto pr-1">
          <!-- Rendered dynamically -->
        </div>
      </div>

      <!-- Step 2: Select Rental Period -->
      <div id="bw-step-2" class="hidden space-y-4 text-xs">
        <h4 class="font-bold text-white text-base">Step 2 — Select Rental Period &amp; Pickup Hub</h4>
        
        <div id="bw-selected-vehicle-card"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Pickup Date *</label>
            <input type="date" id="bw-pickup-date" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Pickup Time *</label>
            <input type="time" id="bw-pickup-time" value="10:00" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Return Date *</label>
            <input type="date" id="bw-return-date" class="rnr-input">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Return Time *</label>
            <input type="time" id="bw-return-time" value="18:00" class="rnr-input">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Pickup Hub *</label>
            <select id="bw-pickup-loc" class="rnr-input"></select>
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Return Hub</label>
            <select id="bw-return-loc" class="rnr-input"></select>
          </div>
        </div>

        <div class="flex justify-between pt-4 border-t border-zinc-800">
          <button onclick="BookingWizard.goToStep(1)" class="btn-rnr-secondary text-xs px-4">Back</button>
          <button onclick="BookingWizard.goToStep(3)" class="btn-rnr-primary text-xs px-6">Continue to Rider Info <i class="bi bi-arrow-right"></i></button>
        </div>
      </div>

      <!-- Step 3: Customer Information -->
      <div id="bw-step-3" class="hidden space-y-4 text-xs">
        <h4 class="font-bold text-white text-base">Step 3 — Customer Information</h4>
        <p class="text-zinc-400">Phone number is required for ride SMS and OTP pickup verification. Email is optional.</p>

        <div class="space-y-4 p-4 rounded-xl bg-zinc-900 border border-zinc-800">
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Full Legal Name * (REQUIRED)</label>
            <input type="text" id="bw-cust-name" required placeholder="Name as per Driving License" class="rnr-input text-xs">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Phone Number * (REQUIRED)</label>
            <input type="tel" id="bw-cust-phone" required placeholder="+91 98765 00000" class="rnr-input text-xs">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Email Address (OPTIONAL — Does not block booking)</label>
            <input type="email" id="bw-cust-email" placeholder="rider@gmail.com" class="rnr-input text-xs">
          </div>
          <div>
            <label class="block font-bold text-zinc-300 mb-1">Trip Notes or Special Requests</label>
            <input type="text" id="bw-cust-notes" placeholder="e.g. Planning a concert trip, need 2 extra helmets" class="rnr-input text-xs">
          </div>
        </div>

        <div class="flex justify-between pt-4 border-t border-zinc-800">
          <button onclick="BookingWizard.goToStep(2)" class="btn-rnr-secondary text-xs px-4">Back</button>
          <button onclick="BookingWizard.goToStep(4)" class="btn-rnr-primary text-xs px-6">Continue to Document Upload <i class="bi bi-arrow-right"></i></button>
        </div>
      </div>

      <!-- Step 4: Driving License Documents -->
      <div id="bw-step-4" class="hidden space-y-4 text-xs">
        <h4 class="font-bold text-white text-base">Step 4 — Driving License Documents</h4>
        <p class="text-zinc-400">Supported formats: JPG, PNG, PDF. Max 5MB per file. (You can also upload later from your account if not immediately handy).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- License Front -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-white">License (Front Side)</span>
                <span id="bw-license-front-status" class="badge-status badge-pending">Required</span>
              </div>
              <p class="text-[11px] text-zinc-400 mb-3">Clear photo showing your name, photo, and 2-wheeler vehicle endorsement.</p>
            </div>
            <input type="file" id="bw-doc-front-file" accept="image/jpeg,image/png,application/pdf" class="rnr-input text-xs cursor-pointer">
          </div>

          <!-- License Back -->
          <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-white">License (Back Side)</span>
                <span id="bw-license-back-status" class="badge-status badge-pending">Required</span>
              </div>
              <p class="text-[11px] text-zinc-400 mb-3">Clear photo showing address and issuing authority endorsement.</p>
            </div>
            <input type="file" id="bw-doc-back-file" accept="image/jpeg,image/png,application/pdf" class="rnr-input text-xs cursor-pointer">
          </div>
        </div>

        <div class="p-3 rounded-lg bg-yellow-brand/10 border border-yellow-brand/30 text-yellow-brand text-xs">
          <i class="bi bi-info-circle-fill mr-1"></i> Staff will verify your license before vehicle handover. If documents are rejected, you can easily re-upload them in your customer portal.
        </div>

        <div class="flex justify-between pt-4 border-t border-zinc-800">
          <button onclick="BookingWizard.goToStep(3)" class="btn-rnr-secondary text-xs px-4">Back</button>
          <button onclick="BookingWizard.goToStep(5)" class="btn-rnr-primary text-xs px-6">Proceed to Summary <i class="bi bi-arrow-right"></i></button>
        </div>
      </div>

      <!-- Step 5: Booking Summary & Promo -->
      <div id="bw-step-5" class="hidden space-y-4 text-xs">
        <h4 class="font-bold text-white text-base">Step 5 — Rental Summary &amp; Price Breakdown</h4>

        <!-- Summary Details Card -->
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 space-y-2.5">
          <div class="flex justify-between text-white font-bold pb-2 border-b border-zinc-800">
            <span id="bw-sum-vehicle-name">Vehicle</span>
            <span id="bw-sum-duration" class="text-yellow-brand">Duration</span>
          </div>
          <div class="flex justify-between text-zinc-400">
            <span>Pickup:</span>
            <span id="bw-sum-pickup" class="text-white"></span>
          </div>
          <div class="flex justify-between text-zinc-400">
            <span>Return:</span>
            <span id="bw-sum-return" class="text-white"></span>
          </div>
          <div class="flex justify-between text-zinc-400 pt-2 border-t border-zinc-800/60">
            <span>Rental Charges:</span>
            <span id="bw-sum-rental-charge" class="text-white font-semibold"></span>
          </div>
          <div class="flex justify-between text-zinc-400">
            <span>Refundable Security Deposit:</span>
            <span id="bw-sum-deposit" class="text-white font-semibold"></span>
          </div>
          <div class="flex justify-between text-emerald-400">
            <span>Coupon Discount:</span>
            <span id="bw-sum-discount" class="font-semibold">₹0.00</span>
          </div>
          <div class="flex justify-between text-zinc-400">
            <span>GST / Taxes (5%):</span>
            <span id="bw-sum-tax" class="text-white font-semibold"></span>
          </div>
          <div class="flex justify-between text-base font-black text-white pt-2 border-t border-zinc-800">
            <span>Total Booking Value:</span>
            <span id="bw-sum-total" class="text-yellow-brand text-lg"></span>
          </div>
          <div class="flex justify-between text-xs text-zinc-400 pt-1">
            <span>Required Advance (25%):</span>
            <span id="bw-sum-advance-payable" class="text-emerald-400 font-bold"></span>
          </div>
          <div class="flex justify-between text-xs text-zinc-400">
            <span>Remaining at Pickup:</span>
            <span id="bw-sum-remaining" class="text-yellow-brand font-bold"></span>
          </div>
        </div>

        <!-- Promo Code Input -->
        <div class="flex gap-2">
          <input type="text" id="bw-promo-input" placeholder="Have a coupon? Try ROMERIDE100, CONCERT20" class="rnr-input uppercase font-mono text-xs">
          <button onclick="BookingWizard.applyPromoCode()" class="btn-rnr-secondary text-xs px-4 whitespace-nowrap">Apply Code</button>
        </div>

        <!-- Terms & Conditions Acknowledgement Box -->
        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg text-xs space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="font-bold text-white flex items-center gap-1"><i class="bi bi-file-earmark-ruled text-yellow-brand"></i> Rental Terms &amp; Policies</span>
            <button type="button" onclick="App.openTermsModal()" class="text-yellow-brand underline font-semibold hover:text-yellow-300 text-[11px]">View 14 Terms &amp; Conditions</button>
          </div>
          <label class="flex items-start gap-2 cursor-pointer mt-1 text-[11px] text-zinc-300">
            <input type="checkbox" id="bw-terms-agree" checked class="mt-0.5 accent-yellow-400">
            <span>I acknowledge and agree to the <strong>Roam &amp; Ride Important Rental Terms &amp; Conditions</strong> (including refundable ₹1,000 security deposit, fuel handover policy, and traffic safety guidelines).</span>
          </label>
        </div>

        <div class="flex justify-between pt-4 border-t border-zinc-800">
          <button onclick="BookingWizard.goToStep(4)" class="btn-rnr-secondary text-xs px-4">Back</button>
          <button onclick="BookingWizard.goToStep(6)" class="btn-rnr-primary text-xs px-6">Payment Options <i class="bi bi-arrow-right"></i></button>
        </div>
      </div>

      <!-- Step 6: Offline Payment & Confirmation Method -->
      <div id="bw-step-6" class="hidden space-y-4 text-xs">
        <div>
          <span class="badge-status bg-emerald-500 text-black font-black text-[10px]">OFFLINE VERIFICATION</span>
          <h4 class="font-bold text-white text-base mt-1">Step 6 — Offline Payment &amp; Confirmation</h4>
          <p class="text-zinc-400">All payments are offline and verified &amp; recorded directly by staff from the Admin Operations Center.</p>
        </div>

        <div class="rnr-card p-5 bg-zinc-900 border-yellow-brand/40 space-y-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl shrink-0">
              <i class="bi bi-whatsapp"></i>
            </div>
            <div class="flex-1">
              <h5 class="font-extrabold text-white text-sm">Official WhatsApp Desk (+91 9395485880)</h5>
              <p class="text-zinc-300 text-xs mt-1">
                Clicking confirm below records your ride reservation and redirects you directly to our official WhatsApp helpline with your vehicle details, rental schedule, and pricing.
              </p>
            </div>
          </div>

          <div class="p-3.5 rounded-xl bg-black/60 border border-zinc-800 space-y-2 text-xs">
            <div class="flex items-center justify-between text-zinc-300">
              <span><i class="bi bi-check-circle-fill text-yellow-brand mr-1"></i> Payment Mode:</span>
              <strong class="text-white">Offline (WhatsApp UPI QR Code / Cash at Counter)</strong>
            </div>
            <div class="flex items-center justify-between text-zinc-300">
              <span><i class="bi bi-check-circle-fill text-yellow-brand mr-1"></i> Admin Processing:</span>
              <strong class="text-yellow-brand">Recorded &amp; Confirmed from Admin site</strong>
            </div>
            <div class="flex items-center justify-between text-zinc-300">
              <span><i class="bi bi-check-circle-fill text-yellow-brand mr-1"></i> Security Deposit:</span>
              <strong class="text-white">Refundable upon vehicle return &amp; inspection</strong>
            </div>
          </div>

          <div class="text-[11px] text-zinc-400 bg-zinc-950 p-3 rounded-lg border border-zinc-800/80">
            <i class="bi bi-shield-lock-fill text-yellow-brand mr-1"></i>
            <strong>Offline Payment Policy:</strong> Online self-payment is disabled. Once your payment is verified offline by our staff, your booking status and rental documents will be immediately updated in your customer account.
          </div>
        </div>

        <div class="flex justify-between pt-4 border-t border-zinc-800">
          <button onclick="BookingWizard.goToStep(5)" class="btn-rnr-secondary text-xs px-4">Back</button>
          <button id="bw-confirm-booking-btn" onclick="BookingWizard.submitFinalBooking()" class="btn-rnr-primary text-xs px-6 font-black py-3 flex items-center gap-2 shadow-lg">
            Confirm Ride &amp; Connect on WhatsApp <i class="bi bi-whatsapp text-sm"></i>
          </button>
        </div>
      </div>

      <!-- Step 7: Confirmation & Receipt -->
      <div id="bw-step-7" class="hidden space-y-4 text-xs text-center py-4">
        <div class="w-16 h-16 rounded-full bg-yellow-500/10 text-yellow-brand flex items-center justify-center text-3xl mx-auto border border-yellow-brand/30">
          <i class="bi bi-hourglass-split"></i>
        </div>

        <h3 class="text-2xl font-black text-white">Booking Request Received</h3>
        <div class="inline-block px-3.5 py-1 bg-yellow-brand/10 border border-yellow-brand/30 text-yellow-brand rounded-full font-bold text-xs tracking-wider uppercase mb-1">
          Status: Pending Approval
        </div>
        <p class="text-zinc-400">Your ride booking is recorded and is currently <strong class="text-yellow-brand">Pending Approval</strong> until verified and confirmed by our operations team.</p>

        <!-- WhatsApp Redirection Action Card -->
        <div class="p-4 rounded-xl bg-gradient-to-r from-emerald-950/80 to-zinc-900 border border-emerald-500/50 text-left max-w-md mx-auto space-y-2.5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-emerald-400 font-extrabold text-sm">
              <i class="bi bi-whatsapp text-xl"></i>
              <span>Next Step: Complete Payment via WhatsApp</span>
            </div>
            <span class="badge-status bg-emerald-500 text-black font-black text-[9px]">STEP 2 OF 2</span>
          </div>
          <p class="text-zinc-300 text-xs leading-relaxed">
            Send your pre-filled booking details to our official WhatsApp helpline (<strong>9395485880</strong>) to receive the official UPI QR code &amp; confirm your ride.
          </p>
          <a id="bw-conf-whatsapp-btn" href="https://wa.me/919395485880" target="_blank" class="btn-rnr-primary bg-emerald-500 hover:bg-emerald-600 text-black font-black flex items-center justify-center gap-2 py-3 px-4 rounded-lg text-xs w-full shadow-lg">
            <i class="bi bi-whatsapp text-lg"></i> Continue to WhatsApp Payment (+91 9395485880)
          </a>
        </div>

        <!-- Booking Details Receipt Box -->
        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 text-left space-y-2 max-w-md mx-auto">
          <div class="flex justify-between border-b border-zinc-800 pb-2">
            <span class="text-zinc-400 font-semibold">Booking ID:</span>
            <span id="bw-conf-code" class="font-mono text-yellow-brand font-black text-sm"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Vehicle:</span>
            <span id="bw-conf-vehicle" class="text-white font-bold"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Schedule:</span>
            <span id="bw-conf-dates" class="text-white font-medium"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Pickup Location:</span>
            <span class="text-white font-semibold">Kamarkuchi, Sonapur</span>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-zinc-400">Location Map:</span>
            <a href="https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic" target="_blank" class="text-yellow-brand underline font-bold flex items-center gap-1">
              <i class="bi bi-geo-alt-fill"></i> Open Google Maps
            </a>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Total Value:</span>
            <span id="bw-conf-total" class="text-white font-bold"></span>
          </div>
          <div class="flex justify-between text-yellow-brand">
            <span>Security Deposit:</span>
            <span>₹1,000 (Refundable upon inspection)</span>
          </div>
          <div class="flex justify-between text-emerald-400 font-bold">
            <span>Amount Paid:</span>
            <span id="bw-conf-paid"></span>
          </div>
          <div class="flex justify-between text-yellow-brand font-bold">
            <span>Remaining Balance:</span>
            <span id="bw-conf-balance"></span>
          </div>
          <div class="flex justify-between border-t border-zinc-800 pt-2">
            <span class="text-zinc-400">Booking Status:</span>
            <span id="bw-conf-status" class="badge-status badge-confirmed"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Document Status:</span>
            <span id="bw-conf-docs-status" class="badge-status badge-pending"></span>
          </div>
          <div id="bw-conf-deadline-wrap" class="hidden flex justify-between text-red-400 border-t border-zinc-800 pt-2">
            <span>Payment Deadline:</span>
            <span id="bw-conf-deadline" class="font-bold"></span>
          </div>
        </div>

        <!-- Document Delivery Notice -->
        <div class="p-3.5 rounded-lg bg-zinc-900 border border-yellow-brand/30 text-xs text-zinc-300 max-w-md mx-auto text-left">
          <strong class="text-yellow-brand flex items-center gap-1.5 mb-1"><i class="bi bi-file-earmark-check-fill"></i> Rental Agreement Delivery:</strong>
          Upon staff verification and payment confirmation, your official <strong>14-Clause Rental Agreement</strong> will be automatically attached to your customer account for viewing and download.
        </div>

        <div class="pt-4 flex justify-center gap-3">
          <button onclick="BookingWizard.close(); App.openCustomerAccount('bookings');" class="btn-rnr-primary text-xs px-6 py-2.5 font-bold">
            View in Customer Account <i class="bi bi-arrow-right"></i>
          </button>
          <button onclick="BookingWizard.close()" class="btn-rnr-secondary text-xs px-4 py-2.5">Close</button>
        </div>
      </div>

    </div>
  </div>

  <!-- 4. CUSTOMER ACCOUNT PORTAL (SECTION 7 & 8) -->
  <div id="customer-account-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-5xl bg-zinc-950 border-zinc-800 shadow-2xl relative max-h-[92vh] overflow-y-auto">
      <button onclick="App.closeCustomerAccount()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      
      <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
        <div>
          <span class="badge-status bg-yellow-brand text-black font-black text-[10px]">CUSTOMER PORTAL</span>
          <h2 class="text-2xl font-black text-white mt-1">My Rider Account</h2>
        </div>
        <button onclick="Auth.logout()" class="btn-rnr-secondary text-xs px-3 py-1.5 text-red-400 hover:border-red-500">
          <i class="bi bi-box-arrow-right mr-1"></i> Sign Out
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <div class="space-y-2">
          <button id="acc-tab-dashboard" onclick="App.switchAccountTab('dashboard')" class="w-full text-left px-4 py-3 rounded-lg text-sm font-bold bg-yellow-brand text-black flex items-center gap-3">
            <i class="bi bi-speedometer2"></i> Dashboard Overview
          </button>
          <button id="acc-tab-bookings" onclick="App.switchAccountTab('bookings')" class="w-full text-left px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 flex items-center gap-3">
            <i class="bi bi-bicycle"></i> My Bookings
          </button>
          <button id="acc-tab-documents" onclick="App.switchAccountTab('documents')" class="w-full text-left px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 flex items-center gap-3">
            <i class="bi bi-folder-check text-yellow-brand"></i> My Rental Documents
          </button>
          <button id="acc-tab-profile" onclick="App.switchAccountTab('profile')" class="w-full text-left px-4 py-3 rounded-lg text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-800/60 flex items-center gap-3">
            <i class="bi bi-person-gear"></i> Profile &amp; Password
          </button>
        </div>

        <!-- Content Area -->
        <div class="md:col-span-3">
          
          <!-- Tab 1: Dashboard Overview -->
          <div id="acc-content-dashboard" class="space-y-6">
            <!-- Summary Stats Counters -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-center">
              <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800">
                <span class="text-xs text-zinc-500 font-semibold block">Total Bookings</span>
                <span id="acc-stat-total" class="text-2xl font-black text-white">0</span>
              </div>
              <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800">
                <span class="text-xs text-zinc-500 font-semibold block">Upcoming</span>
                <span id="acc-stat-upcoming" class="text-2xl font-black text-yellow-brand">0</span>
              </div>
              <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800">
                <span class="text-xs text-zinc-500 font-semibold block">Active Rental</span>
                <span id="acc-stat-active" class="text-2xl font-black text-blue-400">0</span>
              </div>
              <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800">
                <span class="text-xs text-zinc-500 font-semibold block">Pending Pay</span>
                <span id="acc-stat-pending-pay" class="text-2xl font-black text-red-400">0</span>
              </div>
              <div class="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800">
                <span class="text-xs text-zinc-500 font-semibold block">Completed</span>
                <span id="acc-stat-completed" class="text-2xl font-black text-emerald-400">0</span>
              </div>
            </div>

            <!-- Recent Bookings List -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-white text-base">Recent Ride Bookings</h4>
                <button onclick="BookingWizard.open()" class="btn-rnr-primary text-xs px-3 py-1.5 font-bold">New Booking</button>
              </div>
              <div id="acc-bookings-list" class="space-y-3">
                <!-- Rendered dynamically -->
              </div>
            </div>
          </div>

          <!-- Tab 2: Bookings -->
          <div id="acc-content-bookings" class="hidden space-y-4">
            <h4 class="font-bold text-white text-base">Booking History &amp; Schedules</h4>
            <div id="acc-all-bookings-list" class="space-y-3">
              <!-- Rendered dynamically -->
            </div>
          </div>

          <!-- Tab 3: CRITICAL SECTION 8 — MY RENTAL DOCUMENTS -->
          <div id="acc-content-documents" class="hidden space-y-4">
            <div class="p-4 rounded-xl bg-gradient-to-r from-yellow-950/40 to-zinc-900 border border-yellow-brand/30 flex items-center justify-between">
              <div>
                <h4 class="font-black text-white text-base flex items-center gap-2">
                  <i class="bi bi-shield-check text-yellow-brand"></i> My Rental Documents
                </h4>
                <p class="text-xs text-zinc-300 mt-1">Confirmed vehicle agreements, booking confirmations, RC, and insurance certificates for your bookings.</p>
              </div>
            </div>

            <!-- Documents List -->
            <div id="acc-documents-list" class="space-y-3">
              <!-- Rendered dynamically -->
            </div>
          </div>

          <!-- Tab 4: Profile Settings -->
          <div id="acc-content-profile" class="hidden space-y-4">
            <h4 class="font-bold text-white text-base">Update Profile &amp; Security</h4>
            <form id="acc-profile-form" class="space-y-4 text-xs max-w-md">
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Full Legal Name</label>
                <input type="text" id="acc-prof-name" required class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Phone Number</label>
                <input type="tel" id="acc-prof-phone" required class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Email Address</label>
                <input type="email" id="acc-prof-email" class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Address / City</label>
                <input type="text" id="acc-prof-address" class="rnr-input">
              </div>
              <div>
                <label class="block font-bold text-zinc-300 mb-1">Change Password (leave blank to keep current)</label>
                <input type="password" id="acc-prof-pass" placeholder="••••••••" class="rnr-input">
              </div>
              <button type="submit" class="btn-rnr-primary text-xs font-bold py-2.5 px-6">Save Profile</button>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- 5. INDIVIDUAL BOOKING DETAILS MODAL -->
  <div id="booking-details-modal" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-2xl bg-zinc-950 border-zinc-800 shadow-2xl relative max-h-[90vh] overflow-y-auto">
      <button onclick="document.getElementById('booking-details-modal').classList.add('hidden')" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      <div id="booking-details-modal-content">
        <!-- Rendered dynamically -->
      </div>
    </div>
  </div>

  <!-- 6. OFFICIAL RENTAL AGREEMENT TERMS & CONDITIONS MODAL -->
  <div id="modal-rental-terms" class="modal-backdrop hidden">
    <div class="rnr-card p-6 w-full max-w-3xl bg-zinc-950 border-yellow-brand/50 shadow-2xl relative max-h-[90vh] overflow-y-auto text-xs">
      <button onclick="App.closeTermsModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white text-2xl z-20">&times;</button>
      
      <div class="border-b border-zinc-800 pb-3 mb-4">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-yellow-brand font-black text-lg">ROAM &amp; RIDE</span>
          <span class="text-white text-xs font-bold tracking-wider">• PICK . RIDE . EXPLORE.</span>
        </div>
        <h3 class="text-base font-black text-white">IMPORTANT RENTAL TERMS &amp; CONDITIONS</h3>
        <p class="text-red-400 text-xs font-semibold">PLEASE READ BEFORE RIDING</p>
      </div>

      <div class="space-y-3.5 text-zinc-300 leading-relaxed max-h-[60vh] overflow-y-auto pr-2">
        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">1. RENTAL PERIOD.</strong> One rental day is 24 consecutive hours from the pickup time recorded on the front of this receipt. The scooter must be returned by the agreed date, time and location. Any extension requires prior approval from Roam&amp;Ride and may attract additional charges.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">2. RETURN GRACE PERIOD &amp; LATE FEE.</strong> A 30-minute grace period applies after the scheduled return time. After this period, the late-return charge agreed at booking applies. The applicable rate must be written on the front of the receipt or in the signed rental agreement. Keeping the scooter beyond the agreed time does not automatically extend the booking.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">3. RENTAL PAYMENT &amp; SECURITY DEPOSIT.</strong> Rental charges are payable as agreed at booking. A refundable security deposit of ₹1,000 is collected separately from the rental charge. After the scooter and accessories are returned and inspected, documented outstanding amounts may be deducted and explained to the renter. The remaining undisputed deposit balance will be refunded within the timeline communicated at booking.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">4. FUEL POLICY.</strong> The fuel level will be recorded at handover. The scooter must be returned with the same fuel level. Any shortfall may be charged at the reasonable, documented cost of restoring the recorded level.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">5. LICENCE &amp; AUTHORISED RIDER.</strong> The renter must provide a genuine, current and valid driving licence authorising them to ride the relevant vehicle class and must meet all legal eligibility requirements. Only the named renter, or another rider expressly approved in writing by Roam&amp;Ride and legally qualified, may ride the scooter. The scooter must not be lent, sub-rented or transferred to another person.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">6. SAFE &amp; LAWFUL USE.</strong> The renter must obey all traffic laws, speed limits, parking rules and road-safety requirements and use legally required helmets and safety equipment. Riding under the influence of alcohol or drugs, racing, stunts, overloading, carrying more passengers than legally permitted, using the scooter for unlawful activities or unauthorised commercial use is prohibited.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">7. TRAFFIC FINES &amp; PENALTIES.</strong> The renter is responsible for traffic challans, parking fines, tolls and penalties arising from their use or possession of the scooter during the rental period, including notices received after return that relate to that period. Roam&amp;Ride may share relevant booking details with authorities when legally required.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">8. ACCIDENT, DAMAGE, BREAKDOWN OR THEFT.</strong> The renter must promptly inform Roam&amp;Ride of any accident, damage, breakdown, fire, theft or attempted theft. Contact police or emergency services wherever required, obtain an incident reference where applicable, and cooperate with lawful and insurance procedures. Do not abandon the scooter or arrange repairs without approval except where immediate action is necessary for safety. Responsibility for loss, damage, insurance excess or uninsured amounts will be assessed based on the facts, applicable law and relevant insurance terms; liability is not imposed where prohibited by law.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">9. VEHICLE CARE &amp; CONDITION.</strong> The renter must inspect the scooter before accepting it and ensure existing scratches, dents, defects, fuel level, odometer reading and supplied accessories are recorded. Use reasonable care, park securely and report faults promptly. Stop riding if the scooter appears unsafe. No modification, tampering or unauthorised repair is permitted.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">10. RETURN &amp; ACCESSORIES.</strong> Return the scooter to the agreed location by the agreed time, with the same recorded fuel level and all supplied keys, helmets, documents and accessories. Roam&amp;Ride and the renter should jointly inspect the scooter at return and record any new damage, missing items, fuel shortfall or other agreed charges. The renter should keep a copy or photograph of the completed return record.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">11. OPERATING AREA &amp; TRACKING.</strong> Use the scooter only within the area agreed at booking. Inter-state travel or travel outside the approved area requires prior written permission and compliance with applicable legal requirements. If a GPS/tracking device is installed, location information may be used for fleet security, safety, recovery and lawful operational purposes, subject to applicable privacy law.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">12. CANCELLATION, EARLY RETURN &amp; NON-RETURN.</strong> Any cancellation, rescheduling or early-return refund terms must be communicated and agreed at booking. Failure to return the scooter, unauthorised retention, concealment or transfer may be reported to the appropriate authorities and addressed through lawful procedures.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">13. PERSONAL BELONGINGS &amp; CUSTOMER INFORMATION.</strong> The renter is responsible for personal belongings left in or on the scooter, except where liability is imposed by law. Customer identity, licence and contact information will be used for rental verification, administration, safety, insurance, legal compliance and recovery purposes, subject to applicable privacy requirements.
        </div>

        <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-lg">
          <strong class="text-yellow-brand font-bold">14. AGREEMENT &amp; DISPUTES.</strong> The front of this receipt, these terms and conditions, and any signed rental agreement and vehicle inspection report form the booking record. Any handwritten change must be acknowledged by both parties. The parties will first attempt to resolve concerns in good faith. Applicable Indian law and the courts having competent jurisdiction will apply. Nothing here excludes rights or liabilities that cannot legally be excluded.
        </div>

        <div class="p-4 bg-yellow-brand/10 border border-yellow-brand/40 rounded-lg">
          <div class="font-extrabold text-yellow-brand text-xs mb-1">RENTER ACKNOWLEDGEMENT</div>
          <p class="text-[11px] text-zinc-300">
            By booking through our platform or accepting vehicle keys, you confirm that you have read and understood these terms and conditions and agree to comply with them, together with the booking details and the signed rental agreement.
          </p>
          <div class="mt-3 pt-2 border-t border-yellow-brand/20 flex flex-wrap justify-between items-center gap-2 text-[11px]">
            <div><strong>Roam&amp;Ride WhatsApp:</strong> <a href="https://wa.me/919395485880" target="_blank" class="text-emerald-400 font-bold">9395485880</a></div>
            <div><strong>Pickup point:</strong> Kamarkuchi, Sonapur</div>
            <div><a href="https://maps.app.goo.gl/m8W3cwX7uiGEQok28?g_st=ic" target="_blank" class="text-yellow-brand underline font-bold">📍 Google Maps Link</a></div>
          </div>
        </div>
      </div>

      <div class="flex justify-between items-center pt-4 border-t border-zinc-800 mt-4">
        <a href="https://wa.me/919395485880?text=Hi%2C%20I%20have%20a%20question%20about%20Roam%20%26%20Ride%20rental%20terms" target="_blank" class="btn-rnr-secondary text-xs px-4 py-2 text-emerald-400 border-emerald-500/40 hover:bg-emerald-950/30">
          <i class="bi bi-whatsapp mr-1"></i> WhatsApp Support (9395485880)
        </a>
        <button onclick="App.closeTermsModal()" class="btn-rnr-primary text-xs px-6 py-2">Close &amp; Accept</button>
      </div>
    </div>
  </div>

  <!-- JavaScript Scripts -->
  <script src="assets/js/api.js"></script>
  <script src="assets/js/auth.js"></script>
  <script src="assets/js/booking.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
