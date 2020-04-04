<?php

$defaultConfig = [
	'AuxConfig' => [
	    'currentApiVersion' => 1,
        'replaceWithVersionIfAbsent' => 'CURR'
	],
	'Hooks' => [
        'BeforeRequestParser' => 'APIUrlParse'
	]
];
