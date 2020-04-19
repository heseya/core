<?php

namespace App\Console\Commands;

use App\Chat;
use App\Message as Email;
use Webklex\IMAP\Message;
use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use App\Mail\Message as MailMessage;
use Illuminate\Support\Facades\Mail;

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
     * Pobieranie maili przez imap i zapisywanie ich.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = Client::account('default');
        $client->connect();

        $emails = $client->getFolder('INBOX')->messages()->all()->paginate(100);

        foreach ($emails as $email) {
            $this->saveEmail($email);
        }

        $this->info('Incoming emails synced.');

        $emails = $client->getFolder('Sent')->messages()->all()->paginate(100);

        foreach ($emails as $email) {
            $this->saveEmail($email);
        }

        $this->info('Outgoing emails synced.');
    }

    /*
     * Zapisywanie poszczególnych wiadomości
     *
     * @param $email
     */
    protected function saveEmail(Message $email): void
    {
        // Wyszukiwanie lub tworzenie chatu
        $chat = Chat::firstOrCreate([
            'system' => Chat::SYSTEM_EMAIL,
            'external_id' => $email->from[0]->mail,
        ]);

        // Zapisywanie wszystkich załączników
        foreach ($email->getAttachments() as $attachment) {

            $attachment->save('public/storage');

            if (in_array($attachment->content_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                $html = '<img class="chat-image" src="' . asset('storage/' . $attachment->name) . '"/>';
            } else {
                $html = '<a class="chat-attachment" href="' . asset('storage/' . $attachment->name) . '">' . $attachment->name . '</a>';
            }

            $message = $chat->messages()->firstOrCreate([
                'received' => true,
                'external_id' => $email->message_id,
                'content' => $html,
                'created_at' => $email->date,
            ]);
        }

        // Sprawdzenie czy wiadomość nie jest pusta
        $content = $email->bodies['html']->content ?? $email->bodies['text']->content ?? null;

        if (trim(strip_tags($content))) {
            // zapisywanie wiadomości
            $message = $chat->messages()->firstOrCreate([
                'received' => true,
                'external_id' => $email->message_id,
                'content' => trim($content),
                'created_at' => $email->date,
            ]);
        }

        $chat->save();
    }
}
