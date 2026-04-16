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
            ['name' => 'Jacques Noir',     'email' => 'admin@ltd.test',   'role' => 'president',  'pin' => '1337'],
            ['name' => 'Franz Lanroel',    'email' => 'franz@lost.mc',    'role' => 'treasurer',  'pin' => '4291'],
            ['name' => 'Marcus Freeman',   'email' => 'marcus@lost.mc',   'role' => 'officer',    'pin' => '7813'],
            ['name' => 'Arthur Murphy',    'email' => 'arthur@lost.mc',   'role' => 'officer',    'pin' => '3547'],
            ['name' => 'Johnny B',         'email' => 'johnny@lost.mc',   'role' => 'member',     'pin' => '6102'],
            ['name' => 'Erwan Fox',        'email' => 'erwan@lost.mc',    'role' => 'member',     'pin' => '8439'],
            ['name' => 'Jack Cadillac',    'email' => 'jack@lost.mc',     'role' => 'member',     'pin' => '5276'],
            ['name' => 'Aroune Diakite',   'email' => 'aroune@lost.mc',   'role' => 'prospect',   'pin' => '9034'],
            ['name' => 'Mamadou Doumbe',   'email' => 'mamadou@lost.mc',  'role' => 'prospect',   'pin' => '2658'],
            ['name' => 'Olaf Williams',    'email' => 'olaf@lost.mc',     'role' => 'member',     'pin' => '7391'],
        ];

        foreach ($members as $m) {
            User::updateOrCreate(
                ['email' => $m['email']],
                [
                    'name'     => $m['name'],
                    'role'     => $m['role'],
                    'password' => Hash::make($m['pin']),
                    'sim_pin'  => Hash::make($m['pin']),
                ]
            );
        }
    }
}
