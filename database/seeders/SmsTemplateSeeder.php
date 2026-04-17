<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'        => 'Nowe zapytanie – potwierdzenie',
                'description' => 'Wysyłane natychmiast po wpłynięciu nowego zapytania od klienta.',
                'content'     => 'Dzien dobry! Dziekujemy za kontakt z {{company_name}}. Odezwiemy sie w ciagu 24h. Zapraszamy do wspolpracy!',
                'is_active'   => true,
            ],
            [
                'name'        => 'Kontakt nawiązany',
                'description' => 'Wysyłane po pierwszym kontakcie z klientem – potwierdzenie mailowe.',
                'content'     => 'Czesc {{client_name}}! Wlasnie wyslalismy Ci szczegolowa oferte na maila. Sprawdz skrzynke. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Oferta wysłana',
                'description' => 'Wysyłane po przesłaniu wyceny/propozycji do klienta.',
                'content'     => '{{client_name}}, Twoja wycena od {{company_name}} jest gotowa! Sprawdz maila i daj nam znac, jesli masz pytania.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Konferencja / Umowa podpisana – Start',
                'description' => 'Wysyłane po zatwierdzeniu oferty i podpisaniu umowy.',
                'content'     => '{{client_name}}, super wiadomosc – zaczynamy projekt {{project_name}}! Szczegoly kickoff spotkania w mailu. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Faktura wysłana',
                'description' => 'Wysyłane po wystawieniu faktury do klienta.',
                'content'     => '{{client_name}}, Twoja faktura od {{company_name}} czeka w skrzynce mailowej. Termin platnosci: 7 dni. Dziekujemy!',
                'is_active'   => true,
            ],
            [
                'name'        => 'Płatność potwierdzona',
                'description' => 'Wysyłane po potwierdzeniu otrzymania płatności.',
                'content'     => '{{client_name}}, platnosc otrzymana – dziekujemy! Kontynuujemy prace nad projektem. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Etap projektu ukończony',
                'description' => 'Wysyłane po ukończeniu etapu projektu.',
                'content'     => '{{client_name}}, etap projektu {{project_name}} jest gotowy! Szczegoly i podglad w mailu. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Projekt ukończony – LIVE',
                'description' => 'Wysyłane po oddaniu gotowego projektu klientowi.',
                'content'     => '{{client_name}}, projekt {{project_name}} jest LIVE! Gratulacje! Pelne szczegoly przekazania w mailu. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Faktura przeterminowana – przypomnienie',
                'description' => 'Wysyłane gdy faktura jest przeterminowana – delikatne przypomnienie.',
                'content'     => '{{client_name}}, mamy nieoplacona fakture na Twoim koncie. Prosimy o kontakt: {{company_name}}. Dziekujemy.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Prośba o opinię Google',
                'description' => 'Wysyłane po zakończeniu projektu – prośba o wystawienie opinii.',
                'content'     => '{{client_name}}, bedziesmy wdzieczni za opinie Google o naszej wspolpracy! Zajmie 2 minuty. – {{company_name}}',
                'is_active'   => true,
            ],
            [
                'name'        => 'Service CTA - potwierdzenie kontaktu',
                'description' => 'wysylamy sms do klienta ktory skorzysta z cta na widoku uslugi',
                'content'     => 'Hi Konrad will get in touch soon. thank you for choice Websiete Expert ',
                'is_active'   => true,
            ],
        ];

        foreach ($templates as $data) {
            SmsTemplate::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
