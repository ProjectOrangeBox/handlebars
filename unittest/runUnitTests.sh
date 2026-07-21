#!/bin/sh

../../vendor/bin/phpunit --colors --testdox --bootstrap bootstrap.php --testdox-html results.html ./tests
