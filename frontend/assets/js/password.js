// password.js — Botón de mostrar/ocultar contraseña reutilizable.
// Por delegación: se engancha a cualquier botón .toggle-password de la página.

document.addEventListener("click", function (e) {
  const boton = e.target.closest(".toggle-password");
  if (!boton) return;
  const input = document.getElementById(boton.dataset.target);
  if (!input) return;
  // Estaba oculta (password): este clic la va a revelar.
  const revelar = input.type === "password";
  input.type = revelar ? "text" : "password";
  const icono = boton.querySelector("i");
  if (icono) {
    icono.classList.toggle("bi-eye", !revelar);
    icono.classList.toggle("bi-eye-slash", revelar);
  }
  boton.setAttribute("aria-label", revelar ? "Ocultar contraseña" : "Mostrar contraseña");
  boton.setAttribute("aria-pressed", String(revelar));
});
