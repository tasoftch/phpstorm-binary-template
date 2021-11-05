<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

require "vendor/autoload.php";

$BINARY_NAME = 'binary';
$INCLUDE_SHEBANG = 1;

$UPLOAD = 0;
$UPLOAD_INFO = [
	'IP_ADDR' => '192.168.200.1',
	'USER_NAME' => 'pi',
	"PASSWORD" => 'raspberry',
	'PATH' => "/home/pi/bin/$BINARY_NAME" . ($INCLUDE_SHEBANG ? "" : ".phar")
];


if(file_exists("dist/$BINARY_NAME.phar"))
    unlink("dist/$BINARY_NAME.phar");

$phar = new Phar("dist/$BINARY_NAME.phar");

$dirGenerator = function($dir, $pattern = "/^test|test\.\w+$/i") {
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
		$name = $file->getBasename();
		if($name[0] == '.')
			continue;

		if($pattern && preg_match($pattern, $name))
			continue;

		if(stripos($file->getRealPath(), '/test/') !== false)
			continue;
		if(stripos($file->getRealPath(), '/tests/') !== false)
			continue;

		yield $file->getPathname() => $file->getRealPath();
	}
};

$phar->buildFromIterator($dirGenerator("src"));
$phar->buildFromIterator($dirGenerator("vendor/composer"));
$phar->buildFromIterator($dirGenerator("vendor/psr"));
$phar->buildFromIterator($dirGenerator("vendor/symfony"));
$phar->addFile("vendor/autoload.php");
$phar->addFile("main.php", 'main.php');

// Include further vendor packages
//$phar->buildFromIterator($dirGenerator("vendor/ikarus"));
//$phar->buildFromIterator($dirGenerator("vendor/tasoft"));

$SB = $INCLUDE_SHEBANG ? "#!/usr/bin/php\n" : "";

$phar->setStub("$SB<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2021, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS \"AS IS\"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

Phar::mapPhar('$BINARY_NAME.phar');
Phar::interceptFileFuncs();

require(\"phar://$BINARY_NAME.phar/main.php\");
__HALT_COMPILER();?>");

if($UPLOAD) {
	shell_exec("sshpass -p \"${UPLOAD_INFO['PASSWORD']}\" scp dist/$BINARY_NAME.phar {$UPLOAD_INFO['USER_NAME']}@{$UPLOAD_INFO['IP_ADDR']}:{$UPLOAD_INFO['PATH']}");
}
echo "Done.";
