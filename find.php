<?php

// Подключаем файл конфигурации
require_once ("config.php");

// Подгружаем класс кластерного поиска
require_once ("classes/efind.class.php");

// Создаем новый объект
$sapi = new eFindSpeedSearch();

// Очистка инициализации запроса
$sapi->init_search($dbs, $ses['efind']);

// Подключаемся к БД
if (!$sapi->openDB($dbs)) {
	// Ошибка подключения к БД
    echo $sapi->error500();
    exit;
}

// Старт быстрого поиска
$sapi->speed_search($ses['efind']);

$xmlResponce = $sapi->search_response($ses['efind']);

// Вывод ответа
header ("Content-type: application/xml");

// Выводим содержимое ответа поиска
echo '<?xml version="1.0" encoding="utf-8" ?><data version="2.0">' . $xmlResponce . '</data>';

// Завершаем выполнение скрипта
exit;

?>
