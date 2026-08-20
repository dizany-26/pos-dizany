<?php

return [
    'environments' => [
        'beta' => [
            'label' => 'Pruebas (SUNAT Beta)',
            'bill_service' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
            'sol_user' => 'MODDATOS',
            'sol_password' => 'MODDATOS',
        ],
        'production' => [
            'label' => 'Producción',
            'bill_service' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
        ],
    ],
    'production_locked' => true,
];
