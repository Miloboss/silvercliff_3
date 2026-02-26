<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ $package->title }} — Silver Cliff Resort</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- AOS (scroll animations) -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet" />

  <!-- Your styles -->
  <link rel="stylesheet" href="/simple_web_ui/styles.css" />
  <style>
    .package-hero {
        position: relative;
        padding: 140px 0 80px;
        background-image: linear-gradient(to bottom, rgba(5, 10, 4, 0.7), rgba(5, 10, 4, 0.9)), url('{{ $package->image_url ?: "https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&w=1400&q=80" }}');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }
    .check-list {
        list-style: none;
        padding: 0;
    }
    .check-list li {
        position: relative;
        padding-left: 28px;
        margin-bottom: 12px;
        font-size: 0.95rem;
        color: var(--text-dim);
    }
    .check-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: bold;
    }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <header class="site-header">
    <nav class="nav-wrap container">
      <a class="brand" href="/" aria-label="Home">
        <img src="/simple_web_ui/logo.png" alt="Silver Cliff Resort" class="brand-logo">
        <div class="brand-text">
          <div class="brand-title">Silver Cliff</div>
          <div class="brand-sub">The Real Jungle Experience</div>
        </div>
      </a>
      <ul class="nav-links d-none d-lg-flex">
        <li><a class="nav-link" href="/simple_web_ui/index.html#intro">Introduction</a></li>
        <li><a class="nav-link" href="/simple_web_ui/rooms.html">Rooms</a></li>
        <li><a class="nav-link" href="/simple_web_ui/index.html#packages">Packages</a></li>
        <li><a class="nav-link" href="/simple_web_ui/booking-status.html">Check Booking</a></li>
        <li><a class="nav-link cta" href="/simple_web_ui/booking.html">Book Now</a></li>
      </ul>
      <button class="menu-btn d-lg-none" id="menuBtn" type="button">
        <svg class="menu-icon" viewBox="0 0 24 24" width="28" height="28">
          <path class="line line1" d="M4 7h16" />
          <path class="line line2" d="M4 12h16" />
          <path class="line line3" d="M4 17h16" />
        </svg>
      </button>
    </nav>
  </header>

  <main>
    <!-- Page Hero -->
    <section class="package-hero">
        <div class="container" data-aos="fade-up">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge-pill mb-3">🌟 Featured Experience</span>
                    <h1 class="hero-title mb-3">{{ $package->title }}</h1>
                    <p class="hero-sub">{{ $package->subtitle }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="glass-card p-4 d-inline-block">
                        <div class="small text-muted-soft text-uppercase letter-spacing mb-1">Starting from</div>
                        <div class="h2 fw-bold text-danger mb-0">THB {{ number_format($package->price_thb) }}</div>
                        <div class="small text-muted-soft">per person / all-inclusive</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <section class="section py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Left: Details -->
                <div class="col-lg-8" data-aos="fade-up">
                    <h3 class="h4 fw-bold mb-4">Journey Overview</h3>
                    <p class="text-muted-soft mb-5 lead">{{ $package->description }}</p>

                    <h3 class="h4 fw-bold mb-4">Day-by-Day Experience</h3>
                    <div class="timeline mb-5">
                        @foreach($package->itineraries as $it)
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-day">Day {{ $it->day_no }}</div>
                                <div class="timeline-title">{{ $it->title }}</div>
                                <div class="timeline-desc">{{ $it->description }}</div>
                                
                                @if($it->day_no == 1 && $package->options->count() > 0)
                                <div class="mt-4 p-4 rounded-4 border border-secondary bg-dark bg-opacity-25">
                                    <h5 class="h6 fw-bold mb-3 text-secondary">Choice of Customizable Adventures</h5>
                                    <p class="small text-muted-soft mb-3">On Day 1, you can choose any <strong>two</strong> of the following activities included in your package:</p>
                                    <div class="row g-3">
                                        @foreach($package->options as $opt)
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white bg-opacity-5 rounded-3 border border-white border-opacity-10 h-100">
                                                <div class="fw-bold mb-1">{{ $opt->name }}</div>
                                                <div class="small text-muted-soft">{{ $opt->description }}</div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <h3 class="h4 fw-bold mb-4">What's Included</h3>
                    <ul class="check-list row g-3">
                        @if($package->includes)
                            @foreach($package->includes as $inc)
                            <li class="col-md-6">{{ $inc }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <!-- Right: Booking Action -->
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="glass-card p-4 sticky-top" style="top: 100px;">
                        <h3 class="h5 fw-bold mb-4">Secure Your Experience</h3>
                        
                        @if($package->options->count() > 0)
                        <div id="bookingOptions" class="mb-4">
                            <label class="form-label-sm mb-3">Select 2 activities for Day 1 <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @foreach($package->options as $opt)
                                <div class="col-6">
                                    <label class="option-pill" onclick="toggleOption(this, '{{ $opt->id }}')">
                                        {{ $opt->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <div id="optionError" class="small text-danger mt-2" style="display:none;">Please select exactly 2 activities.</div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label-sm">Preferred Travel Dates</label>
                            <div class="d-flex gap-2">
                                <input type="date" id="checkin" class="form-control form-dark" placeholder="Check-in">
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <button class="btn btn-danger btn-lg fw-bold py-3" onclick="continueToBooking()">Reserve My Spot</button>
                            <a href="/simple_web_ui/index.html#contact" class="btn btn-outline-light fw-bold">Ask for Details</a>
                        </div>
                        
                        <div class="mt-4 p-3 bg-dark bg-opacity-50 rounded border border-secondary">
                            <div class="small text-muted-soft text-center italic">Best price guarantee when booking direct.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
  </main>

  <footer class="footer mt-5">
    <div class="container">
      <div class="footer-inner">
        <span>© <span id="year"></span> Silver Cliff Resort</span>
        <span class="muted">Khao Sok National Park, Thailand</span>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 650, once: true });
    document.getElementById("year").textContent = new Date().getFullYear();

    let selectedOptions = [];
    const isUltimate = @json($package->code === 'ULTIMATE-JUNGLE');
    const packageId = @json($package->id);

    function toggleOption(el, id) {
        const idx = selectedOptions.indexOf(id);
        if (idx > -1) {
            selectedOptions.splice(idx, 1);
            el.classList.remove('active');
        } else {
            selectedOptions.push(id);
            el.classList.add('active');
        }
        document.getElementById('optionError').style.display = 'none';
    }

    function continueToBooking() {
        if (isUltimate && selectedOptions.length !== 2) {
            document.getElementById('optionError').style.display = 'block';
            return;
        }

        const cin = document.getElementById('checkin').value;
        const params = new URLSearchParams();
        params.set('type', 'package');
        params.set('package_id', packageId);
        if (cin) params.set('checkin', cin);
        if (selectedOptions.length) params.set('options', selectedOptions.join(','));

        window.location.href = `/simple_web_ui/booking.html?${params.toString()}`;
    }
  </script>
</body>
</html>
