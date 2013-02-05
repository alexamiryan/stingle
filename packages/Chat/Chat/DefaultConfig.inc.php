<?php
$defaultConfig = array(	
						'AuxConfig' => array(
												'logMinutes' => 30,  // in minutes
												'invitationClearTimeout' => 5,  // in minutes
												'sessionClearTimeout' => 10,  // in minutes
												'chatUserClassName' => 'ChatUser'),
						'Objects' => array	(	
												'ChatInvitationManager' => 'chatInvMgr', 
												'ChatMessageManager' => 'chatMsgMgr', 
												'ChatSessionManager' => 'chatSessMgr' 
											)
					);
