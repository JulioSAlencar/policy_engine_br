<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Gera a URL apontando para o seu Front-end estático
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."?token=$token&email={$notifiable->getEmailForPasswordReset()}";
        });

        // 2. Personaliza todo o conteúdo visual e textual do E-mail
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            
            // Recria a URL para colocar no botão do e-mail
            $url = config('app.frontend_url')."?token=$token&email={$notifiable->getEmailForPasswordReset()}";

            return (new MailMessage)
                ->subject('Recuperação de Senha - GovCert') // Assunto do e-mail
                ->greeting('Olá, ' . $notifiable->name . '!') // Saudação inicial
                ->line('Você está recebendo este e-mail porque solicitou a redefinição de senha da sua conta em nosso sistema.')
                ->line('Se você não fez essa solicitação, pode ignorar esta mensagem em segurança.')
                ->action('Criar uma nova senha', $url) // Botão principal
                ->line('Este link de recuperação é válido por apenas 60 minutos.')
                ->salutation('Um abraço, Equipe GovCert'); // Despedida no final
        });
    }
}