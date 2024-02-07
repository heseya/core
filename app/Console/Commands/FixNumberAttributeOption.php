<?php

namespace App\Console\Commands;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Console\Command;

class FixNumberAttributeOption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attributes:number-options';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix number attribute options value_number';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $attributes = Attribute::query()->where('type', '=', AttributeType::NUMBER->value);
        $count = $attributes->count();
        $bar = $this->output->createProgressBar($count);

        $bar->start();

        foreach ($attributes->cursor() as $attribute) {
            foreach ($attribute->options()->whereNull('value_number')->cursor() as $option) {
                if (preg_match('/^\d{1,6}(\.\d{1,4}|)$/', $option->name)) {
                    $option->update([
                        'value_number' => (float) $option->name,
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
