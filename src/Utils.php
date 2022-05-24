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

        foreach ($replaces as $replace) {
            $message = str_replace($replace, "\\\{$replace}", $replace);
        }

        return $replace;
    }
}