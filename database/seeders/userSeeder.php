<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::firstOrCreate([
            'username'  => 'yasser_fouad',
            'name'  => 'Yasser Fouad',
            'super' => 1,
            'password'  => bcrypt('yasser_fouad_3@alkyall#2025'),
        ]);

        User::firstOrCreate([
            'username'  => 'khaled_alkyall',
            'name'  => 'Khaled Akyall',
            'super' => 1,
            'password'  => bcrypt('khaled_alkyall_1@alkyall#2025'),
        ]);

        User::firstOrCreate([
            'username'  => 'youssef',
            'name'  => 'Youssef Abdel Rahim',
            'super' => 1,
            'password'  => bcrypt('youssef_2@alkyall#2025'),
        ]);
    }
}
