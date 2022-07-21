<?php

namespace Heseya\Demo\Console\Commands;

use App\Events\OrderEvent;
use App\Events\WebHookEvent;
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
     *
     * @return int
     */
    public function handle(): int
    {
        $eventType = $this->argument('event');
        $event = '\\App\\Events\\' . $eventType;

        if (!is_subclass_of($event, WebHookEvent::class)) {
            $this->error('Invalid event type');
            return 0;
        }

        if (is_subclass_of($event, OrderEvent::class)) {
            $order = Order::query()
                ->where('id', $this->argument('id'))
                ->orWhere('code', $this->argument('id'))
                ->first();

            if (!($order instanceof Order)) {
                $this->error('Order not found');
                return 0;
            }

            $this->info('WebHook dispatching...');
            $event::dispatch($order);
            $this->info('WebHook dispatched');
            return 1;
        }

        $this->error('Not supported event type');
        return 0;
    }
}