<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $branch_id;

    public function __construct($branch_id)
    {
        $this->branch_id = $branch_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
        ->subject('New Bulletin Board Notification')
        ->line('Hello!')
        ->line('A new notification has been posted on the bulletin board.')
        //->action('View Bulletin Board', url('/'))
        ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'branch_id' => $this->branch_id
        ];
    }
}
