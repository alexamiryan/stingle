<?php
$defaultConfig = array(	
						'Objects' => array(	
											'JobQueueManager' => 'jobQueueMgr',
											),
						'Hooks' => array(
							'AfterPluginInit' => 'CollectJobQueuesDir',
						)
					);
