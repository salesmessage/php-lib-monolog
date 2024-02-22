# Log formatter for Monolog

Log formatter for Monolog is a library that extends the formatting capabilities provided by [Monolog](https://github.com/Seldaek/monolog).

## Installation

1. Make sure you have the Monolog library (Installed automatically with Laravel).

2. Add this repository to project's composer.json:

```json5
{
    // ...
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/salesmessage/php-lib-monolog"
        }
    ]
}
```

3. Install the library:

For Laravel 10 or above
```shell
composer require salesmessage/monolog "^2"
```
For Laravel 9 or below
```shell
composer require salesmessage/monolog "^1"
```

4. After installation library connected to app automatically via Laravel service provider

### Usage
You can create logs in laravel as usually via laravel tools