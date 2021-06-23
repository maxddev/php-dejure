# php-dejure
[![Release](https://img.shields.io/github/release/S1SYPHOS/php-dejure.svg)](https://github.com/S1SYPHOS/php-dejure/releases) [![License](https://img.shields.io/github/license/S1SYPHOS/php-dejure.svg)](https://github.com/S1SYPHOS/php-dejure/blob/master/LICENSE) [![Issues](https://img.shields.io/github/issues/S1SYPHOS/php-dejure.svg)](https://github.com/S1SYPHOS/php-dejure/issues) [![Status](https://travis-ci.org/S1SYPHOS/php-dejure.svg?branch=master)](https://travis-ci.org/S1SYPHOS/php-dejure)

A PHP library for linking legal norms in texts with [dejure.org](https://dejure.org).


## What

This library is an OOP port of `vernetzungsfunction.inc.php`, which can be [downloaded here](https://dejure.org/vernetzung.html).


## Why

While including this file works fine, I wanted to take the object-oriented approach, stuffing all its logic into a class, with setters & getters etc.


## How

Install this package with [Composer](https://getcomposer.org):

```text
composer require S1SYPHOS/php-dejure
```

An example implementation could look something like this:

```php
<?php

require_once('vendor/autoload.php');

use S1SYPHOS\DejureOnline;

$object = new DejureOnline();

$object->setProvider('MyDejureTest');
$object->setMail('hello@mydomain.com');

$text  = '<div>';
$text .= 'This is a <strong>simple</strong> HTML text.';
$text .= 'It contains legal norms, like § Art. 12 GG.';
$text .= '.. or § 433 BGB!';
$text .= '</div>';

echo $object->dejurify($text);
```


## Roadmap

- [ ] Add tests
- [x] ~~Adding checks to `__construct`~~
- [x] ~~Attempt cache directory creation~~
- [x] ~~Improve code~~
- [ ] Improve code more
- [ ] Translate code (almost done)
- [ ] `join` paths, so trailing slash is no longer required


**Happy coding!**
