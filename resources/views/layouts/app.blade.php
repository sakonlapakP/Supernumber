<!doctype html>
<html lang="th">
  <head>
    @php
      $staticPath = static fn (string $path): string => '/' . ltrim($path, '/');
      $cssVersion = @filemtime(public_path('css/supernumber.css')) ?: time();
      $gaMeasurementId = trim((string) config('services.ga4.measurement_id', ''));
    @endphp
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Supernumber')</title>
    <meta name="description" content="@yield('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง และคัดเบอร์มงคลที่เหมาะกับคุณ')" />
    <meta name="robots" content="@yield('robots', 'index, follow')" />
    <link rel="canonical" href="@yield('canonical', url()->current())" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="@yield('og_title', 'Supernumber')" />
    <meta property="og:description" content="@yield('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง และคัดเบอร์มงคลที่เหมาะกับคุณ')" />
    <meta property="og:url" content="@yield('og_url', url()->current())" />
    <meta property="og:image" content="@yield('og_image', $staticPath('images/home_banner.jpg'))" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="theme-color" content="@yield('theme_color', '#2a2321')" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ $staticPath('favicon-v2.ico') }}" />
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ $staticPath('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $staticPath('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $staticPath('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $staticPath('apple-touch-icon.png') }}" />
    @hasSection('preload_image')
      <link rel="preload" as="image" href="@yield('preload_image')" />
    @endif
    <link rel="stylesheet" href="{{ $staticPath('css/supernumber.css') }}?v={{ $cssVersion }}" />
  </head>
  <body class="@yield('body_class')">
    @hasSection('hide_header')
    @else
      @include('partials.header')
    @endif

    <main>
      @yield('content')
    </main>

    @hasSection('hide_footer')
    @else
      @include('partials.footer')
    @endif

    <div class="cookie-consent" data-cookie-consent hidden>
      <div class="cookie-consent__inner">
        <div class="cookie-consent__copy">
          <h3 class="cookie-consent__title">ความยินยอมข้อมูลส่วนบุคคล</h3>
          <p class="cookie-consent__text">
            บริษัท ซุปเปอร์นัมเบอร์ จำกัด ใช้คุกกี้เพื่อมอบประสบการณ์การใช้งานเว็บไซต์ที่ดีที่สุดให้กับคุณ 
            <a href="#" class="cookie-consent__link">ดูรายละเอียดนโยบายความเป็นส่วนตัว</a>
          </p>
        </div>
        <div class="cookie-consent__actions">
          <button type="button" class="cookie-consent__button cookie-consent__button--settings" data-privacy-settings-trigger>ตั้งค่าความเป็นส่วนตัว</button>
          <button type="button" class="cookie-consent__button cookie-consent__button--accept-all" data-cookie-accept-all>ให้ความยินยอมทั้งหมด</button>
        </div>
      </div>
    </div>

    <!-- Privacy Settings Modal -->
    <div class="privacy-modal" id="privacy-modal" hidden>
      <div class="privacy-modal__overlay"></div>
      <div class="privacy-modal__content">
        <div class="privacy-modal__header">
          <h2>ความยินยอมข้อมูลส่วนบุคคล</h2>
          <button type="button" class="privacy-modal__close" data-privacy-modal-close aria-label="ปิด">&times;</button>
        </div>
        
        <div class="privacy-modal__body">
          <div class="privacy-modal__intro">
            <span style="color: #d8a34a; font-weight: 800;">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</span> 
            ให้ความสำคัญต่อความเป็นส่วนตัวของลูกค้า เราจะทำงานอย่างดีที่สุดเพื่อรักษาความลับ และควบคุมข้อมูลส่วนบุคคลของคุณให้ปลอดภัย อย่างไรก็ตาม ลูกค้าสามารถเลือกที่จะให้หรือไม่ให้ความยินยอมก็ได้ โดยความยินยอมและการจัดการข้อมูลส่วนบุคคลจะถูกแบ่งเป็นสามหัวข้อเพื่อให้สิทธิอย่างเต็มที่
          </div>

          <div class="privacy-option-card">
            <div class="privacy-option-card__head">
              <h3>1. ความยินยอมในการให้ข้อมูลส่วนบุคคล</h3>
              <p>ความยินยอมในการเก็บรวบรวม ใช้ หรือเปิดเผยข้อมูลส่วนบุคคล เพื่อการลงชื่อเข้าใช้ และรับบริการต่างๆ รวมถึงการจัดทำเอกสารหรือสัญญา ตามวัตถุประสงค์ของลูกค้า <a href="{{ route('privacy.personal') }}" target="_blank">ดูเพิ่มเติม</a></p>
            </div>
            <div class="privacy-option-card__controls">
              <label class="privacy-radio">
                <input type="radio" name="consent_personal" value="1" checked>
                <span class="privacy-radio__label">ยินยอม</span>
              </label>
              <label class="privacy-radio">
                <input type="radio" name="consent_personal" value="0">
                <span class="privacy-radio__label">ไม่ยินยอม</span>
              </label>
            </div>
          </div>

          <div class="privacy-option-card">
            <div class="privacy-option-card__head">
              <h3>2. ความยินยอมในการนำข้อมูลไปใช้เพื่อพัฒนาสินค้าหรือบริการให้ดียิ่งขึ้น</h3>
              <p>เพื่อให้ท่านได้รับความพึงพอใจต่อบริการของเราอย่างต่อเนื่อง ทางบริษัทจะปรับปรุงและพัฒนาสินค้าและบริการให้ดียิ่งขึ้น <a href="{{ route('privacy.development') }}" target="_blank">ดูเพิ่มเติม</a></p>
            </div>
            <div class="privacy-option-card__controls">
              <label class="privacy-radio">
                <input type="radio" name="consent_dev" value="1" checked>
                <span class="privacy-radio__label">ยินยอม</span>
              </label>
              <label class="privacy-radio">
                <input type="radio" name="consent_dev" value="0">
                <span class="privacy-radio__label">ไม่ยินยอม</span>
              </label>
            </div>
          </div>

          <div class="privacy-option-card">
            <div class="privacy-option-card__head">
              <h3>3. ความยินยอมให้บริษัทนำเสนอผลิตภัณฑ์ บริการ หรือโปรโมชั่นพิเศษ</h3>
              <p>เพื่อให้ท่านไม่พลาดข้อเสนอพิเศษของผลิตภัณฑ์หรือบริการ ทางเราขอเรียนเชิญให้ท่านเข้าร่วมกิจกรรมต่างๆ <a href="{{ route('privacy.marketing') }}" target="_blank">ดูเพิ่มเติม</a></p>
            </div>
            <div class="privacy-option-card__controls">
              <label class="privacy-radio">
                <input type="radio" name="consent_marketing" value="1" checked>
                <span class="privacy-radio__label">ยินยอม</span>
              </label>
              <label class="privacy-radio">
                <input type="radio" name="consent_marketing" value="0">
                <span class="privacy-radio__label">ไม่ยินยอม</span>
              </label>
            </div>
          </div>

          <p style="margin: 0; font-size: 13px; color: #7a6c62;">
            คุณสามารถอ่านรายละเอียดเพิ่มเติมได้ที่: <a href="{{ route('privacy') }}" target="_blank" style="color: #d8a34a; font-weight: 600; text-decoration: underline;">คลิก</a>
          </p>
        </div>

        <div class="privacy-modal__footer">
          <button type="button" class="privacy-modal__submit" data-privacy-save>ยืนยัน</button>
        </div>
      </div>
    </div>

    <script>
      (() => {
        const measurementId = @json($gaMeasurementId);

        const noopAnalytics = {
          enable: () => false,
          track: () => false,
          isEnabled: () => false,
        };

        if (!measurementId) {
          window.SupernumberAnalytics = noopAnalytics;
          return;
        }

        const storageKey = "supernumber_cookie_consent";
        let enabled = false;
        let scriptRequested = false;

        const sanitizeUrl = (value) => {
          if (!value) return "";

          try {
            const parsed = new URL(value, window.location.origin);
            return `${parsed.origin}${parsed.pathname}`;
          } catch (_) {
            return "";
          }
        };

        const pagePath = () => window.location.pathname || "/";
        const pageLocation = () => sanitizeUrl(window.location.href);
        const pageReferrer = () => sanitizeUrl(document.referrer);

        const loadScript = () => {
          if (scriptRequested) return;

          scriptRequested = true;

          const script = document.createElement("script");
          script.async = true;
          script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
          document.head.appendChild(script);
        };

        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function gtag() {
          window.dataLayer.push(arguments);
        };

        const track = (eventName, params = {}) => {
          if (!enabled || !eventName) return false;

          const payload = {
            transport_type: "beacon",
            page_path: pagePath(),
            page_location: pageLocation(),
            page_referrer: pageReferrer(),
            ...params,
          };

          Object.keys(payload).forEach((key) => {
            if (payload[key] === "" || payload[key] === null || typeof payload[key] === "undefined") {
              delete payload[key];
            }
          });

          window.gtag("event", eventName, payload);

          return true;
        };

        const enable = () => {
          if (enabled) return true;

          loadScript();
          window.gtag("js", new Date());
          enabled = true;
          window.gtag("config", measurementId, {
            send_page_view: false,
            anonymize_ip: true,
            page_path: pagePath(),
            page_location: pageLocation(),
          });
          track("page_view", {
            page_title: document.title,
          });

          return true;
        };

        window.SupernumberAnalytics = {
          enable,
          track,
          isEnabled: () => enabled,
        };

        if (window.localStorage.getItem(storageKey) === "accepted") {
          enable();
        }

        window.addEventListener("supernumber:analytics-consent-granted", enable);
      })();
    </script>

    <script>
      (() => {
        const toggle = document.querySelector(".nav-toggle");
        const menu = document.getElementById("mobile-menu");

        if (!toggle || !menu) return;

        const closeMenu = () => {
          menu.classList.remove("is-open");
          toggle.setAttribute("aria-expanded", "false");
        };

        toggle.addEventListener("click", () => {
          const isOpen = menu.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        menu.addEventListener("click", (event) => {
          if (event.target.matches("a")) {
            closeMenu();
          }
        });
      })();

      (() => {
        const storageKey = "supernumber_privacy_consent";
        const banner = document.querySelector("[data-cookie-consent]");
        const modal = document.getElementById("privacy-modal");
        
        const trigger = document.querySelector("[data-privacy-settings-trigger]");
        const acceptAllBtn = document.querySelector("[data-cookie-accept-all]");
        
        const closeModalBtn = document.querySelector("[data-privacy-modal-close]");
        const saveSettingsBtn = document.querySelector("[data-privacy-save]");

        if (!banner) return;

        const hideBanner = () => { banner.hidden = true; };
        const showBanner = () => { banner.hidden = false; };
        const openModal = () => { modal.hidden = false; document.body.style.overflow = "hidden"; };
        const closeModal = () => { modal.hidden = true; document.body.style.overflow = ""; };

        // Load saved state
        const savedConsent = window.localStorage.getItem(storageKey);
        if (savedConsent) {
          hideBanner();
        } else {
          showBanner();
        }

        // Action: Accept All
        if (acceptAllBtn) {
          acceptAllBtn.addEventListener("click", () => {
            const consent = { personal: true, dev: true, marketing: true, timestamp: Date.now() };
            window.localStorage.setItem(storageKey, JSON.stringify(consent));
            window.dispatchEvent(new CustomEvent("supernumber:analytics-consent-granted"));
            hideBanner();
          });
        }

        // Action: Open Settings
        if (trigger) {
          trigger.addEventListener("click", openModal);
        }

        // Action: Close Modal
        if (closeModalBtn) {
          closeModalBtn.addEventListener("click", closeModal);
        }

        // Action: Save Settings
        if (saveSettingsBtn) {
          saveSettingsBtn.addEventListener("click", () => {
            const getVal = (name) => document.querySelector(`input[name="${name}"]:checked`).value === "1";
            const consent = {
              personal: getVal("consent_personal"),
              dev: getVal("consent_dev"),
              marketing: getVal("consent_marketing"),
              timestamp: Date.now()
            };
            window.localStorage.setItem(storageKey, JSON.stringify(consent));
            
            if (consent.personal || consent.dev) {
                window.dispatchEvent(new CustomEvent("supernumber:analytics-consent-granted"));
            }
            
            closeModal();
            hideBanner();
          });
        }
      })();
    </script>
    @stack('scripts')
  </body>
</html>
