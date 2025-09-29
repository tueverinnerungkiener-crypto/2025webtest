<!doctype html>
<html lang="de" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Fahrzeuge | Autozentrum Kiener – Gebrauchtwagen, Service & Finanzierung in Ladenburg</title>
  <meta name="description" content="Aktuelle Fahrzeuge des Autozentrum Kiener in Ladenburg: geprüfte Gebrauchtwagen inklusive Finanzierung und Service." />
  <meta name="theme-color" content="#ed804d" />
  <link rel="canonical" href="https://www.azkiener.de/fahrzeuge.php" />
  <script>window.tailwind = window.tailwind || {}; window.tailwind.config = { darkMode: "class" };</script>
  <script src="assets/js/tailwindcdn.js"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://i.ibb.co" crossorigin>

  <!-- Favicon-Einbindung -->
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.png" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">

  <!-- Open Graph Tags -->
  <meta property="og:title" content="Fahrzeuge | Autozentrum Kiener – Gebrauchtwagen, Service & Finanzierung in Ladenburg" />
  <meta property="og:description" content="Entdecken Sie die aktuellen Fahrzeuge des Autozentrum Kiener in Ladenburg." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="https://i.ibb.co/m5DbZ5Z6/finish-4.jpg" />

  <!-- Structured Data für Google -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "url": "https://azkiener.de",
    "logo": "https://azkiener.de/logo.png",
    "name": "Autozentrum Kiener",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Wallstadter Straße 49/1",
      "postalCode": "68526",
      "addressLocality": "Ladenburg",
      "addressCountry": "DE"
    },
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+4962039302450",
      "contactType": "customer service",
      "availableLanguage": ["German"]
    }
  }
  </script>
</head>

<body class="min-h-screen bg-white text-gray-900 antialiased">
  <a href="#main" class="skip-link">Zum Inhalt springen</a>
  <a href="#kontakt" class="skip-link">Zur Kontaktsektion springen</a>

  <!-- Header / Top-Bar -->
  <header class="sticky top-0 z-40 border-b bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3 reveal" data-reveal="up" data-reveal-delay="50">
        <div class="h-10 w-10 rounded-xl bg-[#ed804d] text-white grid place-items-center font-bold">AK</div>
        <div class="leading-tight">
          <div class="text-lg font-semibold">Autozentrum Kiener</div>
          <div class="text-xs text-gray-500">Der Gebrauchtwagen-Spezialist in Ladenburg</div>
        </div>
      </div>
      <nav class="hidden md:flex items-center gap-6 text-sm" aria-label="Hauptnavigation">
        <a class="nav-link reveal" data-reveal="down" href="/index.php#fahrzeuge"><span aria-hidden="true"></span> Fahrzeuge</a>
        <a class="nav-link reveal" data-reveal="down" data-reveal-delay="60" href="/index.php#services"><span aria-hidden="true"></span> Service</a>
        <a class="nav-link reveal" data-reveal="down" data-reveal-delay="120" href="/index.php#finanzierung"><span aria-hidden="true"></span> Finanzierung</a>
        <a class="nav-link reveal" data-reveal="down" data-reveal-delay="180" href="/index.php#ueber"><span aria-hidden="true"></span> Über uns</a>
        <a class="btn-outline reveal" data-reveal="down" data-reveal-delay="240" href="kontakt.html">Kontakt</a>
        <button id="themeToggle" class="btn-icon reveal" data-reveal="down" data-reveal-delay="300" aria-pressed="false" aria-label="Dark Mode umschalten"><span id="themeIcon">🌗</span></button>
      </nav>
      <button class="md:hidden btn-outline" aria-label="Menü öffnen" aria-controls="mobileMenu" aria-expanded="false" id="mobileMenuBtn">Menü</button>
    </div>
    <!-- mobiles Menü: animiertes Panel -->
    <div id="mobileMenu" class="md:hidden menu-panel border-t bg-white" role="dialog" aria-modal="true">
      <nav class="mx-auto max-w-7xl px-4 py-3 grid gap-3 text-sm" aria-label="Mobile Navigation">
        <a class="py-1" href="/index.php#fahrzeuge">Fahrzeuge</a>
        <a class="py-1" href="/index.php#services">Service</a>
        <a class="py-1" href="/index.php#finanzierung">Finanzierung</a>
        <a class="py-1" href="/index.php#ueber">Über uns</a>
        <a class="py-1 font-medium" href="kontakt.html">Kontakt</a>
        <button id="themeToggleMobile" class="btn-outline mt-2" aria-pressed="false">Dark Mode</button>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="border-b bg-gray-50">
      <div class="mx-auto max-w-7xl px-4 py-16 sm:py-20">
        <h1 class="text-3xl sm:text-4xl font-semibold text-gray-900">Fahrzeuge</h1>
        <p class="mt-4 max-w-2xl text-gray-600">Entdecken Sie unsere aktuellen Neu- und Gebrauchtwagen. Alle Fahrzeuge sind werkstattgeprüft und sofort verfügbar.</p>
      </div>
    </section>

    <section id="fahrzeuge" class="mx-auto max-w-7xl px-4 py-12 sm:py-16" aria-labelledby="fahrzeuge-title">
      <?php include __DIR__ . '/partials/fahrzeuge.inc.php'; ?>
    </section>
  </main>

  <footer class="border-t">
    <div class="mx-auto max-w-7xl px-4 py-10 text-sm text-gray-600 grid sm:grid-cols-2 lg:grid-cols-4 gap-8" data-reveal-group="80">
      <div class="reveal" data-reveal="up"><div class="font-medium text-gray-900">Autozentrum Kiener</div>
        <p class="mt-2">Ihr Partner für Gebrauchtwagen, Service & Finanzierung in Ladenburg.</p></div>
      <div class="reveal" data-reveal="up"><div class="font-medium text-gray-900">Rechtliches</div>
        <ul class="mt-2 space-y-2">
          <li><a href="impressum.html" class="hover:underline">Impressum</a></li>
          <li><a href="datenschutz.html" class="hover:underline">Datenschutz</a></li>
          <li><button id="cookieSettingsBtn" class="link">Cookie-Einstellungen</button></li>
        </ul></div>
      <div class="reveal" data-reveal="up"><div class="font-medium text-gray-900">Kontakt</div>
        <ul class="mt-2 space-y-2" id="kontakt">
          <li><a href="tel:+4962039302450" class="hover:underline">06203 - 9 30 24 50</a></li>
          <li><a href="tel:+4915174509328" class="hover:underline">0151 - 74 50 93 28</a></li>
          <li><a href="mailto:azkiener@gmx.de" class="hover:underline">azkiener@gmx.de</a></li>
          <li>Wallstadter Straße 49/1 – 68526 Ladenburg</li>
        </ul></div>
      <div class="reveal" data-reveal="up"><div class="font-medium text-gray-900">Newsletter</div>
        <form id="newsletterForm" class="mt-2 flex gap-2" novalidate>
          <input type="email" required placeholder="Ihre E-Mail" class="input" id="newsletterEmail" aria-label="Newsletter E-Mail">
          <button class="btn-primary">Anmelden</button>
        </form>
        <p id="newsletterStatus" class="text-xs mt-2" role="status" aria-live="polite"></p></div>
    </div>
    <div class="text-center text-xs text-gray-500"><span id="visitorCounter">Besucher: gesamt – letzte 30 Tage –</span></div>
    <div class="text-center text-xs text-gray-500 pb-16">© <span id="year"></span> F.A.M. Autohaus Kiener GmbH & Co. KG</div>
  </footer>

  <button id="backToTop" class="back-to-top" aria-label="Nach oben">↑</button>
  <div id="cookieBanner" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie Hinweis">
    <div class="container">
      <p>Wir verwenden Cookies für grundlegende Funktionen (z. B. Dark-Mode-Speicherung). <a href="datenschutz.html#cookies" class="link">Mehr erfahren</a></p>
      <div class="actions">
        <button id="cookieAcceptAll" class="btn-primary">Alle akzeptieren</button>
        <button id="cookieReject" class="btn-outline">Nur notwendige</button>
      </div>
    </div>
  </div>

  <script src="assets/js/main.js" defer></script>
</body>
</html>
