<?php
$defaultConfig = array(
    'AuxConfig' => array('uploadDir' => 'uploads/attachs/',
        'attachmentsClearTimeout' => 4, // In Days
        'imageUploaderConfig' => array('saveFormat' => 'jpeg',
            'acceptedMimeTypes' => array('image/gif',
                'image/jpeg',
                'image/pjpeg',
                'image/png'
            ),
            'minimumSize' => array('largeSideMinSize' => 5, 'smallSideMinSize' => 5)
        )),
    'Objects' => array(
        'ConversationManager' => 'convMgr',
        'ConversationAttachmentManager' => 'convAttMgr'),
    'Tables' => [
        'conversation_attachments' => 1,
        'conversation_messages' => 1,
        'conversations' => 1
    ]
);
