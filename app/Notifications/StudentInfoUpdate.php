<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentInfoUpdate extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $branch_id;
    protected $parent_id;
    protected $info_update_id;
    protected $info_update;

    public function __construct($data)
    {
        //
        $this->branch_id = $data['branch_id'];
        $this->parent_id = $data['parent_id'];
        $this->info_update_id = $data['info_update_id'];
        $this->info_update = $data['info_update'];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
            'branch_id' => $this->branch_id,
            'parent_id' => $this->parent_id,
            'info_update_id' => $this->info_update_id,
            'info_update' => $this->info_update
        ];
    }
}
