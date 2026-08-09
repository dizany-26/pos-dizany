<?php

$origins = array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', (string) env(
        'PASSWORD_RESET_ALLOWED_ORIGINS',
        'http://localhost:8000,http://127.0.0.1:8000'
    ))
));

return [
    'allowed_origins' => array_values(array_unique($origins)),
    // localhost solo sirve en la misma PC. Los correos locales deben usar
    // una dirección alcanzable por los demás equipos de la red.
    'local_origin' => rtrim((string) env('PASSWORD_RESET_LOCAL_ORIGIN', 'http://127.0.0.1:8000'), '/'),
];
