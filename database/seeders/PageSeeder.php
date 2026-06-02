<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'ALMuhalab International Co.',
                'meta_description' => 'Since 1977 — Transportation, General Trading, Construction, Import and Export, Commission Agency, and IT Services.',
                'is_published' => true,
            ]
        );

        if ($page->sections()->exists()) {
            return; // already seeded
        }

        // Hero
        $page->sections()->create([
            'type' => 'hero',
            'title' => 'Hero Banner',
            'sort_order' => 1,
            'content' => [
                'heading' => 'ALMuhalab International Co.',
                'tagline' => 'Since 1977',
                'subheading' => 'A leading parent company operating across Transportation, General Trading, Construction, Import & Export, Commission Agency, and IT Services.',
                'button_text' => 'Contact Us',
                'button_url' => '#contact',
            ],
        ]);

        // About
        $page->sections()->create([
            'type' => 'about',
            'title' => 'About Us',
            'sort_order' => 2,
            'content' => [
                'heading' => 'Meet the ALMuhalab International Co.',
                'text' => "Welcome to ALMuhalab International Company! Established in 1977, we have proudly operated continuously across a diverse range of sectors, including Transportation, General Trading, Construction, Import and Export, Commission Agency, and IT Services. As a leading parent company, we maintain strong partnerships with numerous government entities and prominent international corporations. Explore how we can serve your needs and contribute to your success!",
            ],
        ]);

        // Partners
        $page->sections()->create([
            'type' => 'partners',
            'title' => 'Our Partners',
            'sort_order' => 3,
            'content' => [
                'heading' => 'WE REPRESENT THE FOLLOWING',
                'partners' => [
                    [
                        'name' => 'TATNEFT LLC',
                        'description' => 'We are the Exclusive Middle East Agent for TATNEFT LLC. Tatneft is a vertically integrated oil and gas company, the fifth-largest oil company in Russia.',
                        'url' => 'https://www.tatneft.ru/en',
                    ],
                    [
                        'name' => 'China Communications Construction Company Ltd.',
                        'description' => 'We are the exclusive agent of CCCC, a predominantly state-owned multinational firm specializing in engineering and construction, focusing on highways, bridges, tunnels, railways, and marine ports.',
                        'url' => 'https://en.ccccltd.cn/',
                    ],
                    [
                        'name' => 'Russian Direct Investment Fund (RDIF)',
                        'description' => 'We are a registered Partner with RDIF. Created in 2011 to co-invest alongside top investors, RDIF has invested and committed over 2.1 trillion rubles.',
                        'url' => 'https://www.rdif.ru/Eng_Index/',
                    ],
                    [
                        'name' => 'KATAYSK Pump Plant JSC',
                        'description' => 'We are the agent of the KATAYSK Pump Plant JSC (KNZ), specializing in the development, production, and sale of pumps for oil and gas, water management, nuclear energy, and thermal energy.',
                        'url' => 'https://knz.ru/',
                    ],
                    [
                        'name' => 'Chromis LLC Factory (ChemRar)',
                        'description' => 'We are the exclusive agent of the Chromis LLC Factory. RDIF partnered with ChemRar Group to establish Chromis, dedicated to producing the antiviral drug Avifavir (Favipiravir).',
                        'url' => 'https://en.chemrar.ru/chromis/',
                    ],
                    [
                        'name' => 'China Railway Tunnel Group Co. LTD',
                        'description' => 'We are the exclusive dealer in Kuwait for CRTG, the leading engineering contractor for tunnel and underground projects in China and a key member of China Railway Group Ltd. (Fortune Global 500).',
                        'url' => 'https://www.crtg.cn/',
                    ],
                    [
                        'name' => 'Hyundai E&C',
                        'description' => 'We are a registered Sub-Partner with Hyundai Engineering and Construction Co., Ltd., a leading construction firm in South Korea founded in 1947.',
                        'url' => 'https://en.hdec.kr/',
                    ],
                    [
                        'name' => 'Scientific Research Center in Siberia',
                        'description' => 'We are Agent of the Scientific Research Center for "Whole Body Intensive Hyperthermia" in Siberia, Russia. This medical practice has received approval from the Kuwaiti Ministry of Health.',
                        'url' => 'https://youtube.com/watch?v=bVi-6TrsEH4',
                    ],
                    [
                        'name' => 'Sputnik-V (Gamaleya National Center)',
                        'description' => 'We are the exclusive distributor for Sputnik-V. The Gamaleya National Center of Epidemiology and Microbiology is a premier research institution globally, established in 1891.',
                        'url' => 'https://sputnikvaccine.com/',
                    ],
                    [
                        'name' => 'TSLUUT IMPEX LLC',
                        'description' => 'The exclusive agent in the Middle East for Mongolian beef and lamb meat (TSLUUT IMPEX LLC).',
                    ],
                ],
            ],
        ]);

        // Contact
        $page->sections()->create([
            'type' => 'contact',
            'title' => 'Contact Us',
            'sort_order' => 4,
            'content' => [
                'heading' => 'Contact Us',
                'intro' => 'We strive to stay in communication with our clients. Have a question about our business, or want to see if we match your specific needs? Send us a message, or give us a call.',
                'address' => '40 Khalid Ibn Al Waleed St, Kuwait City, State of Kuwait',
                'phone' => '+96599461878',
                'email' => 'info@almuhalab.net',
            ],
        ]);
    }
}
