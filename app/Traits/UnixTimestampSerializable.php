<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait UnixTimestampSerializable
{
    /**
     * Prepare a date for array / JSON serialization.
     * Always return an integer Unix Timestamp to ensure consistency across platforms.
     *
     * @param  \DateTimeInterface  $date
     * @return int
     */
    protected function serializeDate(\DateTimeInterface $date): int
    {
        return $date->getTimestamp();
    }

    /**
     * Convert a DateTime to a storable string.
     * Supports Unix Timestamps (int/string) coming from API requests.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function fromDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        // If the value is a numeric string or integer, assume it's a Unix Timestamp
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value)->format($this->getDateFormat());
        }

        return parent::fromDateTime($value);
    }
}
