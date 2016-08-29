<?php

namespace mpyw\HardBotter\Traits;

function trigger_error($message, $level)
{
    $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG'][] = [$message, $level];
}
