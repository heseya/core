<?php

namespace App\Console\Commands;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Console\Command;

class FixDateAttributeOption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attributes:date-options';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix date attribute options value_date';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $attributes = Attribute::query()->where('type', '=', AttributeType::DATE->value);
        $count = $attributes->count();
        $bar = $this->output->createProgressBar($count);

        $bar->start();

        foreach ($attributes->cursor() as $attribute) {
            foreach ($attribute->options()->whereNull('value_date')->cursor() as $option) {
                $date = strtotime($option->name);
                if ($date) {
                    $option->update([
                        'value_date' => date('Y-m-d', $date),
                    ]);
                } else {
                    $option->delete();
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
