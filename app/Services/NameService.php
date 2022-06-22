<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Order;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Str;

class NameService implements NameServiceContract
{
    private SettingsServiceContract $settingsService;

    public function __construct(SettingsServiceContract $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function generate(): string
    {
        $pattern = $this->settingsService->getSetting('order_number_template')->value;
        $start = (int) $this->settingsService->getSetting('order_number_start')->value;

        return $this->render($pattern, [
            'r' => Str::of(Str::random())->upper(),

            'year' => date('Y'),
            'month' => date('n'),
            'day' => date('j'),

            'no' => Order::count() + $start + 1,
            'no_year' => Order::whereYear('created_at', date('Y'))->count() + 1,
            'no_month' => Order::whereYear('created_at', date('Y'))
                ->whereMonth('created_at', date('n'))->count() + 1,
            'no_day' => Order::where('created_at', date('Y-m-d'))->count() + 1,
        ]);
    }

    private function render(string $pattern, array $params): string
    {
        $splitted = explode('{', $pattern);
        array_shift($splitted);

        $arr = [];
        $tags = [];
        $splittedCount = count($splitted);

        for ($i = 0; $i < $splittedCount; $i++) {
            $arr[$i] = '';
            $pairs = 0;

            while ($pairs < 1) {
                $piece = array_shift($splitted);
                $closures = explode('}', $piece);
                $pairs += count($closures) - 1;

                array_pop($closures);

                if ($pairs >= 1) {
                    $arr[$i] .= implode('}', $closures);
                } else {
                    $arr[$i] .= $piece . '{';
                    $pairs--;
                }
            }

            $tags[trim($arr[$i])] = $arr[$i];
        }

        $number = $pattern;

        foreach ($tags as $key => $tag) {
            $temp = explode(':', $key);

            if (count($temp) > 1 && isset($params[$temp[0]])) {
                $param = $params[$temp[0]];
                if (is_numeric($temp[1])) {
                    $param = substr($param, - $temp[1]);
                } else {
                    throw new ClientException(Exceptions::CLIENT_ORDER_CODE_LENGTH_MUST_BE_NUMERIC);
                }
            } else {
                $param = $params[$key] ?? '{' . $tag . '}';
            }

            $number = preg_replace('/{' . preg_quote($tag) . '}/', $param, $number);
        }

        return $number;
    }
}
