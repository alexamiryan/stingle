<?php
$defaultConfig = [
    'AuxConfig' => [
        'requestLogEnabled' => false,
        'saveIPInCustomLog' => true,
        'isUsingSessions' => true
    ],
    'Hooks' => [
        'AfterOutput' => 'LogRequest',
        'DBLog' => 'DBLog'
    ],
    'Tables' => [
        'log_mixed' => 1,
        'log_requests' => 1
    ]
];
