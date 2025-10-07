function $(q) {
  return document.querySelector(q);
}
function $all(q) {
  return Array.from(document.querySelectorAll(q));
}

function toggleScope() {
  const type = $("#type").value;
  const scope = $("#scopeBox");
  scope.style.display = type === "PARTIAL" ? "block" : "none";
}

document.addEventListener("DOMContentLoaded", () => {
  const t = $("#type");
  if (t) {
    t.addEventListener("change", toggleScope);
    toggleScope();
  }
});
