<?php

namespace Tests\Feature;

use App\Models\GodDomainOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicGodsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_displays_nine_portals_with_gods_and_two_empty_locked_cells(): void
    {
        $response = $this->get(route('web.home'));

        $response->assertOk()
            ->assertSee(route('valiant-shade.gods.index'), false)
            ->assertSee('Tanrılar')
            ->assertSee('grid-template-columns: repeat(3, minmax(0, 1fr));', false);

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertSame(9, preg_match_all('/class="vs-quad__cell(?:\s|")/', $content));
        $this->assertSame(2, preg_match_all('/<div class="vs-quad__cell vs-quad__cell--locked" aria-hidden="true"><\/div>/', $content));
    }

    public function test_public_gods_index_and_detail_pages_render_catalog_content(): void
    {
        config()->set('gods.groups', [
            'major' => [
                'label' => 'Büyük Tanrılar',
                'gods' => [
                    [
                        'slug' => 'siwion',
                        'name' => 'Siwion',
                        'epithet' => 'Örnek unvan',
                        'summary' => 'Örnek kısa tanıtım.',
                        'sections' => [
                            [
                                'title' => 'İnanç',
                                'paragraphs' => ['Örnek bölüm bilgisi.'],
                            ],
                        ],
                    ],
                ],
            ],
            'minor' => [
                'label' => 'Küçük Tanrılar',
                'gods' => [
                    [
                        'slug' => 'minor-example',
                        'name' => 'Küçük Örnek',
                        'summary' => 'Küçük tanrı örneği.',
                        'sections' => [],
                    ],
                ],
            ],
        ]);

        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()
            ->assertSee(route('valiant-shade.gods.show', ['slug' => 'siwion']), false)
            ->assertSee(route('valiant-shade.gods.show', ['slug' => 'minor-example']), false);

        $this->get(route('valiant-shade.gods.show', ['slug' => 'siwion']))
            ->assertOk()
            ->assertSeeInOrder(['Büyük Tanrılar', 'Siwion', 'Küçük Tanrılar', 'Küçük Örnek'])
            ->assertSee('Örnek kısa tanıtım.')
            ->assertSee('Örnek bölüm bilgisi.');
    }

    public function test_unknown_god_slug_returns_not_found(): void
    {
        $this->get(route('valiant-shade.gods.show', ['slug' => 'unknown-god']))
            ->assertNotFound();
    }

    public function test_empty_gods_index_shows_source_attribution(): void
    {
        config()->set('gods.groups', [
            'major' => ['label' => 'Büyük Tanrılar', 'gods' => []],
            'minor' => ['label' => 'Küçük Tanrılar', 'gods' => []],
        ]);

        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()
            ->assertSee('Seçilen filtreler için tanrı bulunamadı.')
            ->assertSee('Mineria Wiki — İnanç');
    }

    public function test_gods_index_shows_mythology_then_domains_without_separate_catalog(): void
    {
        $response = $this->get(route('valiant-shade.gods.index'));

        $response->assertOk()
            ->assertSeeInOrder([
                'Mineria Mitolojisi',
                'İlk ateş vardı.',
                'İnsanların tanrılara karşı olan hikayeleri büyüdü',
                'Tanrılar ve Domainler',
                'Kutsal silah',
            ])
            ->assertDontSee('Profillerini görüntülemek için bir tanrı seçebilirsin.')
            ->assertSee('Lawful Neutral')
            ->assertSee('Lantern Staff')
            ->assertSee('Warhammer')
            ->assertSee('Gladius');

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertSame(1, preg_match('/<tr>\s*<td class="text-center align-middle">\s*<a[^>]*>Keia<\/a>.*?<\/tr>/s', $content, $matches));
        $this->assertSame(4, substr_count($matches[0], '—'));
    }

    public function test_default_gods_have_public_profiles(): void
    {
        foreach (['belarun', 'renvil', 'rorin-hiud', 'keia', 'shea', 'kahooli', 'siwion', 'silvarin'] as $slug) {
            $this->get(route('valiant-shade.gods.show', ['slug' => $slug]))
                ->assertOk()
                ->assertSee('Tanrı profili');
        }
    }

    public function test_god_filters_show_only_selected_groups(): void
    {
        config()->set('gods.groups', [
            'major' => ['label' => 'Büyük Tanrılar', 'gods' => [['slug' => 'major-test', 'name' => 'Major Test', 'summary' => '', 'sections' => []]]],
            'minor' => ['label' => 'Küçük Tanrılar', 'gods' => [['slug' => 'minor-test', 'name' => 'Minor Test', 'summary' => '', 'sections' => []]]],
        ]);
        $majorUrl = route('valiant-shade.gods.show', ['slug' => 'major-test']);
        $minorUrl = route('valiant-shade.gods.show', ['slug' => 'minor-test']);

        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()->assertSee($majorUrl, false)->assertSee($minorUrl, false)
            ->assertSee('id="filter-major" checked', false)->assertSee('id="filter-minor" checked', false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['major']]))
            ->assertOk()->assertSee($majorUrl, false)->assertDontSee($minorUrl, false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['minor']]))
            ->assertOk()->assertSee($minorUrl, false)->assertDontSee($majorUrl, false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1]))
            ->assertOk()->assertDontSee($majorUrl, false)->assertDontSee($minorUrl, false)
            ->assertSee('Seçilen filtreler için tanrı bulunamadı.');
    }

    public function test_guest_can_update_domain_details_and_clear_existing_values(): void
    {
        $url = route('valiant-shade.gods.update', ['slug' => 'belarun']);
        $index = route('valiant-shade.gods.index');
        $this->from($index)->put($url, [
            'alignment' => 'Neutral Good',
            'domains' => "Magic\nTravel",
            'subdomains' => "Light\nStars",
            'weapon' => '<script>alert(1)</script>',
        ])->assertRedirect($index)->assertSessionHas('success');

        $this->assertSame(['Magic', 'Travel'], GodDomainOverride::findOrFail('belarun')->domains);
        $this->get($index)->assertOk()
            ->assertSee('Neutral Good')->assertSee('Magic')
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->from($index)->put($url, [
            'alignment' => '',
            'domains' => '',
            'subdomains' => '',
            'weapon' => '',
        ])->assertRedirect($index);
        $override = GodDomainOverride::findOrFail('belarun');
        $this->assertNull($override->alignment);
        $this->assertSame([], $override->domains);
        $this->assertSame([], $override->subdomains);
        $this->assertNull($override->weapon);
        $this->get($index)->assertOk()->assertDontSee('Lantern Staff')->assertDontSee('Lawful Neutral');
    }

    public function test_god_updates_validate_data_and_reject_unknown_slugs(): void
    {
        $index = route('valiant-shade.gods.index');
        $data = ['alignment' => str_repeat('x', 101), 'domains' => '', 'subdomains' => '', 'weapon' => ''];
        $this->from($index)->put(route('valiant-shade.gods.update', ['slug' => 'belarun']), $data)
            ->assertRedirect($index)->assertSessionHasErrors('alignment');
        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'belarun']);

        $this->put(route('valiant-shade.gods.update', ['slug' => 'unknown-god']), $data)->assertNotFound();
        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'unknown-god']);
    }

    public function test_category_updates_change_filters_and_profile_groups_without_losing_details(): void
    {
        GodDomainOverride::create([
            'slug' => 'belarun',
            'alignment' => 'Neutral Good',
            'domains' => ['Magic'],
            'subdomains' => ['Light'],
            'weapon' => 'Staff',
        ]);
        $majorIndex = route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['major']]);
        $minorIndex = route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['minor']]);
        $show = route('valiant-shade.gods.show', ['slug' => 'belarun']);
        $update = route('valiant-shade.gods.update', ['slug' => 'belarun']);
        $details = ['alignment' => 'Neutral Good', 'domains' => 'Magic', 'subdomains' => 'Light', 'weapon' => 'Staff'];

        $this->get($majorIndex)->assertOk()->assertSee($show, false)->assertSee('Magic');
        $this->get($minorIndex)->assertOk()->assertDontSee($show, false);

        $this->from($majorIndex)->put($update, $details + ['category' => 'minor'])
            ->assertRedirect($majorIndex)->assertSessionHas('success');
        $this->get($majorIndex)->assertOk()->assertDontSee($show, false);
        $this->get($minorIndex)->assertOk()->assertSee($show, false)->assertSee('Magic')->assertSee('Staff')
            ->assertSee('value="minor" selected', false);

        $content = $this->get($show)->assertOk()->getContent();
        $this->assertIsString($content);
        $this->assertSame(1, preg_match('/<nav[^>]*aria-label="Küçük Tanrılar"[^>]*>(.*?)<\/nav>/s', $content, $matches));
        $this->assertStringContainsString('Belarun', $matches[1]);
        $this->assertSame(1, preg_match('/<nav[^>]*aria-label="Büyük Tanrılar"[^>]*>(.*?)<\/nav>/s', $content, $matches));
        $this->assertStringNotContainsString('Belarun', $matches[1]);
        $this->assertSame(1, substr_count($content, 'aria-current="page"'));
        $this->assertSame(['Magic'], GodDomainOverride::findOrFail('belarun')->domains);

        $this->from($minorIndex)->put($update, $details + ['category' => 'major'])->assertRedirect($minorIndex);
        $this->get($minorIndex)->assertOk()->assertDontSee($show, false);
        $this->get($majorIndex)->assertOk()->assertSee($show, false);

        $this->from($majorIndex)->put($update, $details)->assertRedirect($majorIndex);
        $this->assertSame('major', GodDomainOverride::findOrFail('belarun')->category);
    }

    public function test_invalid_category_is_rejected_without_changing_details(): void
    {
        $url = route('valiant-shade.gods.update', ['slug' => 'belarun']);
        $this->from(route('valiant-shade.gods.index'))->put($url, [
            'category' => 'other',
            'alignment' => 'Neutral Good',
            'domains' => 'Magic',
            'subdomains' => 'Light',
            'weapon' => 'Staff',
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'belarun']);
    }

    public function test_legacy_gods_urls_redirect_to_panel_urls(): void
    {
        $this->get('/gods')->assertRedirect(route('valiant-shade.gods.index'));

        $this->get('/gods/siwion')
            ->assertRedirect(route('valiant-shade.gods.show', ['slug' => 'siwion']));
    }
}
