<?php

namespace App\Traits;

use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\LaravelData\Optional;

trait DtoHasPhone
{
    private function initializePhone(): void
    {
        if (!$this->phone) {
            // Setting here as null not working
            $this->phone_country = $this->phone_number = '';

            return;
        }

        if (!($this->phone instanceof Optional)) {
            $phone = new PhoneNumber($this->phone);
            $this->phone_country = $phone->getCountry();
            $this->phone_number = $phone->formatNational();
        } else {
            $this->phone_country = $this->phone_number = new Optional();
        }
    }
}
