<?php

function rupiah($data)
{
    $result = "Rp " . number_format($data, 2, ',', '.');
    return $result;
}
