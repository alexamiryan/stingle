<?php

function recordRequest($type) {
	HookManager::callHook("RecordRequest", $type);
}
