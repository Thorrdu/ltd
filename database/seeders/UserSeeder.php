<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Jacques Noir',     'email' => 'admin@ltd.test',        'pin' => '1337'],
            ['name' => 'Franz Lanroel',    'email' => 'franz@lost.mc',         'pin' => '4291'],
            ['name' => 'Marcus Freeman',   'email' => 'marcus@lost.mc',        'pin' => '7813'],
            ['name' => 'Arthur Murphy',    'email' => 'arthur@lost.mc',        'pin' => '3547'],
            ['name' => 'Johnny B',         'email' => 'johnny@lost.mc',        'pin' => '6102'],
            ['name' => 'Erwan Fox',        'email' => 'erwan@lost.mc',         'pin' => '8439'],
            ['name' => 'Jack Cadillac',    'email' => 'jack@lost.mc',          'pin' => '5276'],
            ['name' => 'Aroune Diakite',   'email' => 'aroune@lost.mc',        'pin' => '9034'],
            ['name' => 'Mamadou Doumbe',   'email' => 'mamadou@lost.mc',       'pin' => '2658'],
            ['name' => 'Olaf Williams',    'email' => 'olaf@lost.mc',          'pin' => '7391'],
        ];

        foreach ($members as $m) {
            User::updateOrCreate(
                ['email' => $m['email']],
                [
                    'name'     => $m['name'],
                    'role'     => 'officer',
                    'password' => Hash::make('password'),
                    'sim_pin'  => Hash::make($m['pin']),
                ]
            );
        }
    }
}
