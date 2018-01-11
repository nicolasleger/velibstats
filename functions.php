<?php

function displayCodeStation($code)
{
    if($code < 10000)
        return '0'.$code;
    return $code;
}