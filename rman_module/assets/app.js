/* Tiny helpers */
function $(q) {
  return document.querySelector(q);
}
function $all(q) {
  return Array.from(document.querySelectorAll(q));
}

/* Show/hide PARTIAL scope panel */
function toggleScope() {
  const type = $("#type");
  const scope = $("#scopeBox");
  if (!type || !scope) return;
  scope.hidden = type.value !== "PARTIAL";
}

/* Focus ring helper for keyboard users */
function enableFocusVisible() {
  function onKey(e) {
    if (e.key === "Tab") document.body.classList.add("using-kb");
  }
  function onMouse() {
    document.body.classList.remove("using-kb");
  }
  window.addEventListener("keydown", onKey, { passive: true });
  window.addEventListener("mousedown", onMouse, { passive: true });
}

/* Sticky header shadow on scroll */
function elevateHeaderOnScroll() {
  const hdr = $(".appbar");
  if (!hdr) return;
  const fn = () => hdr.classList.toggle("scrolled", window.scrollY > 4);
  window.addEventListener("scroll", fn, { passive: true });
  fn();
}

/* Auto-resize textareas */
function autosizeTextareas() {
  $all("textarea[data-autosize]").forEach((t) => {
    const fit = () => {
      t.style.height = "auto";
      t.style.height = t.scrollHeight + "px";
    };
    ["input", "change"].forEach((ev) => t.addEventListener(ev, fit));
    fit();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const t = $("#type");
  if (t) {
    t.addEventListener("change", toggleScope);
    toggleScope();
  }
  enableFocusVisible();
  elevateHeaderOnScroll();
  autosizeTextareas();

  $all(".button, .btn").forEach((btn) => {
    btn.addEventListener(
      "click",
      (e) => {
        if (btn.classList.contains("no-ripple")) return;
        const r = document.createElement("span");
        r.className = "ripple";
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        r.style.width = r.style.height = size + "px";
        r.style.left = e.clientX - rect.left - size / 2 + "px";
        r.style.top = e.clientY - rect.top - size / 2 + "px";
        btn.appendChild(r);
        setTimeout(() => r.remove(), 450);
      },
      { passive: true }
    );
  });
});
