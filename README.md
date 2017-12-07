DBAL Extensions
===============

**Installation**

Using composer:

```bash
composer require ijanki/doctrine-mysql-dbal-extensions
```

**Setup**

Configure doctrine dbal to use the wrapper class.

```yml
# config.yml
doctrine:
    dbal:
        wrapper_class: 'Ijanki\DBAL\Connection'
```

List of Extensions
--------------------

- insertOnDuplicateKey

Credits
-------
INSPIRED BY https://github.com/yadakhov/insert-on-duplicate-key

License
-------

This library is licensed under the MIT License - see the `LICENSE` file for details
