<?php
namespace kodeops\LaravelTelegramWrapper;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Exception;
use kodeops\LaravelTelegramWrapper\Jobs\NewTelegramUpdate;
use kodeops\LaravelTelegramWrapper\Exceptions\LaravelTelegramWrapperException;

class Utils
{
    public static function escapeMarkdownCharacters(string $message)
    {
        $replaces = [
            '_', 
            '*', 
            '[', 
            ']', 
            '(', 
            ')', 
            '~', 
            '`', 
            '>', 
            '#', 
            '+', 
            '-', 
            '=', 
            '|', 
            '{', 
            '}', 
            '.', 
            '!'
        ];
        $replaced = '';
        foreach ($replaces as $replace) {
            $replaced = str_replace($replace, "\\\\" . $replace, $message);
        }

        return $replaced;
    }
}
