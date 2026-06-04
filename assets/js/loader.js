/**
 * HK Builders & Developers — Premium Page Loader
 * Drop this <script src="loader.js"></script> into the <head> of every HTML page
 * Also link <link rel="stylesheet" href="loader.css"> in each page
 */

(function () {
  // Inject the loader HTML into the page immediately
  const loaderHTML = `
    <div class="hk-loader" id="hkLoader">
      <!-- Corner decorations -->
      <div class="hk-loader-corner hk-loader-corner-tl"></div>
      <div class="hk-loader-corner hk-loader-corner-tr"></div>
      <div class="hk-loader-corner hk-loader-corner-bl"></div>
      <div class="hk-loader-corner hk-loader-corner-br"></div>

      <div class="hk-loader-content">
        <!-- Animated Ring + Monogram -->
        <div class="hk-loader-ring-outer">
          <svg viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="ringGold" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#c9a84c"/>
                <stop offset="50%" stop-color="#f0dfa0"/>
                <stop offset="100%" stop-color="#c9a84c"/>
              </linearGradient>
              <linearGradient id="ringGold2" x1="100%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#c9a84c" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#f0dfa0" stop-opacity="0.8"/>
              </linearGradient>
            </defs>

            <!-- Outer static ring (faint) -->
            <circle cx="80" cy="80" r="75" stroke="rgba(201,168,76,0.08)" stroke-width="1"/>

            <!-- Rotating dashed ring -->
            <circle
              class="ring-rotate"
              cx="80" cy="80" r="70"
              stroke="url(#ringGold2)"
              stroke-width="0.5"
              stroke-dasharray="8 6"
              fill="none"
            />

            <!-- Main gold arc (rotating) -->
            <path
              class="ring-rotate"
              d="M 80 10 A 70 70 0 0 1 150 80"
              stroke="url(#ringGold)"
              stroke-width="1.5"
              stroke-linecap="round"
              fill="none"
            />

            <!-- Reverse rotating arc -->
            <path
              class="ring-rotate-reverse"
              d="M 80 150 A 70 70 0 0 1 10 80"
              stroke="rgba(201,168,76,0.4)"
              stroke-width="1"
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="20 10"
            />

            <!-- Inner circle -->
            <circle cx="80" cy="80" r="55" stroke="rgba(201,168,76,0.12)" stroke-width="0.5" fill="none"/>

            <!-- Dots on outer ring -->
            <circle cx="80" cy="10" r="3" fill="#c9a84c" opacity="0.9"/>
            <circle cx="150" cy="80" r="2" fill="#f0dfa0" opacity="0.6"/>
            <circle cx="80" cy="150" r="2" fill="#c9a84c" opacity="0.4"/>
            <circle cx="10" cy="80" r="1.5" fill="#f0dfa0" opacity="0.3"/>

            <!-- Tick marks -->
            <line x1="80" y1="5" x2="80" y2="13" stroke="rgba(201,168,76,0.5)" stroke-width="1"/>
            <line x1="145" y1="80" x2="153" y2="80" stroke="rgba(201,168,76,0.3)" stroke-width="1"/>
            <line x1="80" y1="147" x2="80" y2="155" stroke="rgba(201,168,76,0.2)" stroke-width="1"/>
            <line x1="7" y1="80" x2="15" y2="80" stroke="rgba(201,168,76,0.2)" stroke-width="1"/>
          </svg>
          <div class="hk-loader-monogram">HK</div>
        </div>

        <!-- Brand Name -->
        <div class="hk-loader-brand">
          <div class="hk-loader-name">HK Builders</div>
          <div class="hk-loader-sub">& Developers — Pakistan</div>
        </div>

        <!-- Progress -->
        <div class="hk-loader-progress-wrap">
          <div class="hk-loader-progress-bar" id="hkLoaderBar"></div>
        </div>
        <div class="hk-loader-percent" id="hkLoaderPercent">0%</div>

        <!-- Tagline -->
        <div class="hk-loader-tagline">Building Trust. Creating Futures.</div>
      </div>
    </div>
  `;

  // Create container and inject
  const div = document.createElement('div');
  div.innerHTML = loaderHTML;
  document.documentElement.insertBefore(div.firstElementChild, document.documentElement.firstChild);

  // Add floating particles
  function addParticles() {
    const loader = document.getElementById('hkLoader');
    if (!loader) return;
    for (let i = 0; i < 18; i++) {
      const p = document.createElement('div');
      p.className = 'hk-loader-particle';
      p.style.left = Math.random() * 100 + '%';
      p.style.animationDuration = (6 + Math.random() * 10) + 's';
      p.style.animationDelay = (Math.random() * 6) + 's';
      p.style.width = p.style.height = (1 + Math.random() * 2) + 'px';
      loader.appendChild(p);
    }
  }

  // Animate progress bar
  function runLoader() {
    const bar = document.getElementById('hkLoaderBar');
    const percent = document.getElementById('hkLoaderPercent');
    const loader = document.getElementById('hkLoader');

    if (!bar || !percent || !loader) return;

    addParticles();

    let progress = 0;
    const minDuration = 2200; // minimum display time in ms
    const startTime = Date.now();

    const phases = [
      { target: 30, speed: 18 },
      { target: 60, speed: 12 },
      { target: 80, speed: 6 },
      { target: 95, speed: 3 },
    ];

    let phaseIndex = 0;

    const tick = setInterval(() => {
      const phase = phases[phaseIndex];
      if (phase && progress < phase.target) {
        progress += (Math.random() * phase.speed * 0.3);
        progress = Math.min(progress, phase.target);
      } else if (phaseIndex < phases.length - 1) {
        phaseIndex++;
      }

      bar.style.width = progress + '%';
      percent.textContent = Math.floor(progress) + '%';
    }, 60);

    // Listen for page load AND enforce minimum display time
    function finishLoading() {
      clearInterval(tick);
      const elapsed = Date.now() - startTime;
      const remaining = Math.max(0, minDuration - elapsed);

      setTimeout(() => {
        // Complete the bar
        progress = 100;
        bar.style.width = '100%';
        percent.textContent = '100%';

        setTimeout(() => {
          loader.classList.add('hidden');

          // Reveal page content
          document.querySelectorAll('.page-reveal').forEach((el, i) => {
            setTimeout(() => el.classList.add('loaded'), i * 80);
          });
        }, 400);
      }, remaining);
    }

    if (document.readyState === 'complete') {
      finishLoading();
    } else {
      window.addEventListener('load', finishLoading);
    }
  }

  // Run immediately
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runLoader);
  } else {
    runLoader();
  }
})();