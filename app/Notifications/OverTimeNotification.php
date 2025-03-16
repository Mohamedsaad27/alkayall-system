<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverTimeNotification extends Notification
{
    use Queueable;
    protected $overTime;
    protected $action;
    protected $message;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($overTime, $action, $message)
    {
        $this->overTime = $overTime;
        $this->action = $action;
        $this->message = $message;
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

 
    public function toArray($notifiable)
    {
        return [
            'overTime' => $this->overTime,
            'action' => $this->action,
            'message' => $this->message,
        ];
    }
}
