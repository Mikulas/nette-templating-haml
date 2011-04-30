Haml filter for Nette
=====================

* _Author_: Mikuláš Dítě
* _Copyright_: (c) Mikuláš Dítě 2011

Example
-------

```haml
!!! 5
%html
  %head
    %meta [name => robots, content => {$robots}, n:ifset => $robots]
    %title Nette Application Skeleton
    %script [type => text/javascript, src => {$basePath}/js/netteForms.js]

  %body
    %div [n:foreach => $flashes as $flash, class => flash {$flash->type}]
      {$flash->message}
    .note
      %ul
        %li Haml...
        %li Haml is here...
        %li Haml for your Nette!
    %article
      {include #content}
```

converts to

```html
<!DOCTYPE html> 
<html> 
	<head>
		<title>Nette Application Skeleton</title> 
		<script type="text/javascript" src="/haml/www/js/netteForms.js"></script> 
	</head> 
	<body>
		<div class="note"> 
			<ul> 
				<li>Haml...</li> 
				<li>Haml is here...</li> 
				<li>Haml for your Nette!</li> 
			</ul> 
		</div> 
		<article>
			<!-- tady by opravdu byl obsah #content bloku -->
		</article> 
	</body> 
</html> 
```

Requirements
------------

* Nette2.0-beta - https://nette.org/

Installation
------------

Easy as a pie. Just put these lines to your BasePresenter (and possibly BaseControl)

```php
public function templatePrepareFilters($template)
{
	$template->registerFilter(new Nette\Templating\Filters\Haml);
	$template->registerFilter(new Nette\Latte\Engine);
}
```

Just make sure Latte is not executed before Haml filter.

Usage
------------

Indent by either spaces or tabs, but not both. Remember, with Haml you only write the starting tag. The whole markup looks like this:

```haml
%element_name#id.class1.class2[attr1 => unescaped, attr2 => "quoted, , ,"] Text might be here or on the next line
```

Now, lets break it down. You may specify the element by omitting the %element_name, in which case it will default to div, which leaves you with the #id, .class, or both. At least one must be set however. Next comes the attributes. No escaping is required but for commas - if you need them, quote the whole value. Attributes are optional. Unlike element, textual values might be put on the end of the line as well as on the next (with relative indentation to the line it belongs to).

License - Original BSD
-----------------------

Copyright (c) Mikuláš Dítě, 2011
All rights reserved.

*Redistribution* and use in source and binary forms, with or without
modification, are *permitted* provided that the following conditions are met:

* Redistributions of source code *must retain* the above *copyright* notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* All advertising materials mentioning features or use of this software must display the following acknowledgement: This product includes software developed by the author.
* Neither the name of the author nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

This software is *provided* by author *_as_* *_is_* and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
