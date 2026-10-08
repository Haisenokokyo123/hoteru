(() => {
  const toggle = document.querySelector(".nav-toggle");
  const navigation = document.querySelector("#main-navigation");
  if (!toggle || !navigation) return;
  const close = () => {
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Open navigation");
    navigation.classList.remove("is-open");
  };
  toggle.addEventListener("click", () => {
    const expanded = toggle.getAttribute("aria-expanded") !== "true";
    toggle.setAttribute("aria-expanded", String(expanded));
    toggle.setAttribute(
      "aria-label",
      expanded ? "Close navigation" : "Open navigation",
    );
    navigation.classList.toggle("is-open", expanded);
  });
  document.addEventListener("keydown", (event) => {
    if (
      event.key === "Escape" &&
      toggle.getAttribute("aria-expanded") === "true"
    ) {
      close();
      toggle.focus();
    }
  });
  document.addEventListener("click", (event) => {
    if (!navigation.contains(event.target) && !toggle.contains(event.target))
      close();
  });
  navigation.addEventListener("click", (event) => {
    if (event.target.closest("a")) close();
  });
})();

// Room cards link to their native disclosure form; open it after following the link.
(() => {
  const openBooking = () => {
    if (!window.location.hash.startsWith("#book-room-")) return;
    const form = document.querySelector(window.location.hash);
    if (!(form instanceof HTMLDetailsElement)) return;
    form.open = true;
    form.querySelector("summary")?.focus({ preventScroll: true });
  };

  window.addEventListener("hashchange", openBooking);
  document.addEventListener("click", (event) => {
    if (event.target.closest('a[href^="#book-room-"]')) {
      window.setTimeout(openBooking, 0);
    }
  });
  openBooking();
})();

// Content stays visible without JavaScript; motion is added only as it enters view.
(() => {
  const sections = document.querySelectorAll("[data-reveal]");
  if (
    !sections.length ||
    !("IntersectionObserver" in window) ||
    !("animate" in Element.prototype)
  )
    return;

  const preference = window.matchMedia("(prefers-reduced-motion: reduce)");
  if (preference.matches) return;

  const animations = new Set();
  const observer = new IntersectionObserver(
    (entries) => {
      let order = 0;
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        observer.unobserve(entry.target);
        if (preference.matches) continue;

        const animation = entry.target.animate(
          [
            { opacity: 0, transform: "translateY(24px)" },
            { opacity: 1, transform: "translateY(0)" },
          ],
          {
            duration: 760,
            delay: Math.min(order++, 3) * 75,
            easing: "cubic-bezier(0.22, 1, 0.36, 1)",
            fill: "backwards",
          },
        );
        animations.add(animation);
        animation.finished.then(
          () => animations.delete(animation),
          () => animations.delete(animation),
        );
      }
    },
    { threshold: 0.08, rootMargin: "0px 0px -24px 0px" },
  );

  sections.forEach((section) => observer.observe(section));
  preference.addEventListener("change", (event) => {
    if (!event.matches) return;
    observer.disconnect();
    animations.forEach((animation) => animation.cancel());
    animations.clear();
  });
})();
