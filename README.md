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


## Roadmap

- [ ] Add tests
- [ ] Adding checks to `__construct`
- [ ] Attempt cache directory creation
- [ ] Translate code
- [ ] Further optimize code


**Happy coding!**
