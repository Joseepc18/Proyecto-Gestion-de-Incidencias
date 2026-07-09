'use strict';

// Processor de Artillery para carga-auth.yml.
// Loguea UNA sola vez (la promesa se cachea a nivel de módulo) y comparte el token
// entre todos los usuarios virtuales, así la carga mide el listado y no el bcrypt
// del login. Se usa aquí en vez del bloque 'before' de Artillery porque ese hook
// hace socket timeout en esta versión sobre Windows.

let tokenPromise = null;

// Hook beforeScenario (async): deja context.vars.token listo para el flujo.
async function setAuthToken(context) {
  const email = process.env.ARTILLERY_EMAIL;
  const password = process.env.ARTILLERY_PASSWORD;

  if (!email || !password) {
    throw new Error('Faltan ARTILLERY_EMAIL / ARTILLERY_PASSWORD en el entorno.');
  }

  if (!tokenPromise) {
    const base = context.vars.target;
    tokenPromise = fetch(base + '/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ email, password }),
    }).then(async (res) => {
      const cuerpo = await res.json().catch(() => ({}));
      if (!res.ok || !cuerpo.access_token) {
        // Sin access_token: credenciales malas o el usuario tiene 2FA (usar un ciudadano).
        throw new Error('Login sin token (HTTP ' + res.status + '); usa un ciudadano sin 2FA.');
      }
      return cuerpo.access_token;
    });
  }

  context.vars.token = await tokenPromise;
}

module.exports = { setAuthToken };
