<?php
return [
    'id' => 'master-theme',
    'version' => '1.0.0',
    'author' => 'Andrius Paulavičius',
    'name' => 'Master Theme',
    'configs' => './configs',
    'locales' => './locales',
    'resources' =>'./resources',
    'templates' => './templates',
    'vendor'=>'Dvelum',
    'autoloader'=> [
        './classes'
    ],
    'objects' =>[
    ],
    //'post-install'=>'\\Dvelum\\Articles\\Installer'
];