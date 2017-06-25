<?php

use App\TransactionType;
use App\TransactionTypeFactory;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransactionType::truncate();

        /** @var TransactionTypeFactory $factory */
        $factory = app(TransactionTypeFactory::class);
        $supportedTypes = $factory->getSupportedTypes();

        foreach ($supportedTypes as $supportedType) {
            $factory->make($supportedType)->save();
        }
    }
}
