# zaboy-rest 4.1.0

# Запуск тестов

Установите переменную окружения `'APP_ENV' = "dev"`;

Перед тем как запускать тесты, создайте файл `test.local.php` в `config/autoload`
и добавьте туда настройки для `httpDataStore` изменив localhost в параметре url так что бы по нему можно было получить доступ к веб-приложению.

Пример:

 ```php
    return [
        "dataStore" => [
            'testHttpClient' => [
                'class' => 'zaboy\rest\DataStore\HttpClient',
                'tableName' => 'test_res_http',
                'url' => 'http://localhost/api/rest/test_res_http',
                'options' => ['timeout' => 30]
            ],
            'testEavOverHttpClient' => [
                'class' => 'zaboy\rest\DataStore\HttpClient',
                 'url' => 'http://localhost/api/rest/entity_product',
                 'options' => ['timeout' => 30]
            ],
        ]
    ];
 ```

Скопируйте `index.php`и .htaccess из библиотеки в паблик директорию проекта.

Запустите скрипт `script/install.php`, он создаст таблицы в базе.

# Использование библиотеки

Что бы использовать данную библиотеку в своих приложениях следуйте [данной инструкции](INSTALL.md)

# Доскументация

[Детальная документация](doc/)

* [Запуск тестов](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/TESTS.md)
* [DataStore Абстрактные фабрики](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/DataStore%20Abstract%20Factory.md)
* [EAV](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/EAVDataStore.md)
* [EAV примеры](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/EAV%20example.md)
* [Composite](https://github.com/avz-cmf/zaboy-rest/blob/master/doc/Composite.md)