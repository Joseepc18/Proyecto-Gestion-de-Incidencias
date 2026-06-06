#!/usr/bin/env python3
from flask import Flask, request, jsonify
import requests
from datetime import datetime
import pytz

app = Flask(__name__)

DISCORD_WEBHOOK_URL = "https://discord.com/api/webhooks/1510325298598514788/BDz6WYTP7B4CPtrlN9nAjY5C4tKbVPeX5FWIHGsH8ndQ3B6cshe7nQp8hPieNzNDFzCn"
ZONA_HORARIA = "America/Guayaquil"

ERRORES = {
    "ECONNRESET":   "La conexión fue interrumpida bruscamente",
    "ECONNREFUSED": "El servidor rechazó la conexión (servicio caído)",
    "ETIMEDOUT":    "La conexión tardó demasiado (timeout)",
    "ENOTFOUND":    "No se encontró el servidor (DNS fallido)",
    "502":          "Error 502 — el backend no responde",
    "503":          "Error 503 — servicio no disponible",
    "504":          "Error 504 — timeout del servidor",
}

SUGERENCIAS = {
    "ECONNRESET":   "docker compose ps` y revisa logs con `docker compose logs --tail=50",
    "ECONNREFUSED": "docker compose ps` — probablemente un contenedor se cayó",
    "ETIMEDOUT":    "ping incidenciasupse.site` para ver si es red o servidor",
    "ENOTFOUND":    "systemctl status cloudflared` — puede ser el túnel de Cloudflare",
    "502":          "docker compose logs backend --tail=50` para ver el error",
    "503":          "docker compose ps` y verifica que todos los servicios estén Up",
    "504":          "docker compose logs --tail=50` para ver qué servicio está lento",
}

caidas_activas = {}

def hora_actual():
    tz = pytz.timezone(ZONA_HORARIA)
    return datetime.now(tz).strftime("%I:%M %p")

def traducir_error(msg):
    if not msg:
        return "Error desconocido"
    for clave, traduccion in ERRORES.items():
        if clave in str(msg):
            return f"`{clave}` — {traduccion}"
    return str(msg)

def sugerencia_error(msg):
    if not msg:
        return "docker compose ps` para ver el estado de los contenedores"
    for clave, sugerencia in SUGERENCIAS.items():
        if clave in str(msg):
            return sugerencia
    return "docker compose ps` para ver el estado de los contenedores"

@app.route("/webhook", methods=["POST"])
def recibir_webhook():
    data = request.get_json(force=True, silent=True)
    if not data or not isinstance(data, dict):
        return jsonify({"ok": True, "msg": "test ignorado"}), 200

    monitor = data.get("monitor")
    if not monitor or not isinstance(monitor, dict):
        return jsonify({"ok": True, "msg": "sin datos de monitor"}), 200

    monitor_name = monitor.get("name", "Sitio Incidencias")
    monitor_url  = monitor.get("url", "https://incidenciasupse.site")
    heartbeat    = data.get("heartbeat") or {}
    status       = heartbeat.get("status")
    msg_error    = heartbeat.get("msg", "")
    ping_ms      = heartbeat.get("ping")
    ahora        = hora_actual()

    if status == 0:
        caidas_activas[monitor_name] = datetime.utcnow()
        error_es   = traducir_error(msg_error)
        sugerencia = sugerencia_error(msg_error)
        embed = {
            "title": "🚨  ¡El sitio está caído!",
            "color": 0xFF0000,
            "fields": [
                {"name": "📍 Sitio",         "value": f"[{monitor_name}]({monitor_url})", "inline": True},
                {"name": "🕐 Se cayó a las", "value": ahora,    "inline": True},
                {"name": "❌ Error",          "value": error_es, "inline": False},
                {"name": "🔧 Qué revisar",   "value": f"Conéctate por SSH y corre:\n```\n{sugerencia}\n```", "inline": False},
            ],
            "footer": {"text": "Uptime Kuma • Monitor automático"},
            "timestamp": datetime.utcnow().isoformat(),
        }
        requests.post(DISCORD_WEBHOOK_URL, json={"content": "@here", "embeds": [embed]}, timeout=10)

    elif status == 1:
        tiempo_caido = ""
        if monitor_name in caidas_activas:
            diff = datetime.utcnow() - caidas_activas.pop(monitor_name)
            minutos = int(diff.total_seconds() // 60)
            segundos = int(diff.total_seconds() % 60)
            tiempo_caido = f"{minutos} min {segundos} seg" if minutos > 0 else f"{segundos} segundos"

        ping_str  = f"{ping_ms} ms" if ping_ms else "N/A"
        nota_ping = " ⚠️ (un poco lento, normal al arrancar)" if ping_ms and ping_ms > 2000 else ""
        fields = [
            {"name": "📍 Sitio",         "value": f"[{monitor_name}]({monitor_url})", "inline": True},
            {"name": "🕐 Volvió a las",  "value": ahora,                              "inline": True},
            {"name": "📶 Velocidad",     "value": f"`{ping_str}`{nota_ping}",         "inline": True},
        ]
        if tiempo_caido:
            fields.append({"name": "⏱️ Estuvo caído", "value": tiempo_caido, "inline": True})
        fields.append({"name": "😅 Estado", "value": "Todo bajo control, el sitio está funcionando de nuevo.", "inline": False})

        embed = {
            "title": "✅  ¡El sitio volvió!",
            "color": 0x00C851,
            "fields": fields,
            "footer": {"text": "Uptime Kuma • Monitor automático"},
            "timestamp": datetime.utcnow().isoformat(),
        }
        requests.post(DISCORD_WEBHOOK_URL, json={"embeds": [embed]}, timeout=10)

    return jsonify({"ok": True}), 200

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"}), 200

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False)
