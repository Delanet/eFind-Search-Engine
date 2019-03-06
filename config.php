<?php

/*
 * config.php
 *
 * Common settings
 */


// Database settings
$dbs = array (

    'host'     => 'localhost',
    'name'     => '',
    'user'     => '',
    'password' => '',
    'cluster'  => 2,

);


// Search Engine Settings
$ses = array (

    // eFind.ru
    'efind' => array (
        'min_query_length' => 3,    // минимальная длина запроса
        'max_rows'         => 5,            // максимальное количество строк в ответе
        'currency'         => 'RUB',        // валюта цен товара
        'default'          => array (        // выходные параметры по умолчанию
            'instock'  => 0,
            'delivery' => 'по запросу',
        ),
        'vendors'          => array (        // данные по вендорам
            'number' => array (
                'instock'  => 1,
                'delivery' => false,
            ),
            'V10' => array (
                'instock'  => 0,
                'delivery' => '2-3 дня',
            ),
            'V11' => array (
                'instock'  => 0,
                'delivery' => '1-2 дня',
            ),
            'V12' => array (
                'instock'  => 0,
                'delivery' => '3-6 дней',
            ),
            'V13' => array (
                'instock'  => 0,
                'delivery' => '2-3 недели',
            ),
            'V15' => array (
                'instock'  => 1,
                'delivery' => false,
            ),
            'V20' => array (
                'instock'  => 1,
                'delivery' => false,
            ),
            'V30' => array (
                'instock'  => 0,
                'delivery' => '3-4 недели',
            ),
            'V40' => array (
                'instock'  => 0,
                'delivery' => '3-4 недели',
            ),
            'V50' => array (
                'instock'  => 0,
                'delivery' => '3-4 недели',
            ),
            'V55' => array (
                'instock'  => 0,
                'delivery' => '4-6 недель',
            ),
            'V60' => array (
                'instock'  => 0,
                'delivery' => '2-3 недели',
            ),
            'V70' => array (
                'instock'  => 0,
                'delivery' => '4-6 недель',
            ),
            'V80' => array (
                'instock'  => 0,
                'delivery' => '2-3 недели',
            ),
            'V90' => array(
                'instock'  => 0,
                'delivery' => '4 недели',
            ),
            
        ),
    ),
    
    // ChipFind.ru
    'chipfind' => array (
        'max_query_length' => 60,    // максимальная длина запроса
        'min_query_length' => 3,    // минимальная длина запроса
        'max_rows'         => 5,            // максимальное количество строк в ответе
        'currency'         => 'RUB',        // валюта цен товара
        'default'          => array (        // выходные параметры по умолчанию
            'instock'  => '0',
            'delivery' => 'по запросу',
        ),
        'vendors' => array (        // данные по вендорам
            'number' => array (
                'instock'  => '1',
                'delivery' => false,
            ),
            'V10' => array (
                'instock'  => '0',
                'delivery' => '2-3 дня',
            ),
            'V11' => array (
                'instock'  => '0',
                'delivery' => '1-2 дня',
            ),
            'V12' => array (
                'instock'  => '0',
                'delivery' => '3-6 дней',
            ),
            'V13' => array (
                'instock'  => '0',
                'delivery' => '2-3 недели',
            ),
            'V15' => array (
                'instock'  => '1',
                'delivery' => false,
            ),
            'V20' => array (
                'instock'  => '0',
                'delivery' => '1-2 дня',
            ),
            'V30' => array (
                'instock'  => '0',
                'delivery' => '3-4 недели',
            ),
            'V40' => array (
                'instock'  => '0',
                'delivery' => '3-4 недели',
            ),
            'V50' => array (
                'instock'  => '0',
                'delivery' => '3-4 недели',
            ),
            'V55' => array (
                'instock'  => '0',
                'delivery' => '4-6 недель',
            ),
            'V60' => array (
                'instock'  => '0',
                'delivery' => '2-3 недели',
            ),
            'V70' => array (
                'instock'  => '0',
                'delivery' => '4-6 недель',
            ),
            'V80' => array (
                'instock'  => '0',
                'delivery' => '1-2 недели',
            ),
            'V90' => array(
                'instock'  => '0',
                'delivery' => '4 недели',
            ),
            
        ),
    
    ),
    
    // eInfo.ru
    'einfo' => array (
    
    ),
    
    // rLocman.ru
    'rlocman' => array (
    
    ),
    
    // OptoChip.ru
    'optochip' => array (
    
    ),
    
);
