<?php
/**
* Класс eFindSpeedSearch
*
* Быстрый поиск для eFind.ru
*
* LICENSE: GNU Lesser General Public License
*
* @copyright    Copyright (c) 2018 Панов Артур
* @contacts        Email: avpanov@yandex.ru
* @license        https://www.gnu.org/licenses/lgpl-3.0.en.html
* @file            efind.class.php
* @version        v1.10
*/

class eFindSpeedSearch
{

    /** @string        Строка входного запроса */
    private $strInputQuery = '';
    
    /** @string        Строка паттерна разбиения на слова
    * « » (пробел), «\t» (табуляция), «\n» (перевод строки), «,» «;» «*» «?» */
    protected $strWordPattern = '[\s\t\n-/,;*?]+';
    
    /** @string        Строка паттерна очистки от спец символов */
    protected $strSymbolPattern = '![^\p{L}\p{N}\s\t\n,;*?]*!u';
    
    /** @array        Массив слов, выделенных из входного запроса */
    private $arrWords = array ();
    
    /** @array        Массив кластеров, выделенных из входного запроса */
    private $arrClusters = array ();
    
    /** @string        Строка ID отобранных товаров */
    private $strProductIds = '';
    
    /** @string        Строка выходных данных */
    public $strOutputData;

    /**
    * Метод инициализирует поиск. Запрос должен быть в гете в параметре 'search'
    */
    function init_search($db, $s)
	{
        
        // Очистка запроса
        $this->strInputQuery = filter_input (INPUT_GET, 'search', FILTER_SANITIZE_STRING);
        
        // Очистка слов от спецсимволов и приведение к индексу
        $this->strInputQuery = preg_replace ($this->strSymbolPattern, '', trim ($this->strInputQuery));
        
        // Переводим в нижний регистр
        $this->strInputQuery = mb_strtolower ($this->strInputQuery, 'UTF-8');
        
        if (!empty ($this->strInputQuery) and mb_strlen ($this->strInputQuery, 'UTF-8') > $s['min_query_length']) {

            // Разбиваем строку поиска по символам-разделителям
            $this->arrWords = mb_split ($this->strWordPattern, $this->strInputQuery);
            $this->arrWords = array_filter (array_unique ($this->arrWords));
            
            // Выделение кластеров из начала и конца слов
            foreach ($this->arrWords as $word) {
                if (mb_strlen ($word, 'UTF-8') >= $db['cluster']) {
                    array_push ($this->arrClusters, mb_substr ($word, 0, $db['cluster']));
                    array_push ($this->arrClusters, mb_substr ($word, (-1) * $db['cluster']));
                }
            }

            // Разбиение по смысловым частям и выделение кластеров
            foreach ($this->arrWords as $word) {
                $literal = mb_split ("\p{N}+", $word);
                 foreach ($literal as $chank) {
                    $arrChankCluster = $this->str_split_unicode($chank, $db['cluster']);
                    foreach ($arrChankCluster as $val) {
                        array_push ($this->arrClusters, $val);
                    }    
                }
                $digital = mb_split ("\p{L}+", $word);
                 foreach ($digital as $chank) {
                    $arrChankCluster = $this->str_split_unicode($chank, $db['cluster']);
                    foreach ($arrChankCluster as $val) {
                        array_push ($this->arrClusters, $val);
                    }
                }
            }
            
            // Очищаем массив от пустых значений и дублей
            $this->arrClusters = array_filter (array_unique ($this->arrClusters));
            
        }
        
        return true;
    }
    
    
    /**
     * Метод разбиения строки по количеству символов (UNICODE)
     */
    function str_split_unicode($string, $length = 1)
	{
        $out_array = array ();
        $length_string = mb_strlen ($string, 'UTF-8');
        for ($i = 0; $i < $length_string; $i += $length) {
            $chank = mb_substr ($string, $i, $length, 'UTF-8');
            if (mb_strlen ($chank) == $length) {
                array_push ($out_array, $chank);
            }
        }
        return $out_array;
    }
    

    /**
    * Метод подключения к БД по данным в переменной $dbs в config.php
    */
    function openDB($db)
	{
        $this->mysqli = new mysqli($db['host'], $db['user'], $db['password'], $db['name']);
        if (mysqli_connect_error ()) {
            error_log ('Connect Error (' . mysqli_connect_errno () . ') ' . mysqli_connect_error ());
            return false;
        }
        $this->mysqli->query('SET NAMES "utf8"');
        
        return true;
    }
    
    /**
     * Метод возвращает ответ с ошибкой 500
     */
    function error500()
	{
           header ("HTTP/1.1 500 Internal Server Error");
        return "<h1>500 Internal Server Error</h1>\nCould not connect to database";
    }

    
    /**
     * Метод делает поиск по нужному алгоритму и заполняет результатом свойство
     */
    function speed_search($s)
	{

        // Строка запроса кластеров
        $strClusters = "'" . implode ("','", $this->arrClusters) . "'";

        // Учитываем совпадение запроса с найденными строками
        $strQuery = '';
        foreach ($this->arrWords as $word) {
            $strQuery .= ' AND `sapi_index` LIKE "%'.$word.'%"';
        }

        // Запрос на выборку
        $query = "SELECT `sapi_product`, `sapi_pcode`, `sapi_amount`
            FROM `cscart_sapi`
            WHERE `sapi_product`
            IN (SELECT `product_id`
            FROM `cscart_speedup_products_clusters`
            WHERE `cluster_id`
            IN (SELECT `cluster_id`
            FROM `cscart_speedup_clusters`
            WHERE `cluster`
            IN ($strClusters)))
            $strQuery";        
        
        // Делаем выборку из БД
        if ($result = $this->mysqli->query($query)) {
        
            // Задаем порядок следования складов ('local' => 1С, V15, V20, 'external' => ДМС)
            $arrSapiResults = array (
                'local' => array (),
                'external' => array ()
            );
            
            // Разбираем выборку из БД
            while ($row = $result->fetch_assoc()) {
                
                // Только для товаров из 1С
                if (is_numeric ($row['sapi_pcode']))  {
                    $intInstock = $s['vendors']['number']['instock'];
                } else {
                    $strVendor = strstr ($row['sapi_pcode'], '~', true);
                    $intInstock = $s['vendors'][$strVendor]['instock'];
                }
                
                // На локальных складах
                if ($intInstock)  {
                    // Только для товаров в наличии (складские)
                    if ($row['sapi_amount'] > 0) {
                        $arrSapiResults['local'][$row['sapi_product']] = array (
                            'sapi_pcode' => $row['sapi_pcode'],
                            'sapi_amount' => $row['sapi_amount']
                        );
                    }
                } else {
                    // Для остальных вендоров (ДМС, склады не в Москве)
                    $arrSapiResults['external'][$row['sapi_product']] = array (
                        'sapi_pcode' => $row['sapi_pcode'],
                        'sapi_amount' => $row['sapi_amount']
                    );
                }
            }
            
            // Подсчитываем кол-во строк из локальных складов
            $local_rows = count ($arrSapiResults['local']);
            
            // Пользовательская функция сортировки массива по значениям (DESC)
            function descending_sort($a, $b)
			{
                return $a['sapi_pcode'] < $b['sapi_pcode'];
            }
            
            // Пользовательская функция сортировки массива по значениям (ASC)
            function ascending_sort($a, $b)
			{
                return $a['sapi_pcode'] > $b['sapi_pcode'];
            }
            
            // Сортировка товаров из локальных складов
            if ($local_rows > 0) {
                uasort ($arrSapiResults['local'], "ascending_sort");
            }
        
            // Сортировка товаров с ДМС, если товаров на собственном складе меньше требуемого кол-ва строк
            if (count ($arrSapiResults['external']) > 0 and $local_rows < $s['max_rows']) {
                uasort ($arrSapiResults['external'], "descending_sort");
            }

            // Вычленяем только нужные строки по количеству в ответе
            $i = 1;
            foreach ($arrSapiResults as $stock) {
                foreach ($stock as $id => $val) {
                    if ($i > $s['max_rows']) break;
                    $this->strProductIds .= "$id,";
                    ++$i;
                }
            }
            
            // Удаляем лишнюю последнюю запятую
            $this->strProductIds = substr ($this->strProductIds, 0, -1);    
        }
        
        return true;
    }
    
    
    
    //------------------------------------------------------------------------------------------------------------
    /**
     * Актуальная версия поиска eFind
     * Метод отдает ответ в виде строки (xml документ)
     */

    function search_response($s)
	{
        
        // Пустая строка для ответа при отсутствии результатов
        $this->strOutputData = '';

        // Что-то нашлось в БД?
        if (!empty ($this->strProductIds)) {
            
            // Запрос на выборку всех данных
            $result = $this->mysqli->query("SELECT `sapidata_product`, `sapidata_pcode`, `sapidata_name`, `sapidata_brand`, `sapidata_descr`, `sapidata_uri`, `sapidata_pdf`, `sapidata_price1`, `sapidata_amount1`, `sapidata_price2`, `sapidata_amount2`, `sapidata_price3`, `sapidata_amount3`, `sapidata_moq`, `sapidata_mpq`, `sapidata_leadtime`, `sapidata_package`, `sapidata_dc`, `sapidata_amount` FROM `cscart_sapidata` WHERE `sapidata_product` IN ({$this->strProductIds})");
            
            // Восстанавливаем правильный порядок строк
            $arrRowsOrder = explode (",", $this->strProductIds);
            
            // Разбираем в массив ответ сервера
            while ($row = $result->fetch_assoc()) {
                $arrRows[$row['sapidata_product']] = $row;
            }

            // Разбираем ответ
            foreach ($arrRowsOrder as $id) {
                
                // Удаляем дубли розничных и мелкооптовых количеств
                if ($arrRows[$id]['sapidata_amount1'] == $arrRows[$id]['sapidata_amount2']) {
                    $arrRows[$id]['sapidata_price1'] = 0;
                }
                
                // При наличии MOQ > розничного кол-ва - проводим пересмотр количеств
                if ($arrRows[$id]['sapidata_moq'] > $arrRows[$id]['sapidata_amount1']) {
                    
                    $arrRows[$id]['sapidata_price1'] = 0;
                    $arrRows[$id]['sapidata_amount1'] = 0;

                    if ($arrRows[$id]['sapidata_amount2'] > $arrRows[$id]['sapidata_moq']) {
                        $arrRows[$id]['sapidata_amount2'] = $arrRows[$id]['sapidata_moq'];
                    } else {
                        $arrRows[$id]['sapidata_price2'] = 0;
                        $arrRows[$id]['sapidata_amount2'] = 0;
                        $arrRows[$id]['sapidata_amount3'] = $arrRows[$id]['sapidata_moq'];
                    }
                }

                // Начинаем формирование вывода
                $this->strOutputData .= '<item>';

                // Название позиции
                $part_number = htmlspecialchars ($arrRows[$id]['sapidata_name']);
                $this->strOutputData .= "<part>{$part_number}</part>";

                // Производитель
                if (!empty ($arrRows[$id]['sapidata_brand'])) {
                    $manufacturer = htmlspecialchars ($arrRows[$id]['sapidata_brand']);
                    $this->strOutputData .= "<mfg>{$manufacturer}</mfg>";
                }
                
                // Краткое описание
                if (!empty ($arrRows[$id]['sapidata_descr'])) {
                    $note = htmlspecialchars ($arrRows[$id]['sapidata_descr']);
                    $this->strOutputData .= "<note>{$note}</note>";
                }

                // Ссылка на изображение товара (описание)
                $this->strOutputData .= "<img>{$arrRows[$id]['sapidata_uri']}</img>";
                
                // Ссылка на даташит товара (PDF)
                if (!empty ($arrRows[$id]['sapidata_pdf'])) {
                    $this->strOutputData .= "<pdf>{$arrRows[$id]['sapidata_pdf']}</pdf>";
                } else {
                    $this->strOutputData .= "<pdf>{$arrRows[$id]['sapidata_uri']}</pdf>";
                }
                
                // Ссылка на карточку товара
                $this->strOutputData .= "<url>{$arrRows[$id]['sapidata_uri']}</url>";

                // Валюта цен
                $this->strOutputData .= "<cur>{$s['currency']}</cur>";

                // Розничная цена
                if (!empty ((float) $arrRows[$id]['sapidata_price1'])) {
                    $this->strOutputData .= "<pb qty=\"{$arrRows[$id]['sapidata_amount1']}\">{$arrRows[$id]['sapidata_price1']}</pb>";
                }

                // Мелкооптовая цена
                if (!empty ((float) $arrRows[$id]['sapidata_price2'])) {
                    $this->strOutputData .= "<pb qty=\"{$arrRows[$id]['sapidata_amount2']}\">{$arrRows[$id]['sapidata_price2']}</pb>";
                }

                // Оптовая цена
                if (!empty ((float) $arrRows[$id]['sapidata_price3'])) {
                    $this->strOutputData .= "<pb qty=\"{$arrRows[$id]['sapidata_amount3']}\">{$arrRows[$id]['sapidata_price3']}</pb>";
                }
                
                // Количество в упаковке
                if ($arrRows[$id]['sapidata_mpq'] > 0) {
                    $this->strOutputData .= "<mpq>{$arrRows[$id]['sapidata_mpq']}</mpq>";
                }
                
                // Минимальный заказ
                if ($arrRows[$id]['sapidata_moq'] > 0) {
                    $this->strOutputData .= "<moq>{$arrRows[$id]['sapidata_moq']}</moq>";
                }
                
                // D/C - дата производства
                if (!empty ($arrRows[$id]['sapidata_dc'])) {
                    $this->strOutputData .= "<dc>{$arrRows[$id]['sapidata_dc']}</dc>";
                }
                
                // Корпус товара
                if (!empty ($arrRows[$id]['sapidata_package'])) {
                    $pkg = htmlspecialchars ($arrRows[$id]['sapidata_package']);
                    $this->strOutputData .= "<pkg>{$pkg}</pkg>";
                }

                // Товары с цифровым sapidata_pcode (1С)
                if (is_numeric ($arrRows[$id]['sapidata_pcode'])) {
                    // Состояние склада > 0 (в наличии)
                    if (!empty ($arrRows[$id]['sapidata_amount'])) {
                        // На складе в Москве
                        $strInStock = $s['vendors']['number']['instock'];
                        // Доставка
                        $strDelivery = $s['vendors']['number']['delivery'];
                    } else {
                        $strInStock = $s['default']['instock'];
                        $strDelivery = $s['default']['delivery'];
                    }
                } else {
                        
                    // Обозначение вендора (V10, V11, V12, ...)
                    $strVendor = strstr ($arrRows[$id]['sapidata_pcode'], '~', true);
                    
                    // Сроки поставки до склада в Москве
                    $strInStock = $s['vendors'][$strVendor]['instock'];

                    // Доставка
                    $strDelivery = $s['vendors'][$strVendor]['delivery'];
                }
                
                // Добавляем stock
                $this->strOutputData .= "<stock>{$arrRows[$id]['sapidata_amount']}</stock>";
                
                // Добавляем instok и завершаем строку
                $this->strOutputData .= "<instock>{$strInStock}</instock>";
                
                // Добавляем сроки доставки
                if ($strDelivery) {
                    $this->strOutputData .= "<dlv>{$strDelivery}</dlv>";
                }
                
                // Завершаем строку
                $this->strOutputData .= '</item>';

            }
        }

        // Возвращаем XML-ответ
        return $this->strOutputData;
    }

}
