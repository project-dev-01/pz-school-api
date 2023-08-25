<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherHomework extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $branch_id;
    protected $teacher_id;
    protected $homework_id;
    protected $homework;

    public function __construct($data)
    {
        //
        $this->branch_id = $data['branch_id'];
        $this->teacher_id = $data['teacher_id'];
        $this->homework_id = $data['homework_id'];
        $this->homework = $data['homework'];
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
            'teacher_id' => $this->teacher_id,
            'homework_id' => $this->homework_id,
            'homework' => $this->homework
        ];
    }
}
