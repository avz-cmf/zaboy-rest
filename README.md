# zaboy-rest

# Test

Перед тем как запускать тесты, создайте файл test.local.php в config/autoload
и добавте туда настройки для httpDataStore изменив localhost в параметре url так что бы по нему можно было получить доступ к веб-приложению.

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
  ]
];
```

Задайте параметры адаптера базы данных

Скопируйте `index.php` в паблик директорию проекта.

 