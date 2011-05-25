# Haml filter for Nette

* _Author_: Mikuláš Dítě
* _Copyright_: (c) Mikuláš Dítě 2011

# Example


```haml
!!! 5
%html
  %head
    %meta [name => robots, content => {$robots}, n:ifset => $robots]
    %title Haml Example
    %script [type => text/javascript, src => {$basePath}/js/netteForms.js]

  %body
    %div [n:foreach => $flashes as $flash, class => flash {$flash->type}]
      =$flash->message
    .note
      %ul
        %li simple text
        %li =time()
        %li =$basePath
    %article
      {include #content}
    \%h3 this is not a node, it's escaped

    support for FormMacros:
    {form registration}
      =input name
      =input submit
    {/form}
```

converts to

```html
<!DOCTYPE html>
<html>
	<head>
		<meta name="robots" content="{$robots}" n:ifset="$robots">
		<title>Haml Example</title>
		<script type="text/javascript" src="{$basePath}/js/netteForms.js"></script></head>

	<body>
		<div n:foreach="$flashes as $flash" class="flash {$flash->type}">
			{=$flash->message}</div>
		<div class="note">
			<ul>
				<li>simple text</li>
				<li>{=time()}</li>
				<li>{=$basePath}</li></ul></div>
		<article>
			{include #content}</article>
		%h3 this is not a node, it's escaped

		support for FormMacros:
		{form registration}
		{input name}
		{input submit}
		{/form}</body></html>
```

# Requirements

* Nette Version 2.0 Alpha 2 or newer - http://nette.org/

# Installation

Easy as a pie. Just put these lines to your ```BasePresenter``` (and possibly ```BaseControl```)

```php
public function templatePrepareFilters($template)
{
	$template->registerFilter(new Nette\Templating\Filters\Haml);
	$template->registerFilter(new Nette\Latte\Engine);
}
```

Just make sure Latte is not executed before Haml filter.

# Usage

## Overview

Indent by either spaces or tabs, but not both. Remember, with Haml you only write the opening tag. The whole markup looks like this:

```haml
%element_name#id.class1.class2[attr1 => unescaped, attr2 => "quoted, , ,"] Text might be here or on the next line
```

## Detailed

Now, lets break it down. You may specify the element by omitting the %element_name, in which case it will default to div, which leaves you with the #id, .class, or both. At least one must be set however. Next comes the optional attributes in the brackets ```[attribute => value, another => "quoted, same"]```. No escaping is required but for commas - if you need them, quote the whole value. Textual values might be put on the end of the line as well as on the next.

## Inline macros

This feature allows you to omit braces around latte macros as long as you start the line with equation mark

```haml
%h1 = $title
.div [n:foreach => $articles as $article]
	%em = $article->label
	%span = $article->published|date
%footer
	functions:
	= date('Y')
```

goes for

```html
<h1>{=$title}</h1>
<div n:foreach="$articles as $article" class="div">
	<em>{=$article->label}</em>
	<span>{=$article->published|date}</span></div>
<footer>
	functions:
	{=date('Y')}</footer>
```

## Indenting

The parser tries the very best to comprehend the indent and even some super crazy stuff such this is parsed correctly:

```haml
{foreach $products as $product}
	%header = $product|helper
		this text has level 1, as the line above
			so does this one and the node below
			%h2
				this text finally has level 2
				again the same
			this is level 2
				and this one as well, and that's ok
{/foreach}
```
Yet I believe you would rather put it down flat:

```haml
{foreach $products as $product}
	%header = $product|helper
	this text has level 1, as the line above
	so does this one and the node below
	%h2
		this text finally has level 2
		again the same
	fine now
	and this one as well, but that's ok
{/foreach}
```

# License - Original BSD

Copyright (c) Mikuláš Dítě, 2011
All rights reserved.

*Redistribution* and use in source and binary forms, with or without
modification, are *permitted* provided that the following conditions are met:

* Redistributions of source code *must retain* the above *copyright* notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* All advertising materials mentioning features or use of this software must display the following acknowledgement: This product includes software developed by the author.
* Neither the name of the author nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

This software is *provided* by author *_as_* *_is_* and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
