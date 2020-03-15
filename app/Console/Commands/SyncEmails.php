<?php

namespace App\Console\Commands;

use App\Chat;
use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;

class SyncEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync a email account.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = Client::account('default');
        $client->connect();

        $emails = $client->getFolder('INBOX')->messages()->all()->paginate(100);

        foreach ($emails as $email) {

            $chat = Chat::firstOrCreate([
                'system' => Chat::SYSTEM_EMAIL,
                'external_id' => $email->from[0]->mail,
            ]);

            $message = $chat->messages()->firstOrCreate([
                'received' => true,
                'external_id' => $email->message_id,
                'content' => $email->bodies['html']->content ?? '',
                'created_at' => $email->date,
            ]);

            $chat->save();
        }

        $emails = $client->getFolder('Sent')->messages()->all()->paginate(100);

        foreach ($emails as $email) {

            $chat = Chat::firstOrCreate([
                'system' => Chat::SYSTEM_EMAIL,
                'external_id' => $email->to[0]->mail,
            ]);

            $message = $chat->messages()->firstOrCreate([
                'external_id' => $email->message_id,
                'content' => $email->bodies['html']->content ?? '',
                'created_at' => $email->date,
            ]);

            $chat->save();
        }
    }
}
