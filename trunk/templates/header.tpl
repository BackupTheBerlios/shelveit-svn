<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
	<title>Library of Congress Search</title>
  </head>
  <body>
	{foreach from=$messages.infos item=message}
	Info: {$message}<br />
	{/foreach}
	
	{foreach from=$messages.warnings item=message}
	Warning: {$message}<br />
	{/foreach}
	
	{foreach from=$messages.errors item=message}
	Error: {$message}<br />
	{/foreach}
	