# redis-test
Основная логика - файл app/Console/Commands/Run.php 

## Установка
1) Клонировать репозиторий и перейти в директорию проекта
2) Выполнить команду composer install
3) в файле config/app.php прописать значения interval и generator_timeout
4) в файле config/database.php в секции redis прописать параметры соединения с редисом (уже прописаны redislabs по умолчанию, так что можно не трогать)
5) выполнить php artisan run для запуска
6) выполнить php artisan stats для просмотра статистики либо php artisan reset для сброса БД
