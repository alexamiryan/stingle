<?php
$CurrentHostName = HostManager::getHostName();
$hostConfig = ConfigManager::getConfig("Host")->AuxConfig;

$host = new Host();
$host->host = $CurrentHostName;

HostManager::addHost($host);

$host = new Host();
$host->host = $hostConfig->cgiHost;

HostManager::addHost($host);