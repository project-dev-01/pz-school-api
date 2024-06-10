<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatus extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $branch_id;
    protected $application_id;
    protected $guardian_email;  
    protected $student_name;
    protected $phase_1_status;  
    protected $phase_2_status;  


    public function __construct($data)
    {
        //
        $this->branch_id = $data['branch_id'];
        $this->application_id = $data['application_id'];
        $this->guardian_email = $data['guardian_email'];
        $this->student_name = $data['student_name'];
        $this->phase_1_status = $data['phase_1_status'];
        $this->phase_2_status = $data['phase_2_status'];
        
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
            'application_id' => $this->application_id,
            'guardian_email' => $this->guardian_email,
            'student_name' => $this->student_name,
            'phase_1_status' => $this->phase_1_status,
            'phase_2_status' => $this->phase_2_status
        ];
    }
}
