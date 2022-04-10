<?php
namespace kodeops\LaravelTelegramWrapper;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Exception;
use kodeops\LaravelTelegramWrapper\Jobs\NewTelegramUpdate;
use kodeops\LaravelTelegramWrapper\Exceptions\LaravelTelegramWrapperException;

class Telegram
{
    // Documentation: https://core.telegram.org/bots/api

    protected $chat_id;
    protected $params;
    protected $markdownImage;
    protected $keyboard;
    protected $url;
    protected $token;

    public function __construct($token = null)
    {
        $this->token = $token ?? env('TELEGRAM_BOT_TOKEN');

        if (is_null($this->token)) {
            throw new LaravelTelegramWrapperException("Undefined bot token");
        }

        $this->keyboard = false;
        $this->markdownImage = false;
        $this->params = [];
    }

    private function baseUrl()
    {
        // https://core.telegram.org/bots/api#using-a-local-bot-api-server
        // If using custom telegram server change this setting
        return env('TELEGRAM_ENDPOINT') ?? 'https://api.telegram.org';
    }

    public function chat($chat_id)
    {
        $this->chat_id = $chat_id;
        return $this;
    }

    public function getUpdates()
    {
        $this->url = $this->baseUrl() . "/bot{$this->token}/getUpdates";
        return $this->process();
    }

    public function setWebhook($url)
    {
        $this->params = ['url' => $url];
        $this->url = $this->baseUrl() . "/bot{$this->token}/setWebhook?" . http_build_query($this->params);
        return $this->process();
    }

    public function deleteWebhook()
    {
        $this->params = ['drop_pending_updates' => true];
        $this->url = $this->baseUrl() . "/bot{$this->token}/deleteWebhook?" . http_build_query($this->params);
        return $this->process();
    }

    public function sendMessage($queue = false)
    {
        $this->params['chat_id'] = $this->chat_id;

        if ($this->markdownImage) {
            if (! isset($this->params['parse_mode'])) {
                throw new Exception("Undefined parse_mode");
            }
            if ($this->params['parse_mode'] != 'Markdown') {
                throw new Exception("parse_mode must be set to Markdown");
            }
            if (! isset($this->params['text'])) {
                throw new Exception("Missing markdown text");
            }
            $this->params['text'] = $this->markdownImage . $this->params['text'];
        }

        if (env('TELEGRAM_DEBUG')) {
            Log::channel('telegram')->info($this->params['text']);
            return;
        }

        $this->url = $this->baseUrl() . "/bot{$this->token}/sendMessage?" . http_build_query($this->params);
        if ($this->keyboard) {
            $this->url .= '&reply_markup=' . json_encode($this->keyboard, true);
        }

        if ($queue) {
            return ['url' => $this->url, 'params' => $this->params];
        }

        $this->process($this->url, $this->params);
    }

    public function process()
    {
        return self::request($this->url, $this->params);
    }

    public static function request(string $url, array $params, $throw = true)
    {
        try {
            $request = Http::get($url)->throw();
        } catch (Exception $e) {
            $debug = ['url' => $url, 'params' => $params];
            activity()
                ->withProperties($debug)
                ->log('telegram.exception');

            Bugsnag::registerCallback(function ($report) use ($debug) {
                $report->setMetaData($debug);
            });
            Bugsnag::notifyException($e);

            if ($throw) {
                throw $e;
            }

            return false;
        }

        return $request->body();
    }

    public function withKeyboard($keyboard = null)
    {
        if (! is_null($keyboard)) {
            $this->keyboard = $keyboard;
        }

        return $this;
    }

    public function withMarkdownImage($markdownImage = null)
    {
        if (! is_null($markdownImage)) {
            $this->markdownImage = "[​​​​​​​​​​​]({$markdownImage})";
        }

        return $this;
    }

    public function withMarkdown($markdown)
    {
        $this->params = [
            'text' => $markdown,
            'parse_mode' => 'Markdown',
        ];

        return $this;
    }

    public function queueMessage()
    {
        $this->sendMessage(true);
        NewTelegramUpdate::dispatch($this->url, $this->params);
    }
}
