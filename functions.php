<?php
include_once('carte.php');

function displayCodeStation($code)
{
    if($code < 10000)
        return '0'.$code;
    return $code;
}

function getCommuneStation($codeStation)
{
    global $pdo;

    $res = $pdo->query('SELECT insee FROM tranche WHERE debut <= "'.$codeStation.'" AND fin >= "'.$codeStation.'"');
    $ligne = $res->fetch();

    if(is_null($ligne))
        return null;
    
    return $ligne['insee'];
}
?>