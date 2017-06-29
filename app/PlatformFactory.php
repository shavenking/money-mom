<?php

namespace App;


class PlatformFactory
{
    protected $supportedPlatforms = [
        'TELEGRAM' => 'TELEGRAM'
    ];

    protected $model;

    public function __construct(Platform $model)
    {
        $this->model = $model;
    }

    public function getTelegram()
    {
        return $this
            ->model
            ->newQuery()
            ->whereName($this->supportedPlatforms['TELEGRAM'])
            ->firstOrFail();
    }

    public function getSupportedPlatforms(): array
    {
        return $this->supportedPlatforms;
    }

    public function make($platform): Platform
    {
        if (!isset($this->supportedPlatforms[$platform])) {
            throw new \Exception;
        }

        return $this->model->newInstance([
            'name' => $this->supportedPlatforms[$platform]
        ]);
    }
}
