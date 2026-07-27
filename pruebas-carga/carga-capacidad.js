'use strict';

// Processor de Artillery para carga-capacidad.yml.
// Loguea un POOL de N ciudadanos (carga1..cargaN@test.local) UNA sola vez y reparte sus
// tokens round-robin entre los usuarios virtuales. Así la carga se distribuye entre
// muchos cupos de rate limit (120/min c/u) y el techo pasa a ser el hardware, no el throttle.
//
// ⚠️ /api/login usa el limitador con nombre throttle:login = 5 logins/min POR IP. Loguear N>5
// usuarios de golpe desde una sola IP hace que los sobrantes reciban 429 y se queden sin token.
// Por eso los logins se ESCALONAN en tandas de 5 con una pausa de 61 s entre tandas. Con N=15 el
// arranque tarda ~2 min antes de empezar a medir; es el precio de venir de una sola IP.
// El escenario con pool de usuarios solo se corre en LOCAL: carga:usuarios-prueba se niega a
// crear cuentas en producción.

let tokensPromise = null;
let siguiente = 0;

// Loguea los N usuarios en paralelo y cachea el arreglo de tokens a nivel de módulo.
async function cargarTokens(base) {
  const n = parseInt(process.env.CARGA_USUARIOS || '10', 10);
  const password = process.env.CARGA_PASSWORD;

  if (!password) {
    throw new Error('Falta CARGA_PASSWORD en el entorno.');
  }

  // Login de un ciudadano; devuelve su access_token.
  const login = async (i) => {
    const res = await fetch(base + '/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ email: `carga${i}@test.local`, password }),
    });
    const cuerpo = await res.json().catch(() => ({}));
    if (!res.ok || !cuerpo.access_token) {
      throw new Error(`Login falló para carga${i}@test.local (HTTP ${res.status}).`);
    }
    return cuerpo.access_token;
  };

  // Escalonado en tandas de 5 (el throttle del login es 5/min por IP), con pausa entre tandas.
  const tokens = [];
  for (let inicio = 1; inicio <= n; inicio += 5) {
    const tanda = [];
    for (let i = inicio; i < inicio + 5 && i <= n; i++) {
      tanda.push(login(i));
    }
    tokens.push(...(await Promise.all(tanda)));
    if (inicio + 5 <= n) {
      await new Promise((r) => setTimeout(r, 61000));
    }
  }

  return tokens;
}

// Hook beforeScenario: entrega al VU el siguiente token del pool (round-robin).
async function tokenDelPool(context) {
  if (!tokensPromise) {
    tokensPromise = cargarTokens(context.vars.target);
  }
  const tokens = await tokensPromise;
  context.vars.token = tokens[siguiente % tokens.length];
  siguiente++;
}

module.exports = { tokenDelPool };
