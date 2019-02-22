<?php

interface MailTransportInterface
{
    public function send(Mail $mail, $configName = null);
}