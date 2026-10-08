document.querySelectorAll(".auth-password-toggle").forEach(function (button) {
  button.addEventListener("click", function () {
    var input = document.getElementById(button.getAttribute("aria-controls"));
    if (!input) return;
    var visible = input.type === "password";
    input.type = visible ? "text" : "password";
    button.textContent = visible ? "Hide" : "Show";
    button.setAttribute(
      "aria-label",
      visible ? "Hide password" : "Show password",
    );
    button.setAttribute("aria-pressed", visible ? "true" : "false");
  });
});
