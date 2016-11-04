# Подключение zaboy-rest

Что бы установить библиотеку, добавьте в composer.json: 

	"require": {
			...
	        "avz-cmf/zaboy-rest": "^4.0",
			...

и выполните `composer update`.

Установите переменную окружения `'APP_ENV' = "dev"` или `'APP_ENV' = "prod"`;

Скопируйте файл `datastore.services.global.php` из библиотеки в `config/autoload` проекта.

Задайте параметры адаптера базы данных, например в файле `db.local.php` в `config/autoload` 

Пример:

	return [
	    'db' => [
	        'adapters' => [
	            'db' => [
	                'driver' => 'Pdo_Mysql',
	                'database' => 'zaboy_rest',
	                'username' => 'root',
	                'password' => 'pass'
	            ]
	        ]
	    ]
	  ]
	];

Запустите скрипт `script/install.php`, он создаст таблицы в базе.