<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies confiables
    |--------------------------------------------------------------------------
    |
    | IPs o rangos CIDR de los proxies que hay delante de la app, separados por
    | coma. Solo de ellos se aceptan las cabeceras X-Forwarded-*. Si la variable
    | no está definida no se confía en ninguno: la IP del cliente pasa a ser la
    | de la conexión TCP y un X-Forwarded-For inventado deja de contar para el
    | throttle. En producción la cadena es visitante → Cloudflare → cloudflared
    | → Nginx (contenedor), así que el valor es la subred de Docker.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
