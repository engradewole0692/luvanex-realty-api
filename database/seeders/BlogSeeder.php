<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('is_admin', true)->first()
            ?? User::factory()->admin()->create([
                'name' => 'Luvanex Editorial',
                'email' => 'editorial@luvanexrealty.com',
            ]);

        $categories = collect([
            'Markets',
            'Playbook',
            'FX',
            'Strategy',
            'South Africa',
        ])->mapWithKeys(function (string $name) {
            $slug = str($name)->slug()->toString();

            $category = BlogCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => null],
            );

            return [$name => $category];
        });

        $posts = [
            [
                'slug' => 'why-atlanta',
                'title' => "Why Atlanta is a Global Investor's Best-Kept Secret",
                'category' => 'Markets',
                'excerpt' => 'The Atlanta metro is quietly outperforming every major U.S. city for international investor ROI. Here\'s why.',
                'published_at' => '2026-03-01 12:00:00',
                'content' => '<p>Atlanta enters 2026 with a rare combination of demographic tailwinds, corporate expansion and rental demand — the exact conditions that create long-cycle appreciation.</p><p>In this essay we walk through the population growth trajectory, the Fortune 500 employer base, and the specific submarkets where our team is actively acquiring on behalf of international clients.</p><p>The takeaway for any investor considering their first or fifth U.S. asset: Atlanta is not a discovery — it is a discipline. The winners are those who buy the right micro-market at the right basis.</p>',
            ],
            [
                'slug' => 'llc-101',
                'title' => 'LLC 101: Structuring Your First U.S. Property Purchase',
                'category' => 'Playbook',
                'excerpt' => 'A step-by-step guide to setting up a Delaware or Georgia LLC as a foreign investor.',
                'published_at' => '2026-02-15 12:00:00',
                'content' => '<p>The first U.S. property is rarely the hardest part of the process. The legal and banking architecture around it is.</p><p>This playbook walks through EIN issuance, choice of state, operating agreements, and how to open a business bank account remotely — with the sequence we use for our own clients.</p>',
            ],
            [
                'slug' => 'naira-to-dollar',
                'title' => 'From Naira to Dollars: A Compliant Path to Offshore Wealth',
                'category' => 'FX',
                'excerpt' => 'How Nigerian investors are legally moving capital into U.S. assets without triggering compliance headaches.',
                'published_at' => '2026-02-01 12:00:00',
                'content' => '<p>Moving capital across borders requires discipline, documentation, and the right banking partners.</p><p>This essay outlines compliant pathways Nigerian investors use when funding U.S. real estate acquisitions through Luvanex advisory workflows.</p>',
            ],
            [
                'slug' => 'charlotte-boom',
                'title' => 'The Charlotte Boom: Banking Money Meets Real Estate',
                'category' => 'Markets',
                'excerpt' => "Why Charlotte's population surge is driving a decade-long housing story.",
                'published_at' => '2026-01-15 12:00:00',
                'content' => '<p>Charlotte continues to attract banking, fintech, and corporate relocations that support sustained housing demand.</p><p>We outline the submarkets and product types our advisors monitor for long-horizon investors.</p>',
            ],
            [
                'slug' => 'str-vs-mtr',
                'title' => 'STR vs. MTR: Which Rental Strategy Actually Wins?',
                'category' => 'Strategy',
                'excerpt' => 'Short-term rentals promise higher yields, but mid-term is quietly stealing the show. We break down the numbers.',
                'published_at' => '2026-01-01 12:00:00',
                'content' => '<p>Gross yield is only one lens. Operating friction, seasonality, and regulation often decide which strategy compounds better over a hold period.</p><p>This essay compares short-term and mid-term rental economics for cross-border owners.</p>',
            ],
            [
                'slug' => 'sarb-explained',
                'title' => 'SARB Allowances Explained for South African Investors',
                'category' => 'South Africa',
                'excerpt' => 'Your R1m + R10m offshore allowance, decoded for real estate purchases.',
                'published_at' => '2025-12-15 12:00:00',
                'content' => '<p>South African investors have structured offshore allowances that can support U.S. property acquisition when planned correctly.</p><p>We decode the practical sequence for investors coordinating banking, tax counsel, and acquisition timelines.</p>',
            ],
        ];

        foreach ($posts as $data) {
            $category = $categories[$data['category']];

            BlogPost::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'author_id' => $author->id,
                    'category_id' => $category->id,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'content' => $data['content'],
                    'status' => BlogPost::STATUS_PUBLISHED,
                    'published_at' => $data['published_at'],
                    'robots_index' => true,
                    'robots_follow' => true,
                ],
            );
        }
    }
}
