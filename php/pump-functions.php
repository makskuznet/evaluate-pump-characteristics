<?php
const MPD_2_BPD = 6.289810770432;
const BPD_2_MPD = 0.158987294928;
const M_2_FT = 3.2808333333465;
const FT_2_M = 0.3048006096;
const HP_2_KW = 0.745699872; // 1 hp(I) = 745.699872 W = 0.745699872 kW // HP - horsepower
const KW_2_HP = 1.341022;

/**
 * getQArray
 *
 * получаем массив значений Q, исходя из рекомендуемых подач и максимальной подачи,
 * во втором параметре задаём количество точек (=точность),
 * остальные параметры это позиции Qmin,Qmax и ВЕР, которые мы узнаём в функции;
 *
 *
 * @param array $pump
 * @param int $valQuantity
 * @param int $pos_min
 * @param int $pos_max
 * @param int $pos_BEP
 * @return array $Q
 */
function getQArray($pump, $valQuantity, &$pos_min, &$pos_max, &$pos_BEP){
    $stepSizeQ = $pump["Max_Plotted_Prod"]/($valQuantity-1);//шаг

    $Q = []; 
    $Q[0] = 0;
    for ($i = 1; $i<=($valQuantity-1); $i++){
        $Q[$i] = $Q[$i-1] + $stepSizeQ;
    }
    //echo "Q начальное = ",var_dump($Q),"</br>";			//здесь выводится Q без подстановок Qmin, Qmax и BEP
    $minQ_dif = $pump["minQ"] - $Q[0];						//задаём разницу между значениями
    $maxQ_dif = $pump["maxQ"] - $Q[1];

    for ($i = 1; $i <= ($valQuantity-2); $i++){	//проходим циклом по значениям и находим самые близкие значения Q к рекомендуемым и BEP
        if (abs($Q[$i] - $pump["minQ"]) < $minQ_dif){
            $minQ_dif = abs($Q[$i] - $pump["minQ"]);
            $pos_min = $i;
        }
        if (abs($Q[$i+1] - $pump["maxQ"]) < $maxQ_dif){
            $maxQ_dif = abs($Q[$i+1] - $pump["maxQ"]);
            $pos_max = $i+1;
        }
    }
    $BEP_dif = $pump["BEP"] - $Q[$pos_min];
    for ($i = $pos_min + 1; $i < $pos_max; $i++){
        if (abs($pump["BEP"] - $Q[$i]) < $BEP_dif){
            $BEP_dif = abs($pump["BEP"] - $Q[$i]);
            $pos_BEP = $i;
        }
    }
    $Q[$pos_min] = $pump["minQ"];  					//меняем самые близкие значения на рекомендуемые и BEP (для точного построения вертикальных линий на графике)
    $Q[$pos_max] = $pump["maxQ"];
    $Q[$pos_BEP] = $pump["BEP"];
    //echo "</br>","Q после подстановки = ", var_dump($Q),"</br>";
    return $Q;
}
// универсальная функция, возвращает равномерные интервалы, пока не будем привязываться к Q
function getPoints($quantity,$maxValue,$minValue=0){
    // приводим данные, т.к. неизвестно что введет юзер
    $quantity = (int)$quantity;
    $maxValue = (float)$maxValue;
    $minValue = (float)$minValue;
    // проверяем данные
    if($minValue>=$maxValue)
        throw new Exception('начало больше конца');
    if($quantity<3)
        throw new Exception('бесмысленно получить '.$quantity.' точки');

    $values=[]; // сюда сохраним результат
    $values[0]=$minValue;
    $stepLength = $maxValue/($quantity-1);
    for ($i = 1; $i<($quantity); $i++){
        $values[$i] = $values[$i-1] + $stepLength;
    }
    return $values;
}
//менее универсальная функция, больше привязанная к Q, учитываем рекомендованные значения
function getQPoints($quantity,$minRecom,$bep,$maxRecom,$maxAllowed){
    // приводим данные, т.к. неизвестно что введет юзер
    $quantity = (int)$quantity;
    $maxAllowed = (float)$maxAllowed;
    $minRecom = (float)$minRecom;
    $maxRecom = (float)$maxRecom;
    $bep = (float)$bep;

    $values=[]; // сюда сохраним результат
    $currentValue = 0; // текущее значение
    $values[0]=$currentValue; // первый элемент массива
    $stepLength = $maxAllowed/($quantity-1); // длина шага

    // проверяем данные
    if($maxAllowed<=$maxRecom || $maxAllowed<=$minRecom || $maxAllowed<=$bep)
        throw new Exception('макс. допустим. слишком мал');
    if($minRecom>=$maxRecom || $bep>=$maxRecom || $bep<=$minRecom)
        throw new Exception('рекоменд. значение(я) не корректны');
    if($quantity<5)
        throw new Exception('бесмысленно получить '.$quantity.' точки');
    if($quantity==5)
        return [0,$minRecom,$bep,$maxRecom,$maxAllowed];
    if($quantity<9)
        throw new Exception('для корректной работы нужно мин. 9 точек');
    if( ($bep-$minRecom < $stepLength) || ($maxRecom-$bep < $stepLength) || ($maxAllowed-$maxRecom < $stepLength)){
        // можно посчитать мин. рекоммендуемое количество точек для данных значений
        throw new Exception('слишком длинное расстояние между точками, минимум одни из 2х ключевых точек попадают в один шаг. Увеличьте количество точек до и более: '. getRecommendPointsQuantity($minRecom,$bep,$maxRecom,$maxAllowed));
    }


    for ($i = 1; $i<($quantity); $i++){
        $currentValue += $stepLength; // увеличиваем текущий шаг
        //если minRecom между предыдущим и текущим
        if($values[$i-1] < $minRecom && $minRecom <= $currentValue){
            //если отрезок между minRecom и предыдущим значением длиннее
            if(($minRecom - $values[$i-1]) > ($currentValue - $minRecom)){
                // то в текущий элемент массива запишем minRecom и прервем итерацию цикла
                $values[$i] = $minRecom;
                continue;
            }else{
                // иначе перезапишем предыдущий элемент на minRecom
                $values[$i-1] = $minRecom;
            }
        }
        //по аналогии maxRecom
        if($values[$i-1] < $maxRecom && $maxRecom <= $currentValue){
            if(($maxRecom - $values[$i-1]) > ($currentValue - $maxRecom)){
                $values[$i] = $maxRecom;
                continue;
            }else{
                $values[$i-1] = $maxRecom;
            }
        }
        //по аналогии bep
        if($values[$i-1] < $bep && $bep <= $currentValue){
            if(($bep - $values[$i-1]) > ($currentValue - $bep)){
                $values[$i] = $bep;
                continue;
            }else{
                $values[$i-1] = $bep;
            }
        }
        $values[$i] = $currentValue;
    }
    return $values;
}

function getRecommendPointsQuantity($minRecom,$bep,$maxRecom,$maxAllowed){
    $minLength = min($bep-$minRecom,$maxRecom-$bep,$maxAllowed-$maxRecom);
    return ceil($maxAllowed/$minLength)+1;
}
function wd($val){echo '<pre>';var_dump($val);echo '</pre>';}


/**
 * getHbyQ
 *
 * вычисляем напор по полиному;
 *
 * @param array $pump
 * @return array $H
 */
function getHbyQ($Q, $pump, $valQuantity, $stages){
    $H = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $H[$i] = (pow($Q[$i],6)*$pump["h7"] + pow($Q[$i],5)*$pump["h6"] + pow($Q[$i],4)*$pump["h5"] + pow($Q[$i],3)*$pump["h4"] + pow($Q	[$i],2)*$pump["h3"] + $Q[$i]*$pump["h2"] + $pump["h1"]) * $stages;
    }
    return $H;
}


/**
 * getPbyQ
 *
 * вычисляем мощность по полиному;
 *
 * @param array $pump
 * @return array $P
 */
function getPbyQ($Q, $pump, $valQuantity, $stages){
    $P = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $P[$i] = (pow($Q[$i],6)*$pump["b7"] + pow($Q[$i],5)*$pump["b6"] + pow($Q[$i],4)*$pump["b5"] + pow($Q[$i],3)*$pump["b4"] + pow($Q	[$i],2)*$pump["b3"] + $Q[$i]*$pump["b2"] + $pump["b1"]) * $stages;
    }
    return $P;
}


/**
 * getEffbyQ
 *
 * вычисляем КПД по полиному;
 *
 * @param array $Q
 * @return array $Eff
 */
function getEffbyQ($Q, $H, $P, $pump, $valQuantity){
    $Eff = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $Eff[$i] = 100 * $Q[$i] * $H[$i] / ($P[$i] * 135629.9);
    }
    return $Eff;
}

/**
 * recountQ
 *
 * пересчитываем Q для заданной частоты N (в об/мин), выходное значение в м^3/сут;
 *
 * @param array $Q
 * @return array $Q_new
 */
function recountQ($Q, $N, $valQuantity){
    $Q_new = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $Q_new[$i] = $Q[$i] * $N * 0.158987294928 / 3500;				//тут константа - коэф-т перевода баррелей в м^3, а 3500 - обороты исходного Q (считается, что они 	неизменные!!)
    }
    return $Q_new;
}

/**
 * convertQ_2mpd
 *
 * переводим массив значений - расход Q, баррель/сутки в м^3/сут,
 * при заданной частоте $N_request (в об/мин);
 * На выходе массив значений $Q2;
 * Входящие: Q, баррель/сутки
 * N_request, об/мин
 * N_nominal, об/мин, по умолчанию 3500
 *
 *
 * @param array $Q
 * @param int $N_request
 * @param int $N_nominal
 * @return array $Q2
 */
function convertQ_2mpd($Q, $N_request, $N_nominal = 3500){ //
    $Q2 = [];
    foreach($Q as $item){
        $Q2[] = $item * $N_request * BPD_2_MPD / $N_nominal;
    }
    return $Q2;
}

function mpd2bpd($val){
    return $val*MPD_2_BPD;
}
function bpd2mpd($val){
    return $val*MPD_2_BPD;
}
//здесь конвертируем массив
function convQ_2mpd($Q){
    return array_map('bpd2mpd', $Q);
}
function convQ_2bpd($Q){
    return array_map('mpd2bpd', $Q);
}
//а здесь пересчитываем с учетом чатоты
function convertQbyN($Q, $N_request, $N_nominal = 3500){
    $Q2 = [];
    foreach($Q as $item){
        $Q2[] = $item * $N_request / $N_nominal;
    }
    return $Q2;
}


/**
 * recountH
 *
 * пересчитываем Н, получаем напор в метрах;
 *
 * @param array $H
 * @return array $H_new
 */
function recountH($H, $N, $valQuantity){
    $H_new = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $H_new[$i] = $H[$i] * pow($N,2) / (pow(3500,2) * 3.28084);		//здесь аналогично коэф-т перевода метров в футы
    }
    return $H_new;
}


/**
 * recountH
 *
 * пересчитываем P, получаем мощность в кВт (было в л.с.);
 *
 * @param array $H
 * @return array $H_new
 */
function recountP($P, $N, $valQuantity){
    $P_new = array();
    for ($i = 0; $i <= ($valQuantity-1); $i++){
        $P_new[$i] = $P[$i] * pow($N,3) * 0.745 / pow(3500,3);		//здесь аналогично коэф-т перевода метров в футы
    }
    return $P_new;
}