<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list {user?} {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (empty($this->argument('user'))) {
            $this->call('user:auth', [
                '--limit' => $this->option('limit'),
            ]);
        } else {
            $id = search(
                label: 'Search user',
                options: fn (string $value) => strlen($value) > 0
                        ? User::whereLike('name', "%{$value}%")->pluck('name', 'id')->all()
                        : User::pluck('name', 'id')->all()
            );

            $user = User::find($id);

            $role = select(
                label: 'چه عملی میخواهید روی این کاربر انجام دهید',
                options: ['Update', 'Delete'],
                required: true
            );

            if (strtolower($role) === 'delete') {
                $res = confirm("Are you sure for delete $user->name ? ", default: false);

                if ($res) {
                    $user->delete();
                    $this->info('User deleted successfully!');
                }
            } else {
                do {
                    $updateData = form()->text(
                        label: 'user name',
                        validate: ['name' => 'min:5'],
                        default: $user->name,
                        required: true,
                        name: 'name'
                    )->text(
                        label: 'email',
                        validate: ['email' => 'email:unique'],
                        required: true,
                        default: $user->email,
                        name: 'email'
                    )->password(
                        label: 'password',
                        validate: ['password' => 'min:8'],
                        /* required: true, */
                        name: 'password'
                    )->submit();

                    $user->update($updateData);

                    $showAgain = confirm('عملیات مورد نظر با موفقیت انجام شد آیا دوباره تمایل به آپدیت دارید؟', default: false);
                } while ($showAgain);
            }
        }
    }
}
