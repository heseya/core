<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\WebHook;
use App\Models\WebHookEventLogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebHookEventLogEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebHookEventLogEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $webhooks = WebHook::all();

        return [
            'web_hook_id' => $webhooks->random(1)->first()->getKey(),
            'triggered_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'url' => $this->faker->url,
            'status_code' => $this->faker->randomElement([200, 400, 500]),
            'payload' => [
                'event' => EventType::getRandomKey()
            ]
        ];
    }
}
