```
 _     _  _____  ______  _______  _____   _____  _______
 |____/  |     | |     \ |______ |     | |_____] |______
 |    \_ |_____| |_____/ |______ |_____| |       ______|
 
```
 
# Laravel Telegram API Wrapper

This package is a wrapper for the [Telegram API](https://core.telegram.org/bots/api).

## Install composer dependency

`$ composer require kodeops/laravel-telegram-wrapper`

## Setup logging

Add a new `channel` in `logging.php` config file:

```
'telegram' => [
    'driver' => 'single',
    'path' => storage_path('logs/telegram.log'),
    'level' => 'info',
],
```

## Add environment settings

These would be used by default, additionally you can pass the credentials when instantiating the object.

```
TELEGRAM_DEBUG=false
TELEGRAM_BOT_TOKEN=<token>
TELEGRAM_CHAT_ID=<chat_id>
```

## Usage

### Send a new message

```
use kodeops\LaravelTelegramWrapper\Telegram;

return (new Telegram())
    ->withMarkdown($markdown)
    ->sendMessage();
```

You can also specify the credentials (`bot_token` and `chat_id`) when instantiating the object.

```
use kodeops\LaravelTelegramWrapper\Telegram;

return (new Telegram($bot_token))
    ->chat($chat_id)
    ->withMarkdownImage($image)
    ->withKeyboard($keyboard)
    ->withMarkdown($markdown)
    ->sendMessage();
```

This produces a message with the image on top and uses the image for notification highlight.

```
use kodeops\LaravelTelegramWrapper\Telegram;

$keyboard = [
    'inline_keyboard' => [
        [
            [
                'text' => 'Text Button',
                'url' => 'https://tannhauser-gate.xyz',
            ],
        ]
    ]
];

$markdown = 'A simple *markdown* text';
$image = 'https://iibusiness.s3.eu-central-1.amazonaws.com/tannhauser/favicon.jpg';

return (new Telegram())
    ->withKeyboard($keyboard)
    ->withMarkdownCaption($markdown)
    ->sendPhoto($image);
```

Options for sending messages:

- `disableWebPagePreview()`: Disables link previews for links in this message

- `withKeyboard($keyboard)`: Sets the keyboard for a message

- `withMarkdown($markdown)`: Sets the markdown text

- `withMarkdownImage($image)`: Sets an image to the message using markdown

- `withMarkdownCaption($markdown)`: Sets a markdown text as a caption (useful in combination with `sendPhoto($image)`)


### Utils

- `setMyCommands($commands)`: set bot commands

- `setWebhook($url)`: set webhooks url [More info on webhooks](https://core.telegram.org/bots/api#getting-updates)

- `getWebhookInfo()`: get webhooks info

### Additional features

In general you can use `setParam($key, $value)` method to add parameters to the request.