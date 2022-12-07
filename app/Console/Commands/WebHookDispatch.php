<?php

namespace App\Console\Commands;

use App\Events\OrderEvent;
use App\Models\Model;
use App\Models\Order;
use Illuminate\Console\Command;

class WebHookDispatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:dispatch {event} {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch webhook';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $eventType */
        $eventType = $this->argument('event');
        $event = '\\App\\Events\\' . $eventType;

        if (is_subclass_of($event, OrderEvent::class)) {
            $order = Order::query()
                ->where('id', $this->argument('id'))
                ->orWhere('code', $this->argument('id'))
                ->first();

            if (!($order instanceof Order)) {
                $this->error('Order not found.');
                return 0;
            }

            $this->dispatch($event, $order);
            return 1;
        }

        $this->error('Not supported event type.');
        return 0;
    }

    private function dispatch(string $event, Model $model): void
    {
        $this->info('WebHook dispatching...');
        $event::dispatch($model);
        $this->info('WebHook dispatched.');
    }
}
