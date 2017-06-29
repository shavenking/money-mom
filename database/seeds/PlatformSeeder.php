<?php

use App\Platform;
use App\PlatformFactory;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Platform::truncate();

        /** @var PlatformFactory $factory */
        $factory = app(PlatformFactory::class);
        $supportedPlatforms = $factory->getSupportedPlatforms();

        foreach ($supportedPlatforms as $supportedPlatform) {
            $factory->make($supportedPlatform)->save();
        }
    }
}
