<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Schema;
use App\Services\Contracts\OptionServiceContract;
use Illuminate\Support\Collection;

class OptionService implements OptionServiceContract
{
    public function sync(Schema $schema, array $options = []): void
    {
        $keep = Collection::empty();

        foreach ($options as $order => $input) {
            $input['order'] = $order;

            if (isset($input['id'])) {
                $option = Option::findOrFail($input['id']);
                $option->update($input);
            } else {
                $option = $schema->options()->create($input);
            }

            $option->items()->sync($input['items'] ?? []);

            $keep->add($option->getKey());
        }

        $schema->options()->whereNotIn('id', $keep)->delete();
    }
}
