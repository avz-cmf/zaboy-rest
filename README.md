# zaboy-rest

# Test

Перед тем как запускать тесты, создайте файл `test.local.php` в `config/autoload`
и добавте туда настройки для `httpDataStore` и `testEavOverHttpClient` изменив `localhost` в параметре `url` так что бы по нему можно было получить доступ к веб-приложению.
Пример:

```
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
                  'url' => 'http://localhost:9090/api/rest/entity_product',
                  'options' => ['timeout' => 30]
       ],
  ]
];
```

Задайте параметры адаптера базы данных
Так же нужно запустить скрипт-установщик `src/install/Installer.php` из командной строки.

Пример:
`php src/install/Installer.php`

 