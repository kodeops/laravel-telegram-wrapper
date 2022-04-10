```
 _     _  _____  ______  _______  _____   _____  _______
 |____/  |     | |     \ |______ |     | |_____] |______
 |    \_ |_____| |_____/ |______ |_____| |       ______|
 
```
 
# Laravel Telegram API Wrapper

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

These would be used by default, additionally you can pass the credentials when initialising the class.

```
TELEGRAM_DEBUG=false
TELEGRAM_TOKEN=<token>
TELEGRAM_CHAT_ID=<chat_id>
```

## Usage

```
use kodeops\LaravelTelegramWrapper\Telegram;

return (new Telegram($bot_token))
    ->chat($chat_id)
    ->withMarkdownImage($image)
    ->withKeyboard($keyboard)
    ->withMarkdown($markdown)
    ->sendMessage();
```
