<?php
namespace kodeops\LaravelTelegramWrapper\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use kodeops\LaravelTelegramWrapper\Telegram;

class NewTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $params;

    public function __construct($url, $params)
    {
        $this->url = $url;
        $this->params = $params;
    }

    public function handle()
    {
        Telegram::request($this->url, $this->params);
    }
}
