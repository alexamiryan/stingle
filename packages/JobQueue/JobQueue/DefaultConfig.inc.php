<?php

$defaultConfig = [
	'AuxConfig' => [
		'maximumExecutionTime' => 60, // In seconds
		'intervalBetweenRuns' => 1, // In seconds
	],
	'Objects' => [
		'JobQueueManager' => 'jobQueue',
	],
    'Tables' => [
        'job_queue' => 1
    ]
];
