<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Заметки");
$APPLICATION->IncludeComponent(
    "main:notes",
    "",
    array(),
    false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");