<?php
require_once 'src/ChatHandler.php';

define('CHAT_NAME', 'Mundosmilies');
define('CHAT_PASS', 'novomundosmilies1379');

$chat = new ChatHandler(CHAT_NAME, CHAT_PASS);

#$chat->getStaffList();
#$chat->setOuter('https://i.imgur.com/jnCTnx4.png');
#$chat->setInner('https://i.imgur.com/vkiJhBu.png');
#$chat->setTransparent(false);
#$chat->setComments(true);
#$chat->setDescription('My chat description...');
#$chat->setTags('mundo,smilies,chat');
#$chat->setAdsLink('xat.com/Mundosmilies');
#$chat->setLanguage('en');
#$chat->setButtonName(0, 'Home');
#$chat->setButtonText(0, 'Hello, this is my first tab...');