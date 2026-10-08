// Motion enhances the dashboard; the underlying content always stays visible.
(() => {
  if (!document.body.classList.contains("staff-page") || !window.matchMedia)
    return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
  const animations = new Map();
  const revealed = new WeakSet();
  const sections = document.querySelectorAll(
    "[data-staff-reveal], .staff-ring-progress",
  );
  let revealObserver;

  const play = (element, frames, options) => {
    const animation = element.animate(frames, options);
    animations.set(animation, element);
    animation.onfinish = animation.oncancel = () =>
      animations.delete(animation);
  };

  const cancelAnimations = () => {
    animations.forEach((element, animation) => animation.cancel());
    animations.clear();
  };

  const revealSections = () => {
    if (
      reducedMotion.matches ||
      !("IntersectionObserver" in window) ||
      !("animate" in Element.prototype)
    )
      return;

    if (!revealObserver) {
      revealObserver = new IntersectionObserver(
        (entries) => {
          let order = 0;
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const element = entry.target;
            revealObserver.unobserve(element);
            revealed.add(element);
            if (reducedMotion.matches || document.hidden) return;

            if (element.classList.contains("staff-ring-progress")) {
              const offset = parseFloat(
                getComputedStyle(element).strokeDashoffset,
              );
              if (!Number.isFinite(offset)) return;
              play(
                element,
                [
                  { strokeDashoffset: "100" },
                  { strokeDashoffset: String(offset) },
                ],
                {
                  duration: 1250,
                  delay: 180,
                  easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                  fill: "backwards",
                },
              );
              return;
            }

            play(
              element,
              [
                { opacity: 0, transform: "translateY(22px)" },
                { opacity: 1, transform: "translateY(0)" },
              ],
              {
                duration: 850,
                delay: Math.min(order++, 4) * 70,
                easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                fill: "backwards",
              },
            );
          });
        },
        { threshold: 0.06, rootMargin: "0px 0px -18px 0px" },
      );
    }

    sections.forEach((section) => {
      if (!revealed.has(section)) revealObserver.observe(section);
    });
  };

  const hero = document.querySelector(".staff-hero");
  let heroVisible = false;
  let frame = 0;
  let measurePointer = false;
  let pointerX = 0;
  let pointerY = 0;
  let targetX = 0;
  let targetY = 0;
  let currentX = 0;
  let currentY = 0;

  const setDepth = () => {
    hero.style.setProperty("--scene-x", `${currentX.toFixed(2)}px`);
    hero.style.setProperty("--scene-y", `${currentY.toFixed(2)}px`);
    hero.style.setProperty("--spot-x", `${50 + currentX * 6.25}%`);
    hero.style.setProperty("--spot-y", `${50 + currentY * 6.25}%`);
  };

  const resetDepth = () => {
    if (frame) window.cancelAnimationFrame(frame);
    frame = 0;
    measurePointer = false;
    currentX = currentY = targetX = targetY = 0;
    if (hero) setDepth();
  };

  const depthAllowed = () =>
    heroVisible &&
    !document.hidden &&
    !reducedMotion.matches &&
    finePointer.matches;

  const updateDepth = () => {
    frame = 0;
    if (!depthAllowed()) return resetDepth();

    // Measure once per pointer frame, before writing any visual properties.
    if (measurePointer) {
      const bounds = hero.getBoundingClientRect();
      if (!bounds.width || !bounds.height) return resetDepth();
      const x = Math.max(
        0,
        Math.min(1, (pointerX - bounds.left) / bounds.width),
      );
      const y = Math.max(
        0,
        Math.min(1, (pointerY - bounds.top) / bounds.height),
      );
      targetX = (x * 2 - 1) * 8;
      targetY = (y * 2 - 1) * 8;
      measurePointer = false;
    }

    currentX += (targetX - currentX) * 0.14;
    currentY += (targetY - currentY) * 0.14;
    if (Math.abs(targetX - currentX) + Math.abs(targetY - currentY) < 0.025) {
      currentX = targetX;
      currentY = targetY;
      setDepth();
      return;
    }
    setDepth();
    frame = window.requestAnimationFrame(updateDepth);
  };

  if (
    hero &&
    "IntersectionObserver" in window &&
    window.requestAnimationFrame
  ) {
    const heroObserver = new IntersectionObserver((entries) => {
      heroVisible = entries[0].isIntersecting;
      if (!heroVisible) resetDepth();
    });
    heroObserver.observe(hero);

    hero.addEventListener("pointermove", (event) => {
      if (!depthAllowed() || event.pointerType === "touch") return;
      pointerX = event.clientX;
      pointerY = event.clientY;
      measurePointer = true;
      if (!frame) frame = window.requestAnimationFrame(updateDepth);
    });
    hero.addEventListener("pointerleave", () => {
      measurePointer = false;
      targetX = targetY = 0;
      if (depthAllowed() && !frame)
        frame = window.requestAnimationFrame(updateDepth);
    });
  }

  const onPreferenceChange = (query, callback) => {
    if (query.addEventListener) query.addEventListener("change", callback);
    else if (query.addListener) query.addListener(callback);
  };

  onPreferenceChange(reducedMotion, () => {
    if (reducedMotion.matches) {
      if (revealObserver) revealObserver.disconnect();
      cancelAnimations();
      resetDepth();
    } else {
      revealSections();
    }
  });
  onPreferenceChange(finePointer, () => {
    if (!finePointer.matches) resetDepth();
  });
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      cancelAnimations();
      resetDepth();
    }
  });
  // A keyboard user never has to wait for a control's entrance animation.
  document.addEventListener("focusin", (event) => {
    animations.forEach((element, animation) => {
      if (element.contains(event.target)) animation.cancel();
    });
  });

  revealSections();
})();
