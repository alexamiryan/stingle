<?php

$defaultConfig = [
	'AuxConfig' => [
		'maximumExecutionTime' => 60*30, // In seconds
		'intervalBetweenRuns' => 1, // In seconds
	],
	'Objects' => [
		'JobQueueManager' => 'jobQueue',
	]
];
