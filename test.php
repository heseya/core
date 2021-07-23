<?php

use vendor\Illuminate\Support\Collection;

// abstract class Dto {
//     private array $data;
//     protected array $construct;

//     public function __construct(...$params) {
//         $construct = Collection::make($this->construct);
//         $params = Collection::make($params);

//         $construct->each(function ($key, $rules) use ($this, $params) {
//             if ($params->has($key)) {
//                 $value = $params->get($key);

//                 Collection::make()
//             }
//         });
//     }
// }

var_dump(Collection::make(1));
