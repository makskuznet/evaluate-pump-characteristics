<?php
include __DIR__.'/../DB/pumps.php';
include __DIR__.'/pump-functions.php';

$pumpID = $_POST["id"] ? (int)$_POST["id"] : 1;
$pump = getStageFromDB($pumpID);
$stages = $_POST["stages"] ? (int)$_POST["stages"] : 5;	//количество ступеней
$k = 17;  //необходимое кол-во значений (точность), должно быть не меньше 4, в теории
$valQuantity = $_POST["valQuantity"] ? (int)$_POST["valQuantity"] : 17;

$pos_min = null;
$pos_max = null;
$pos_BEP = null;
$Q = getQArray($pump, $valQuantity, $pos_min, $pos_max, $pos_BEP);
//echo "</br>","После подстановки: позиция Q_min=",$pos_min, ", позиция Q_max=",$pos_max, ", позиция Q_BEP=",$pos_BEP,"</br>";

$H = getHbyQ($Q, $pump, $valQuantity, $stages);
//echo "</br>"," Head = ", var_dump($H),"</br>";


$P = getPbyQ($Q, $pump, $valQuantity, $stages);
//echo "</br>"," Power = ", var_dump($P),"</br>";


$Eff = getEffbyQ($Q, $H, $P, $pump, $valQuantity);
//echo "</br>"," Efficiency = ", var_dump($Eff),"</br>";


$N = 2910;	//задаём необходимое количество об/мин для пересчёта посчитанных выше значений
$Q_new = recountQ($Q, $N, $valQuantity);
//echo "</br>"," Q_new = ", var_dump($Q_new),"</br>";


$H_new = recountH($H, $N, $valQuantity);
//echo "</br>"," H_new = ", var_dump($H_new),"</br>";


$P_new = recountP($P, $N, $valQuantity);
//echo "</br>"," P_new = ", var_dump($P_new),"</br>";

$resultArray = [
    "pump data from db" => $pump,
    "Q" => $Q,
    "Позиция мин. рекоменд. Q" => $pos_min,
    "Позиция макс. рекоменд. Q" => $pos_max,
    "Позиция Q BEP" => $pos_BEP,
    "Head" => $H,
    "Power" => $P,
    "Efficiency" => $Eff,
    "Q пересчитанное" => $Q_new, 
    "H пересчитанное" => $H_new,
    "P пересчитанное" => $P_new
];
echo json_encode($resultArray);
