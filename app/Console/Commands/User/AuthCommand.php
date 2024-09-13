<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\form;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class AuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:auth {--limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Authentication user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = text(
            label: 'Enter your email',
            validate: fn (string $value) => match (true) {
                substr_count($value, '@') !== 1 => 'ایمیل نامعتبر',
                default => null
            }
        );

        if ($user = User::whereEmail($email)->first()) {
            $password = password(
                label: 'رمز خود را وارد کنید',
                required: true,
            );

            if (Hash::check($password, $user->password)) {
                $this->call('user:list', [
                    'user' => $user->toArray(),
                    '--limit' => $this->option('limit'),
                ]);
            } else {
                error('شما به این اطلاعات دسترسی ندارید');
            }
        } else {
            $confirm = confirm('کاربری با این مشخصات وجود ندارد آیا میخواید ثبت نام کنید', true);

            if ($confirm) {
                $registerData = form()->text(
                    label: 'user name',
                    validate: ['name' => 'min:5'],
                    required: true,
                    name: 'name'
                )->text(
                    label: 'email',
                    validate: ['email' => 'email:unique'],
                    required: true,
                    name: 'email'
                )->password(
                    label: 'password',
                    validate: ['password' => 'min:8'],
                    required: true,
                    name: 'password'
                )->submit();

                $user = User::create($registerData);

                $this->call('user:list', [
                    'user' => $user->toArray(),
                    '--limit' => $this->option('limit'),
                ]);
            }
        }
    }
}
