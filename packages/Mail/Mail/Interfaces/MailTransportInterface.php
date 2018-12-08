<?php

interface MailTransportInterface
{
    public function send(Mail $mail, Config $customConfig = null);
}