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
            throw new LaravelTelegramWrapperException("Missing TELEGRAM_BOT_TOKEN");
        }

        if (env('TELEGRAM_CHAT_ID')) {
            $this->chat_id = env('TELEGRAM_CHAT_ID');
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

    // https://core.telegram.org/bots/api#getme
    public function getMe()
    {
        $this->url = $this->baseUrl() . "/bot{$this->token}/getMe";
        return $this->process();
    }

    // https://core.telegram.org/bots/api#getWebhookInfo
    public function getWebhookInfo()
    {
        $this->url = $this->baseUrl() . "/bot{$this->token}/getWebhookInfo";
        return $this->process();
    }

    public function getUpdates()
    {
        $this->url = $this->baseUrl() . "/bot{$this->token}/getUpdates";
        return $this->process();
    }

    public function getMyCommands()
    {
        $this->url = $this->baseUrl() . "/bot{$this->token}/getMyCommands";
        return $this->process();
    }

    public function setMyCommands($commands)
    {
        $this->params['commands'] = json_encode($commands);
        $this->url = $this->baseUrl() . "/bot{$this->token}/setMyCommands?" . http_build_query($this->params);
        return $this->process();
    }

    public function getChatMemberCount()
    {
        $this->params['chat_id'] = $this->chat_id;
        $this->url = $this->baseUrl() . "/bot{$this->token}/getChatMemberCount?" . http_build_query($this->params);
        return $this->process();
    }

    public function setWebhook($url)
    {
        $this->params['url'] = $url;
        $this->url = $this->baseUrl() . "/bot{$this->token}/setWebhook?" . http_build_query($this->params);
        return $this->process();
    }

    public function deleteWebhook()
    {
        $this->params['drop_pending_updates'] = true;
        $this->url = $this->baseUrl() . "/bot{$this->token}/deleteWebhook?" . http_build_query($this->params);
        return $this->process();
    }

    public function answerCallbackQuery($callback_query_id, $text, $show_alert)
    {
        $this->params['callback_query_id'] = $callback_query_id;
        $this->params['text'] = $text;
        $this->params['show_alert'] = $show_alert;
        $this->url = $this->baseUrl() . "/bot{$this->token}/answerCallbackQuery?" . http_build_query($this->params);
        return $this->process();
    }

    private function checkChatIdIsPresent()
    {
        if (! isset($this->params['chat_id'])) {
            throw new LaravelTelegramWrapperException("Undefined chat_id");
        }

        if ($this->params['chat_id'] == '') {
            throw new LaravelTelegramWrapperException("Undefined chat_id");
        }
    }

    private function sendWithKeyboard($method, $queue)
    {
        $this->url = $this->baseUrl();
        $this->url .= "/bot{$this->token}/{$method}";
        $this->url .= "?" . http_build_query($this->params);

        if ($this->keyboard) {
            $this->url .= '&reply_markup=' . json_encode($this->keyboard, true);
        }

        $this->checkChatIdIsPresent();

        if ($queue) {
            return ['url' => $this->url, 'params' => $this->params];
        }

        $this->process($this->url, $this->params);
    }

    public function sendPhoto($photo,$queue = false)
    {
        $this->params['chat_id'] = $this->chat_id;
        $this->params['photo'] = $photo;

        $this->sendWithKeyboard('sendPhoto', $queue);
    }

    public function sendMessage($queue = false)
    {
        $this->params['chat_id'] = $this->chat_id;

        $this->checkChatIdIsPresent();

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

        $this->sendWithKeyboard('sendMessage', $queue);
    }

    // https://core.telegram.org/bots/api#editmessagetext
    public function editMessageText(int $message_id)
    {
        $this->params['chat_id'] = $this->chat_id;
        $this->params['message_id'] = $message_id;

        $this->url = $this->baseUrl() . "/bot{$this->token}/editMessageText?" . http_build_query($this->params);
        if ($this->keyboard) {
            $this->url .= '&reply_markup=' . json_encode($this->keyboard, true);
        }
        return $this->process($this->url, $this->params);
    }

    public function process()
    {
        return self::request($this->url, $this->params);
    }

    public static function request(string $url, array $params, $throw = true)
    {
        $request_data = ['url' => $url, 'params' => $params];

        if (env('TELEGRAM_REQUEST_LOG')) {
            activity()
                ->withProperties($request_data)
                ->log('telegram.debug');
        }

        try {
            $request = Http::get($url)->throw();
        } catch (Exception $e) {
            activity()
                ->withProperties($request_data)
                ->log('telegram.exception');

            Bugsnag::registerCallback(function ($report) use ($request_data) {
                $report->setMetaData($request_data);
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
        if (is_null($markdownImage)) {
            return $this;
        }
        $this->markdownImage = "[​​​​​​​​​​​]({$markdownImage})";

        return $this;
    }

    public function withMarkdown($markdown)
    {
        $this->params['text'] = $markdown;
        $this->params['parse_mode'] ='Markdown';

        return $this;
    }

    public function withMarkdownCaption($markdown)
    {
        // https://core.telegram.org/bots/api#sendphoto
        if (strlen($markdown) > 1024) {
            throw new LaravelTelegramWrapperException("caption exceeds allowed characters");
        }
        $this->params['caption'] = $markdown;
        $this->params['parse_mode'] ='Markdown';

        return $this;
    }

    public function disableWebPagePreview()
    {
        $this->params['disable_web_page_preview'] = true;

        return $this;
    }

    public function replyToMessage(int $message_id = null)
    {
        if (is_null($message_id)) {
            return $this;
        }

        $this->params['reply_to_message_id'] = $message_id;

        return $this;
    }

    public function queueMessage()
    {
        $this->sendMessage(true);
        NewTelegramUpdate::dispatch($this->url, $this->params);
    }
}
