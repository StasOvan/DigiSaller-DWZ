<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title> Статус заказа </title>
    <meta name="description" content="Сервис автоматического пополнения баланса steam, доступный любому пользователю, включая Россию.">
    <meta name="keywords" content="steam top up, пополнения баланса steam, Steam,cервис автоматического пополнения баланса steam, пополнить баланс ">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!--link rel="icon" href="img/favicon.ico"-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="css/main.min.css?v=23">
</head>

<?php

/* № заказа	Дата	Группа	Цена	Валюта	название това	ключ	покупатель	емайл */

// ### CONSTs #####
    require_once('config.php');
// ################

    $uniquecode = $_GET['uniquecode']; // уникальный код платежа
    if ( $uniquecode == "") // если пусто = выходим
        { echo "sds"; echo "<p style='color: #FFF; text-align: center;'>Отсутствует код</p>"; die;}


/*
// проверяем уникальный номер в файле storage.csv
    $r = file_get_contents("storage.csv");
    if ( strpos($r, $uniquecode) === false ) {
        $flag_first_enter = true;
        echo "dsasdf";
    }
    else {
        $flag_first_enter = false;
        echo "00000";
    }
*/    
    
// получаем токен
$flag_first_enter = true;

    if ($flag_first_enter) {

        $timestamp = time();
        $hashdata = ($_API_KEY . $timestamp);
        $sign = hash("sha256", $hashdata);

        $post_data = ["seller_id" => $_SELLER_ID, "timestamp" => $timestamp, "sign" => $sign];
        $data_json = json_encode($post_data); // переводим поля в формат JSON
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-type: application/json', 'Accept: application/json'],
                'content' => $data_json
            ]
        ];
        $context = stream_context_create($opts);
        $result = file_get_contents("https://api.digiseller.ru/api/apilogin", false, $context);

        $token = json_decode($result)->token; // токен готов


        // запрашиваем наличие $uniquecode
/*        
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => ['Content-type: application/json', 'Accept: application/json']
            ]
        ];
        $context = stream_context_create($opts);
        $result = file_get_contents("https://api.digiseller.ru/api/purchases/unique-code/{$uniquecode}?token={$token}", false, $context);
*/
        $result = file_get_contents("https://api.digiseller.ru/api/purchases/unique-code/{$uniquecode}?token={$token}");
        print_r($result);

        if ( strpos($result,'"retval":0') === false ) // если нет такой строки
            { echo "<p style='color: #FFF; text-align: center;'>Неверный код операции</p>"; /*die;*/ }

        $invoice_id = json_decode($result)->inv; // номер заказа
        $invoice_date = json_decode($result)->date_pay; // дата заказа
        $currency = json_decode($result)->type_curr;

        if ($currency == "WMZ") $currency = "USD";
        if ($currency == "WMR") $currency = "₽";
        if ($currency == "WME") $currency = "EURO";
        if ($currency == "WMX") $currency = "BTC";

        // получаем инфу
        $result = file_get_contents("https://api.digiseller.ru/api/purchase/info/{$invoice_id}?token={$token}");

        $json = json_decode($result);
        $purchase_date = $json->content->purchase_date; 
        
        $options = $json->content->options;
        $options = (array)$options[0];
        $user = $options["user_data"];
        
        $options = $json->content->buyer_info;
        $options = (array)$options;
        $email = $options["email"];
// !!!
        $user = $email;
// !!!
        $amount = $json->content->amount;
        $invoice_state = $json->content->invoice_state;

    }
    else // считываем данные по существуещему заказу из файла
    {
        $r = file_get_contents("storage.csv");
        //$r = substr($r, 0, strlen($r) - 2); 
        $arr_lines = explode(";//", $r);
        echo "len=  ". count($arr_lines)."\r\n";
        $arr = [];
        $c = 0;
        for ($i=0; $i < count($arr_lines); $i++) {
            $temp = explode("|%", $arr_lines[$i]);
            if (trim($temp[0]) == $uniquecode)
                {$arr[$c] = trim($arr_lines[$i]); $c++;}
        }

        $html = RenderTableOfCodes($arr, $uniquecode, $currency, $user, $email, false); // просто выводим из файла
        $html .= 'создано из БД';

        // это переменные для верхней таблицы
        $purchase_date = explode("|%", $arr[0])[1];
        $user = explode("|%", $arr[0])[7];
        $amount = explode("|%", $arr[0])[3];
        $currency = explode("|%", $arr[0])[4];
        $invoice_state = "Выполнен";
     
    }

?>

<style>
a {
    color: #96d0ed;
    text-decoration: none;
}
a:focus, a:hover {
    color: #d2f0ff;
    text-decoration: none;
}

#list-codes{
    color: #FFF;
}
.copy-button {
    background-color: #ec985b;
    color:black;
    padding: 0 15px;
    margin: 7px;
    border-radius: 3px;
}
input {
    background-color: #555;
    color: aliceblue;
    width: 90%;
    border: 0;
    padding: 2px 4px;
}
</style>

<body>

<!-- The video -->
<video autoplay muted loop id="myVideo">
<source src="back.mp4" type="video/mp4">
</video>
<style>
#myVideo {
    position: fixed;
    right: 0;
    bottom: 0;
    min-width: 100%;
    min-height: 100%;
    opacity: 0.4;
}
</style>

<div class="wrapper" style="position: absolute;">


    <div style="background-color: white;">
		<p style="background-color: aquamarine">Получено значение uniquecode: <?php echo $uniquecode; ?> </p>
		<p style="background-color: aquamarine">hashdata: <?php echo $hashdata; ?> </p>
		<p style="background-color: aquamarine">sign: <?php echo $sign; ?> </p>
		<p style="background-color: yellow">token: <?php echo $token; ?> </p>
		<p style="background-color: yellow"> <?php echo $result; ?> </p>
	<br>
		
    </div>
    <hr>


    <header class="header">

    </header>



    <main class="main">
       

    <section class="result">
        <div class="content">
            <h1 style="font-style: italic;">Статус заказа</h1>
            <div class="result__container">
                <p class="result__caption">номер заказа <span><?php echo $uniquecode; ?> </span></p>
                <div class="result__info">
                    <div>
                        <span>Номер заказа</span>
                        <p><?php echo $uniquecode; ?></p>
                    </div>
                    <div>
                        <span>Дата и время заказа</span>
                        <p><?php echo $invoice_date; ?></p>
                    </div>
                    <div>
                        <span>Указанный логин</span>
                        <p><?php echo $user; ?></p>
                    </div>
                    <div>
                        <span>Сумма заказа</span>
                        <p><?php echo $amount.' '.$currency; ?></p>
                    </div>
                    <div>
                        <span>Статус заказа</span>
                        <?php if ($invoice_state == "1") echo '<p class="text-proccess">В обработке</p>'; 
                                    $status='Ваш заказ пока не обработан. Ожидайте.' ?>
                        <?php if ($invoice_state == "2") echo '<p class="text-error">Отказ</p>'; 
                                    $status='Вам отказано! Обратитесь в техподдержку.' ?>
                        <?php if ($invoice_state == "3") echo '<p class="text-success">Выполнен</p>'; 
                                    $status='Ваш заказ был обработан и средства успешно зачислены.
                                    <div id="list-codes">Идет загрузка кодов ..</div><hr>' ?>

                    </div>
                </div>
                <div class="result__content">

                    <p class="result__content-caption">Информация</p>
                    <!--p class="result__text"><?php //echo $status;?></p-->
                    
                    <p class="result__content-category">Обратите внимание на статус Заказа:</p>
                    <p class="result__text">
                        <span class="text-process">В обработке</span> - в скором времени заказ будет обработан, пожалуйста ожидайте <br>
                        <span class="text-success">Выполнен</span> - средства успешно зачислены на ваш кошелек в Steam <br>
                        <span class="text-error">Ошибка</span> - в процессе обработки заказа возникли проблемы. Свяжитесь с технической поддержкой.
                    </p>
                    <p class="result__text">Возникли вопросы? Обратитесь в <a href="https://t.me/<?php echo $_TELEGRAM_NAME; ?>" target="_blank">"Техническую поддержку"</a>; при обращении обязательно укажите номер заказа.</p>
                </div>
            </div>
        </div>
    </section>
    <div class="popup-feedback" id="popup-feedback">
        <div class="popup-feedback__btn-exit" onclick="popup_feedback_close()">&times;</div>
        <h2 class="popup-feedback__title">Оставьте отзыв</h2>
        <div class="popup-feedback__links">
            <a href="/feedback" class="popup-feedback__link" onclick="popup_feedback_close()">в VK</a>
            <a href="scorecard/nakastore.ru.html" target="»_blank" class="popup-feedback__link" onclick="popup_feedback_close()">на myWot</a>
            <a href="info/index.htm" target="»_blank" class="popup-feedback__link" onclick="popup_feedback_close()">на Plati.ru</a>
        </div>
    </div>
    <script>
        const fade_in = (el, timeout, display) => {
            el.style.opacity = 0;
            el.style.display = display || 'block';
            el.style.transition = `opacity ${timeout}ms`;
            setTimeout(() => {
                el.style.opacity = 1;
            }, 10);
        };

        function popup_feedback_open(){
            fade_in(document.querySelector('#popup-feedback'), 500);
        }
        function popup_feedback_close(){
            document.querySelector('#popup-feedback').style.display = 'none';
        }
    </script>
    </main>

    <footer class="footer">
        <div class="content footer__container">
            <a href="/" class="footer__logo">
                <img src="img/logo_.png" alt="">
            </a>
            
            <!--a href="asp/certview.asp.html?wmid=636332883108" target="_blank">
                <img src="doc/Logo/v_blue_on_transp_ru.png" alt="Здесь находится аттестат нашего WM">
            </a-->

            <a href="https://t.me/<?php echo $_TELEGRAM_NAME; ?>" style="text-decoration: none;" target="_blank">
                <img style="width: 32px; " src="https://telegram.org/img/t_logo.svg">
            </a>
            
        </div>
    </footer>


</div>


<div class="popup" id="login">
    <p class="popup__caption">Где взять логин Steam?</p>
    <img src="img/popup_img.png" alt="">
    <p class="popup__text">Обратите внимание! Логин, это то, что вы указываете при входе в Steam. Если вы укажите
        неверный логин, то средства уйдут другому пользователю. 
    <a target="_blank" href="login/index.htm">Взять вы его можете тут</a></p>
    <button>Понятно</button>
</div>

<?php

// № заказа	Дата	SheetName	Цена	Валюта	название това	ключ	покупатель	емайл

    if ($flag_first_enter == true) { // если первый раз, то формируем $html
        
        $r = file_get_contents(trim($exec_Apps_Script) . "?sum=" . 1000 . "&debug=true");
        $r = substr($r, 0, strlen($r) - 3);
        $arr = explode(";//", $r);
        $html = RenderTableOfCodes($arr, $uniquecode, $currency, $user, $email, true); // и записываем строки в файл storage
        $html .= 'создано из запроса';
        
    } else { 

    }

    function RenderTableOfCodes($arr, $number_order, $currency, $user, $email, $flag){ // flag = true -- это первый раз и добавить строки $line

        $html = 'Ваш список кодов: <br><br><table style="width: 100%;"><tr><th>для чего</th><th>цена</th><th>код</th></tr>';

        for ($i = 0; $i < count($arr); $i++) {
        
            if ($flag) {
                $sheetName = explode("|%", $arr[$i])[0];
                $descr = explode("|%", $arr[$i])[1];
                $price = explode("|%", $arr[$i])[2];
                $code = explode("|%", $arr[$i])[3];
                $date = "dfsdfsd";
            } else {
                $descr = explode("|%", $arr[$i])[5];
                $price = explode("|%", $arr[$i])[3];
                $code = explode("|%", $arr[$i])[6];
            }

            $html .= '<tr>';
            $html .= '<td>'.$descr.'</td>';
            $html .= '<td>'.$price.' '.$currency.'</td>';
            $html .= '<td><input id="'.$i.'" value="'.$code.'" readonly>';
            $html .= '<td><button onclick="f('.$i.')" class="copy-button">Скопировать код</button></td>';
            $html .= '</tr>';

            if ($flag) { // и записываем строку в storage.csv

                $line = $number_order . '|%' . $date . '|%' . $sheetName . '|%' . $price . '|%' . $currency . '|%' . $descr . '|%' . $code . '|%' . $user . '|%' . $email . ";//" . PHP_EOL;
                $fd = fopen("storage.csv", 'r+') or die("Ошибка открытия файла");
                
                if (flock($fd, LOCK_EX)) // установка исключительной блокировки на запись
                {
                    fseek($fd, 0, SEEK_END); //переход в конец файла
                    fwrite($fd, "$line") or die("Ошибка записи"); // запись
                    flock($fd, LOCK_UN); // снятие блокировки
                }
                
                fclose($fd);
            }

        } // end for

        $html .= '</table>';
        return $html;

    }
?>

<script>

    document.getElementById('list-codes').innerHTML = `<?php echo $html; ?>`;

    function f(id) {
        var copyText = document.getElementById(id);
        copyText.select();
        document.execCommand("copy");
        alert("Скопировано в буфер обмена: \r\n" + copyText.value);
    }

</script>

</body>
</html>
