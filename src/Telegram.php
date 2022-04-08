<?php
namespace kodeops\LaravelTelegramWrapper;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Bugsnag;
use Exception;
use kodeops\LaravelTelegramWrapper\Jobs\NewTelegramUpdate;

class Telegram
{
    // Get chat id: https://api.telegram.org/bot<YourBOTToken>/getUpdates

    protected $chat_id;
    protected $bot_key;
    protected $params;
    protected $markdownImage;
    protected $keyboard;
    protected $url;

    public function __construct($chat_id = null, $bot_key = null)
    {
        $this->chat_id = $chat_id;
        $this->bot_key = $bot_key ?? env('TELEGRAM_BOT_KEY');
        $this->keyboard = false;
        $this->markdownImage = false;
        $this->params = [];
    }

    public function deleteWebhook()
    {
        $this->params = ['drop_pending_updates' => true];
        $this->url  = "https://api.telegram.org/bot{$this->bot_key}/deleteWebhook?" . http_build_query($this->params);
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
            Log::channel('telegram')->info($params['text']);
            return;
        }

        $this->url  = "https://api.telegram.org/bot{$this->bot_key}/sendMessage?" . http_build_query($this->params);
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

    public static function request(string $url, array $params, $throw = false)
    {
        try {
            Http::get($url)->throw();
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

        return true;
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
