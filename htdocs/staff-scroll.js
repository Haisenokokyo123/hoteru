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
  const footer = document.querySelector(".site-footer");
  if (footer) footer.dataset.staffScroll = "heading";
  const ribbon = document.querySelector(".staff-scroll-ribbon");
  const photos = [...hero.querySelectorAll(".staff-hero-image")];
  const cards = [...document.querySelectorAll("[data-staff-scroll-card]")].map(
    (element) => ({ element, top: 0, height: 0, progress: 1, focused: false }),
  );
  const sections = [...document.querySelectorAll("[data-staff-scroll]")].map(
    (element) => ({
      element,
      type: element.dataset.staffScroll,
      top: 0,
      height: 0,
      progress: 1,
      settled: false,
      order: [...element.parentElement.children]
        .filter((sibling) =>
          sibling.matches(
            `[data-staff-scroll="${element.dataset.staffScroll}"]`,
          ),
        )
        .indexOf(element),
    }),
  );
  const revealProperties = [
    "--reveal-y",
    "--reveal-x",
    "--reveal-tilt",
    "--reveal-scale",
    "--reveal-opacity",
    "--reveal-progress",
  ];
  const cardProperties = [
    "--card-y",
    "--card-tilt",
    "--card-scale",
    "--card-opacity",
    "--card-image-y",
    "--card-image-zoom",
  ];
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
  let maxScroll = 1;
  let ribbonTop = 0;
  let pointerTarget = null;

  // Layout coordinates ignore transforms on both the element and its ancestors.
  const layoutTop = (element) => {
    let top = 0;
    while (element) {
      top += element.offsetTop;
      element = element.offsetParent;
    }
    return top;
  };

  const resetScene = () => {
    [
      "--scroll-progress",
      "--scroll-zoom",
      "--scroll-pan",
      "--scroll-shift",
      "--scroll-card-lift",
      "--scroll-frame",
      "--scroll-line",
      "--scroll-title-x",
    ].forEach((name) => hero.style.removeProperty(name));
    for (let index = 1; index <= 6; index++) {
      hero.style.removeProperty(`--scroll-word-${index}`);
    }
    photos.forEach((photo, index) => {
      photo.style.opacity = index === 0 ? "1" : "0";
    });
    cards.forEach(({ element }) => {
      cardProperties.forEach((name) => element.style.removeProperty(name));
    });
    sections.forEach(({ element }) =>
      revealProperties.forEach((name) => element.style.removeProperty(name)),
    );
    body.style.removeProperty("--page-progress");
    ribbon?.style.removeProperty("--ribbon-x");
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
    const extra = Math.min(760, Math.round(viewport * 0.85));
    stage.style.setProperty("--hero-height", `${heroHeight}px`);
    stage.style.setProperty("--pin-top", `${top}px`);
    stage.style.setProperty("--pin-distance", `${extra}px`);
    stage.classList.toggle("is-scroll-pinned", enabled && fits);
    heroStart = stage.getBoundingClientRect().top + window.scrollY - top;
    distance = fits ? extra : Math.max(1, heroHeight * 0.9);

    // offsetTop uses layout coordinates, unaffected by the animated transforms.
    [...cards, ...sections].forEach((item) => {
      item.top = layoutTop(item.element);
      item.height = item.element.offsetHeight;
    });
    ribbonTop = ribbon ? layoutTop(ribbon) : 0;
    // Use layout height rather than transformed scrollHeight, avoiding a feedback loop.
    const last = footer || document.querySelector("main");
    maxScroll = Math.max(1, layoutTop(last) + last.offsetHeight - viewport);
  };

  const drawHero = () => {
    const amount = ease(progress);
    hero.style.setProperty("--scroll-progress", amount.toFixed(4));
    hero.style.setProperty(
      "--scroll-zoom",
      (1.06 + amount * (desktop ? 0.48 : 0.28)).toFixed(4),
    );
    hero.style.setProperty(
      "--scroll-pan",
      `${(-amount * (desktop ? 64 : 30)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-shift",
      `${(-amount * (desktop ? 26 : 12)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-card-lift",
      `${(-amount * (desktop ? 44 : 18)).toFixed(2)}px`,
    );
    hero.style.setProperty(
      "--scroll-frame",
      `${(24 - amount * 14).toFixed(2)}px`,
    );
    hero.style.setProperty("--scroll-line", progress.toFixed(4));
    hero.style.setProperty(
      "--scroll-title-x",
      `${(-amount * (desktop ? 24 : 8)).toFixed(2)}px`,
    );
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
      `${(remaining * (desktop ? 130 : 70)).toFixed(2)}px`,
    );
    card.element.style.setProperty(
      "--card-tilt",
      `${(remaining * (desktop ? 14 : 6)).toFixed(2)}deg`,
    );
    card.element.style.setProperty(
      "--card-scale",
      (1 - remaining * (desktop ? 0.16 : 0.08)).toFixed(4),
    );
    card.element.style.setProperty(
      "--card-opacity",
      (1 - remaining * 0.88).toFixed(4),
    );
  };

  const drawSection = (section) => {
    const amount = ease(section.progress);
    const remaining = 1 - amount;
    const amplitudes = {
      stat: [0, 110, 12, 0.14],
      heading: [-72, 45, 0, 0],
      picker: [0, 80, 5, 0.08],
      "panel-left": [-85, 65, 0, 0.06],
      booking: [85, 65, 0, 0.06],
      "form-step": [0, 55, 0, 0.03],
      summary: [0, 65, 8, 0.1],
      "table-row": [30, 18, 0, 0],
    };
    const [x, y, tilt, scale] = amplitudes[section.type] || amplitudes.heading;
    const strength = desktop ? 1 : 0.6;
    const style = section.element.style;
    style.setProperty(
      "--reveal-x",
      `${(x * remaining * strength).toFixed(2)}px`,
    );
    style.setProperty(
      "--reveal-y",
      `${(y * remaining * strength).toFixed(2)}px`,
    );
    style.setProperty(
      "--reveal-tilt",
      `${(tilt * remaining * strength).toFixed(2)}deg`,
    );
    style.setProperty(
      "--reveal-scale",
      (1 - scale * remaining * strength).toFixed(4),
    );
    style.setProperty("--reveal-opacity", (1 - 0.88 * remaining).toFixed(4));
    style.setProperty("--reveal-progress", amount.toFixed(4));
  };

  const entryProgress = (item, scroll, delay = 0) => {
    const range = Math.min(viewport * 0.4, Math.max(180, item.height * 0.8));
    // Even the final footer/summary must finish before the end of the document.
    const start = Math.min(
      item.top - viewport * 0.94 + delay,
      maxScroll - range,
    );
    return clamp((scroll - start) / range);
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
    body.style.setProperty(
      "--page-progress",
      clamp(scroll / maxScroll).toFixed(4),
    );
    if (ribbon) {
      const travel = clamp((scroll + viewport - ribbonTop) / (viewport * 1.6));
      ribbon.style.setProperty(
        "--ribbon-x",
        `${(-travel * (desktop ? 300 : 150)).toFixed(2)}px`,
      );
    }

    cards.forEach((card, index) => {
      const delay = desktop ? (index % 4) * 48 : 0;
      const target = pointerTarget && card.element.contains(pointerTarget)
        ? card.progress
        : card.focused ? 1 : entryProgress(card, scroll, delay);
      card.progress += (target - card.progress) * step;
      const changing = Math.abs(target - card.progress) > 0.001;
      if (!changing) card.progress = target;
      moving = moving || changing;
      drawCard(card);
      const imageProgress = clamp(
        (scroll + viewport - card.top) / (viewport + card.height),
      );
      card.element.style.setProperty(
        "--card-image-y",
        `${((imageProgress - 0.5) * (desktop ? 38 : 20)).toFixed(2)}px`,
      );
      card.element.style.setProperty(
        "--card-image-zoom",
        (1.25 - imageProgress * 0.1).toFixed(4),
      );
    });
    sections.forEach((section) => {
      const stagger =
        section.type === "stat"
          ? desktop
            ? 55
            : 25
          : section.type === "table-row"
            ? 12
            : 0;
      const target = pointerTarget && section.element.contains(pointerTarget)
        ? section.progress
        : section.settled
        ? 1
        : entryProgress(section, scroll, Math.min(section.order, 6) * stagger);
      section.progress += (target - section.progress) * step;
      const changing = Math.abs(target - section.progress) > 0.001;
      if (!changing) section.progress = target;
      moving = moving || changing;
      drawSection(section);
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
      card.focused = !pointerTarget;
      schedule();
    });
    card.element.addEventListener("focusout", () => {
      card.focused = false;
      schedule();
    });
  });
  const settle = (target, includeChildren = true) => {
    // Once a person interacts with a panel, keep it and its controls stationary.
    const container = target.closest(".booking-box, .panel-box, .room-picker");
    sections.forEach((section) => {
      if (
        (container &&
          (container === section.element ||
            (includeChildren && container.contains(section.element)))) ||
        section.element.contains(target)
      ) {
        section.settled = true;
        section.progress = 1;
        section.element.classList.add(
          includeChildren ? "is-motion-settled" : "is-motion-anchored",
        );
        if (enabled) drawSection(section);
      }
    });
  };
  document.addEventListener("focusin", (event) => {
    if (!pointerTarget && event.target.matches(":focus-visible")) settle(event.target);
  });
  document.addEventListener("pointerdown", (event) => {
    // Mouse/touch focus can precede click. Preserve the hit target until click dispatch.
    pointerTarget = event.target;
    body.classList.add("staff-pointer-active");
  }, { capture: true, passive: true });
  const releasePointer = () => {
    setTimeout(() => {
      const target = pointerTarget;
      if (target && target.contains(document.activeElement)) settle(target);
      pointerTarget = null;
      body.classList.remove("staff-pointer-active");
      schedule();
    }, 0);
  };
  window.addEventListener("pointerup", releasePointer, { passive: true });
  window.addEventListener("pointercancel", releasePointer, { passive: true });
  window.addEventListener("blur", releasePointer);
  document.addEventListener(
    "click",
    (event) => {
      if (event.target.closest("input, select, textarea, button, a"))
        settle(event.target);
    },
    { passive: true },
  );
  const settleAnchor = () => {
    let target;
    try {
      target = document.getElementById(
        decodeURIComponent(location.hash.slice(1)),
      );
    } catch (_) {
      return;
    }
    if (target) settle(target, false);
    layoutChanged();
  };
  window.addEventListener("hashchange", settleAnchor);
  window.addEventListener("load", () => {
    settleAnchor();
    layoutChanged();
  });
  if ("ResizeObserver" in window) {
    const resize = new ResizeObserver(layoutChanged);
    resize.observe(hero);
    if (header) resize.observe(header);
    cards.forEach((card) => resize.observe(card.element));
    sections.forEach((section) => resize.observe(section.element));
    if (ribbon) resize.observe(ribbon);
  }
  if (document.fonts?.ready) document.fonts.ready.then(layoutChanged);
  applyPreference();
  settleAnchor();
})();
