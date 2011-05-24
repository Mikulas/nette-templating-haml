<?php


// the identification of this site
define('SITE', '');
define('WWW_DIR', __DIR__ . '/../../../www');
define('APP_DIR', WWW_DIR . '/../app');
define('LIBS_DIR', WWW_DIR . '/../libs');
define('TEMP_DIR', WWW_DIR . '/../temp');


use Nette\Diagnostics\Debugger;
use Nette\Environment;
use Nette\Templating\Filters\Haml;

require LIBS_DIR . '/Nette/loader.php';

Debugger::$strictMode = TRUE;
Debugger::enable();

require LIBS_DIR . '/Wrappers/Debug.php';

Environment::loadConfig();

$application = Environment::getApplication();
$application->errorPresenter = 'Error';

$handle = opendir(__DIR__);
while (FALSE !== ($file = readDir($handle))) {
	if (!preg_match('~\.in$~im', $file))
		continue;

	$info = pathinfo(__DIR__ . "/$file");
	$filename = $info['filename'];

	if (isset($argv[1]) && $argv[1] != $filename)
		continue;

	echo "\n#####################\n\t$filename\n\n";
	$in = __DIR__ . "/$filename.in";
	$template = file_get_contents($in);
	$haml = new Haml();
	$output = $haml->parse($template);
	$out = __DIR__ . "/$filename.out";
	file_put_contents($out, $output);
	$expected = __DIR__ . "/$filename.exp";
	passthru("diff $out $expected");
}
