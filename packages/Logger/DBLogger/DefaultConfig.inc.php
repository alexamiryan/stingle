<?php
$defaultConfig = [
    'AuxConfig' => [
        'requestLogEnabled' => false,
        'saveIPInCustomLog' => true,
        'isUsingSessions' => true
    ],
    'Hooks' => [
        'AfterOutput' => 'LogRequest'
    ]
];
