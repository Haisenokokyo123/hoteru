// Native scrolling drives the sequence. No wheel/touch interception or scroll snapping.
(() => {
  const body = document.body;
  const stage = document.querySelector("[data-staff-scroll-stage]");
  const hero = stage?.querySelector(".staff-hero");
  if (!body.classList.contains("staff-page") || !hero || !window.matchMedia)
    return;

  const preference = window.matchMedia("(prefers-reduced-motion: reduce)");
  const toggle = document.querySelector("[data-staff-motion-toggle]");
  const header = document.querySelector(".site-header");
  const photos = [...hero.querySelectorAll(".staff-hero-image")];
  const cards = [...document.querySelectorAll("[data-staff-scroll-card]")].map(
    (element) => ({ element, top: 0, height: 0, progress: 1, focused: false }),
  );
  const clamp = (number) => Math.max(0, Math.min(1, number));
  const ease = (number) => {
    const value = clamp(number);
    return value * value * (3 - 2 * value);
  };
  let userPaused = false;
  try {
    userPaused = localStorage.getItem("hoteru.staff.motion") === "off";
  } catch (_) {
    // Private browsing can deny storage; the current page still honors the toggle.
  }

  let enabled = false;
  let frame = 0;
  let needsLayout = true;
  let initialized = false;
  let heroStart = 0;
  let distance = 1;
  let progress = 0;
  let viewport = window.innerHeight;
  let desktop = false;

  const resetScene = () => {
    [
      "--scroll-progress",
      "--scroll-zoom",
      "--scroll-pan",
      "--scroll-shift",
      "--scroll-card-lift",
      "--scroll-frame",
      "--scroll-line",
    ].forEach((name) => hero.style.removeProperty(name));
    for (let index = 1; index <= 6; index++) {
      hero.style.removeProperty(`--scroll-word-${index}`);
    }
    photos.forEach((photo, index) => {
      photo.style.opacity = index === 0 ? "1" : "0";
    });
    cards.forEach(({ element }) => {
      ["--card-y", "--card-tilt", "--card-scale", "--card-opacity"].forEach(
        (name) => element.style.removeProperty(name),
      );
    });
  };

  const measure = () => {
    needsLayout = false;
    viewport = window.innerHeight;
    desktop = window.innerWidth >= 1000;
    // Restore natural geometry before measuring; CSS never pins by default.
    stage.classList.remove("is-scroll-pinned");
    const headerHeight = header?.getBoundingClientRect().height || 0;
    const heroHeight = hero.offsetHeight;
    const top = headerHeight + 16;
    const fits = desktop && heroHeight <= viewport - top - 24;
    const extra = Math.min(560, Math.round(viewport * 0.6));
    stage.style.setProperty("--hero-height", `${heroHeight}px`);
    stage.style.setProperty("--pin-top", `${top}px`);
    stage.style.setProperty("--pin-distance", `${extra}px`);
    stage.classList.toggle("is-scroll-pinned", enabled && fits);
    heroStart = stage.getBoundingClientRect().top + window.scrollY - top;
    distance = fits ? extra : Math.max(1, heroHeight * 0.9);

    // offsetTop uses layout coordinates, unaffected by the animated transforms.
    cards.forEach((card) => {
      let element = card.element;
      card.top = 0;
      while (element) {
        card.top += element.offsetTop;
        element = element.offsetParent;
      }
      card.height = card.element.offsetHeight;
    });
  };

  const drawHero = () => {
    const amount = ease(progress);
    hero.style.setProperty("--scroll-progress", amount.toFixed(4));
    hero.style.setProperty(
      "--scroll-zoom",
      (1.08 + amount * (desktop ? 0.2 : 0.06)).toFixed(4),
    );
    hero.style.setProperty(
      "--scroll-pan",
      `${(-amount * (desktop ? 32 : 12)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-shift",
      `${(-amount * (desktop ? 18 : 6)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-card-lift",
      `${(-amount * (desktop ? 28 : 8)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-frame",
      `${(24 - amount * 14).toFixed(2)}px`,
    );
    hero.style.setProperty("--scroll-line", progress.toFixed(4));
    for (let index = 0; index < 6; index++) {
      hero.style.setProperty(
        `--scroll-word-${index + 1}`,
        ease(progress * 7 - index).toFixed(4),
      );
    }

    // Each next photo fades over the preceding one; never fade through an empty backdrop.
    photos.forEach((photo, index) => {
      const opacity =
        index === 0 ? 1 : ease(progress * (photos.length - 1) - index + 1);
      photo.style.opacity = opacity.toFixed(4);
    });
  };

  const drawCard = (card) => {
    const remaining = 1 - ease(card.progress);
    card.element.style.setProperty(
      "--card-y",
      `${(remaining * (desktop ? 64 : 16)).toFixed(2)}px`,
    );
    card.element.style.setProperty(
      "--card-tilt",
      `${(remaining * (desktop ? 7 : 0)).toFixed(2)}deg`,
    );
    card.element.style.setProperty(
      "--card-scale",
      (1 - remaining * (desktop ? 0.055 : 0)).toFixed(4),
    );
    card.element.style.setProperty(
      "--card-opacity",
      (1 - remaining * 0.24).toFixed(4),
    );
  };

  const update = () => {
    frame = 0;
    if (!enabled || document.hidden) return;
    if (needsLayout) measure();
    const scroll = window.scrollY;
    const target = clamp((scroll - heroStart) / distance);
    const step = initialized ? 0.24 : 1;
    progress += (target - progress) * step;
    let moving = Math.abs(target - progress) > 0.001;
    if (!moving) progress = target;
    drawHero();

    cards.forEach((card, index) => {
      // A small column offset creates a cascading reveal; focused links always stay settled.
      const delay = desktop ? (index % 4) * 22 : 0;
      const end = Math.min(card.height * 0.65, viewport * 0.38);
      const target = card.focused
        ? 1
        : clamp(
            (scroll + viewport * 0.96 - card.top - delay) / Math.max(1, end),
          );
      card.progress += (target - card.progress) * step;
      const changing = Math.abs(target - card.progress) > 0.001;
      if (!changing) card.progress = target;
      moving = moving || changing;
      drawCard(card);
    });
    initialized = true;
    if (moving) frame = requestAnimationFrame(update);
  };

  const schedule = () => {
    if (enabled && !document.hidden && !frame)
      frame = requestAnimationFrame(update);
  };
  const layoutChanged = () => {
    needsLayout = true;
    schedule();
  };
  const applyPreference = () => {
    enabled = !preference.matches && !userPaused;
    if (frame) cancelAnimationFrame(frame);
    frame = 0;
    initialized = false;
    body.classList.toggle("staff-motion-paused", !enabled);
    document.documentElement.classList.toggle("staff-motion-paused", !enabled);
    body.classList.toggle("staff-scroll-ready", enabled);
    if (toggle) {
      toggle.hidden = false;
      toggle.disabled = preference.matches;
      toggle.setAttribute("aria-pressed", String(enabled));
      toggle.querySelector("[data-motion-label]").textContent =
        preference.matches
          ? "Reduced motion"
          : enabled
            ? "Motion on"
            : "Motion off";
      toggle.title = preference.matches
        ? "Your device prefers reduced motion."
        : "Turn animated effects on or off";
    }
    if (!enabled) {
      stage.classList.remove("is-scroll-pinned");
      resetScene();
    }
    layoutChanged();
    window.dispatchEvent(new CustomEvent("staff-motion-change"));
  };

  toggle?.addEventListener("click", () => {
    userPaused = !userPaused;
    try {
      localStorage.setItem("hoteru.staff.motion", userPaused ? "off" : "on");
    } catch (_) {}
    applyPreference();
  });
  if (preference.addEventListener)
    preference.addEventListener("change", applyPreference);
  else preference.addListener(applyPreference);
  window.addEventListener("scroll", schedule, { passive: true });
  window.addEventListener("resize", layoutChanged, { passive: true });
  window.addEventListener("pageshow", layoutChanged);
  document.addEventListener("visibilitychange", () => {
    if (document.hidden && frame) {
      cancelAnimationFrame(frame);
      frame = 0;
    } else schedule();
  });
  cards.forEach((card) => {
    card.element.addEventListener("focusin", () => {
      card.focused = true;
      schedule();
    });
    card.element.addEventListener("focusout", () => {
      card.focused = false;
      schedule();
    });
  });
  if ("ResizeObserver" in window) {
    const resize = new ResizeObserver(layoutChanged);
    resize.observe(hero);
    if (header) resize.observe(header);
    cards.forEach((card) => resize.observe(card.element));
  }
  if (document.fonts?.ready) document.fonts.ready.then(layoutChanged);
  applyPreference();
})();
