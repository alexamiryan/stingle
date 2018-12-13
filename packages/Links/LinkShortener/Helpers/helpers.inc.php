<?php

function shortenLink($url = '', $expires = null) {
	return Reg::get('linkShortener')->shortenUrl($url, $expires);
}
