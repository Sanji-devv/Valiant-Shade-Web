<?php

namespace Tests\Feature;

use App\Models\GodDomainOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $this->assertStringContainsString('Chaotic Neutral', $matches[0]);
        $this->assertStringContainsString('Quarterstaff', $matches[0]);
        $this->assertStringContainsString('Vermin (UW)', $matches[0]);
    }

    public function test_supplied_gods_have_profiles_and_expected_categories(): void
    {
        $major = ['belarun', 'bo-leos', 'harados', 'kahooli', 'keia', 'renvil', 'rorin-hiud', 'saubalil', 'shea', 'siwion', 'silvarin'];
        $minor = ['morax', 'diamania', 'nianor', 'ilk-yildiz', 'korr-vagore', 'livakh', 'nymoria', 'ommos', 'thalor', 'xir-keth', 'chancuhtili', 'aeleos', 'kenzariel', 'mara-kuro', 'myrion'];
        $groups = config('gods.groups');

        $this->assertEqualsCanonicalizing($major, array_column($groups['major']['gods'], 'slug'));
        $this->assertEqualsCanonicalizing($minor, array_column($groups['minor']['gods'], 'slug'));
        foreach (array_merge($major, $minor) as $slug) {
            $this->get(route('valiant-shade.gods.show', ['slug' => $slug]))->assertOk();
        }

        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'group' => 'minor', 'domain' => 'Artifice']))
            ->assertOk()
            ->assertSee(route('valiant-shade.gods.show', ['slug' => 'chancuhtili']), false)
            ->assertDontSee(route('valiant-shade.gods.show', ['slug' => 'belarun']), false)
            ->assertSee('Dwarf')
            ->assertSee('Petrification');
    }

    public function test_existing_override_takes_priority_over_new_catalog_data(): void
    {
        GodDomainOverride::create([
            'slug' => 'keia',
            'alignment' => 'Lawful Good',
            'domains' => [['name' => 'Existing', 'url' => 'https://example.com/domain']],
            'subdomains' => ['Existing subdomain'],
            'weapon' => 'Existing weapon',
            'summary' => 'Existing profile',
        ]);

        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()
            ->assertSee('https://example.com/domain', false)
            ->assertSee('Existing subdomain')
            ->assertSee('Existing weapon');
        $this->get(route('valiant-shade.gods.show', ['slug' => 'keia']))
            ->assertOk()->assertSee('Existing profile');
    }

    public function test_default_gods_have_public_profiles(): void
    {
        foreach (['belarun', 'renvil', 'rorin-hiud', 'keia', 'shea', 'kahooli', 'siwion', 'silvarin'] as $slug) {
            $this->get(route('valiant-shade.gods.show', ['slug' => $slug]))
                ->assertOk()
                ->assertSee('Tanrı profili');
        }
    }

    public function test_renvil_subdomains_are_individual_entries(): void
    {
        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()
            ->assertSee('value="Caves"', false)
            ->assertSee('value="Metal"', false)
            ->assertDontSee('CavesMetal');
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
            ->assertSee('id="filter-group" name="group"', false)->assertSee('value="" selected>Tümü', false);
        $this->get(route('valiant-shade.gods.index').'?filter_applied=1&group=')
            ->assertOk()->assertSee($majorUrl, false)->assertSee($minorUrl, false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'group' => 'major']))
            ->assertOk()->assertSee($majorUrl, false)->assertDontSee($minorUrl, false)
            ->assertSee('value="major" selected>Major', false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'group' => 'minor']))
            ->assertOk()->assertSee($minorUrl, false)->assertDontSee($majorUrl, false)
            ->assertSee('value="minor" selected>Minor', false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['major']]))
            ->assertOk()->assertSee($majorUrl, false)->assertDontSee($minorUrl, false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1, 'groups' => ['minor']]))
            ->assertOk()->assertSee($minorUrl, false)->assertDontSee($majorUrl, false);
        $this->get(route('valiant-shade.gods.index', ['filter_applied' => 1]))
            ->assertOk()->assertDontSee($majorUrl, false)->assertDontSee($minorUrl, false)
            ->assertSee('Seçilen filtreler için tanrı bulunamadı.');
    }

    public function test_filters_combine_category_alignment_and_linked_or_plain_domains(): void
    {
        GodDomainOverride::create([
            'slug' => 'renvil',
            'category' => 'minor',
            'alignment' => 'Neutral Good',
            'domains' => [['name' => 'Fire', 'url' => 'https://example.com/fire'], 'Air'],
            'subdomains' => [],
            'weapon' => null,
        ]);
        $major = route('valiant-shade.gods.show', ['slug' => 'belarun']);
        $minor = route('valiant-shade.gods.show', ['slug' => 'renvil']);

        $this->get(route('valiant-shade.gods.index', [
            'filter_applied' => 1, 'group' => 'minor', 'alignment' => 'Neutral Good', 'domain' => 'Fire',
        ]))->assertOk()->assertSee($minor, false)->assertDontSee($major, false)
            ->assertSee('value="Neutral Good" selected', false)
            ->assertSee('value="Fire" selected', false)
            ->assertSee('value="Air"', false);

        $this->get(route('valiant-shade.gods.index', [
            'filter_applied' => 1, 'groups' => ['major', 'minor'], 'alignment' => 'Lawful Neutral', 'domain' => 'Fire',
        ]))->assertOk()->assertSee($major, false)->assertDontSee($minor, false);

        $this->get(route('valiant-shade.gods.index', [
            'filter_applied' => 1, 'groups' => ['minor'], 'domain' => 'Unknown',
        ]))->assertOk()->assertDontSee($minor, false)
            ->assertSee('value="Unknown" selected', false)
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

    public function test_god_name_epithet_and_domain_links_can_be_updated_without_changing_slug(): void
    {
        $index = route('valiant-shade.gods.index');
        $show = route('valiant-shade.gods.show', ['slug' => 'belarun']);
        $this->from($index)->put(route('valiant-shade.gods.update', ['slug' => 'belarun']), [
            'name' => 'Yeni İsim',
            'epithet' => 'Yeni Unvan',
            'category' => 'major',
            'alignment' => 'Neutral Good',
            'domains' => "Fire | https://example.com/fire\nSun",
            'subdomains' => 'Light | https://example.com/light',
            'weapon' => 'Staff',
        ])->assertRedirect($index)->assertSessionHas('success');

        $this->get($index)->assertOk()
            ->assertSee($show, false)->assertSee('Yeni İsim')->assertSee('Yeni Unvan')
            ->assertSee('href="https://example.com/fire"', false)
            ->assertSee('href="https://example.com/light"', false)
            ->assertSee('value="https://example.com/fire"', false);
        $this->get($show)->assertOk()->assertSee('Yeni İsim')->assertSee('Yeni Unvan');
        $this->assertSame(['name' => 'Fire', 'url' => 'https://example.com/fire'], GodDomainOverride::findOrFail('belarun')->domains[0]);
    }

    public function test_invalid_domain_links_are_rejected(): void
    {
        $this->from(route('valiant-shade.gods.index'))->put(route('valiant-shade.gods.update', ['slug' => 'belarun']), [
            'name' => 'Belarun',
            'epithet' => '',
            'alignment' => '',
            'domains' => 'Fire | javascript:alert(1)',
            'subdomains' => '',
            'weapon' => '',
        ])->assertSessionHasErrors('domains');

        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'belarun']);
    }

    public function test_individual_domain_entries_round_trip_and_can_all_be_removed(): void
    {
        $url = route('valiant-shade.gods.update', ['slug' => 'belarun']);
        $index = route('valiant-shade.gods.index');
        $details = [
            'alignment' => 'Neutral Good',
            'weapon' => 'Staff',
            'domain_entries_present' => '1',
            'subdomain_entries_present' => '1',
            'domain_entries' => [
                ['name' => 'Fire', 'url' => 'https://example.com/fire'],
                ['name' => 'Sun', 'url' => ''],
                ['name' => '', 'url' => ''],
            ],
            'subdomain_entries' => [
                ['name' => 'Light', 'url' => 'https://example.com/light'],
                ['name' => 'Ash', 'url' => ''],
            ],
        ];
        $this->from($index)->put($url, $details)->assertRedirect($index)->assertSessionHas('success');
        $override = GodDomainOverride::findOrFail('belarun');
        $this->assertSame([['name' => 'Fire', 'url' => 'https://example.com/fire'], 'Sun'], $override->domains);
        $this->assertSame([['name' => 'Light', 'url' => 'https://example.com/light'], 'Ash'], $override->subdomains);
        $this->get($index)->assertOk()
            ->assertSee('name="domain_entries[0][name]"', false)
            ->assertSee('value="https://example.com/fire"', false)
            ->assertSee('name="subdomain_entries[1][name]"', false)
            ->assertSee('value="Ash"', false);

        unset($details['domain_entries'], $details['subdomain_entries']);
        $this->from($index)->put($url, $details)->assertRedirect($index)->assertSessionHas('success');
        $override->refresh();
        $this->assertSame([], $override->domains);
        $this->assertSame([], $override->subdomains);
    }

    public function test_individual_entries_reject_invalid_urls_and_missing_names_without_writing(): void
    {
        $index = route('valiant-shade.gods.index');
        $url = route('valiant-shade.gods.update', ['slug' => 'belarun']);
        $invalidEntries = [
            'editing_slug' => 'belarun',
            'alignment' => '',
            'weapon' => '',
            'domain_entries_present' => '1',
            'subdomain_entries_present' => '1',
            'domain_entries' => [['name' => 'Fire', 'url' => 'javascript:alert(1)']],
            'subdomain_entries' => [['name' => '', 'url' => 'https://example.com/light']],
        ];
        $this->from($index)->put($url, $invalidEntries)
            ->assertRedirect($index)->assertSessionHasErrors(['domain_entries.0.url', 'subdomain_entries.0.name']);
        $response = $this->followingRedirects()->from($index)->put($url, $invalidEntries);
        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'belarun']);
        $response->assertOk()
            ->assertSee('data-gods-edit-errors', false)
            ->assertSee('Domain bağlantısı geçerli bir HTTP veya HTTPS adresi olmalıdır.')
            ->assertSee('Bağlantı eklenen alt domainin adını girin.')
            ->assertSee('value="javascript:alert(1)"', false)
            ->assertSee('value="https://example.com/light"', false);
    }

    public function test_guest_can_edit_profile_text_and_replace_or_remove_an_image(): void
    {
        Storage::fake('public');
        $show = route('valiant-shade.gods.show', ['slug' => 'belarun']);
        $update = route('valiant-shade.gods.profile.update', ['slug' => 'belarun']);
        $this->from($show)->put($update, [
            'summary' => 'Yeni tanıtım',
            'profile_text' => "Birinci paragraf\n<script>alert(1)</script>",
            'profile_image' => UploadedFile::fake()->image('god.jpg'),
        ])->assertRedirect($show)->assertSessionHas('success');

        $override = GodDomainOverride::findOrFail('belarun');
        $oldImage = $override->profile_image;
        $this->assertNotNull($oldImage);
        Storage::disk('public')->assertExists($oldImage);
        $this->assertContains('Fire', $override->domains);
        $this->get($show)->assertOk()->assertSee('Yeni tanıtım')->assertSee('Birinci paragraf')
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('storage/'.$oldImage, false);

        $this->from($show)->put($update, [
            'summary' => 'Yeni tanıtım',
            'profile_text' => 'Yeni metin',
            'profile_image' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect($show);
        $newImage = GodDomainOverride::findOrFail('belarun')->profile_image;
        $this->assertNotNull($newImage);
        $this->assertNotSame($oldImage, $newImage);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($newImage);

        $this->from($show)->put($update, [
            'summary' => 'Yeni tanıtım',
            'profile_text' => '',
            'remove_image' => '1',
        ])->assertRedirect($show);
        $this->assertNull(GodDomainOverride::findOrFail('belarun')->profile_image);
        Storage::disk('public')->assertMissing($newImage);
        $this->get($show)->assertOk()->assertSee('Henüz görsel eklenmedi.');
    }

    public function test_profile_rejects_non_image_uploads_and_conflicting_image_actions(): void
    {
        Storage::fake('public');
        $show = route('valiant-shade.gods.show', ['slug' => 'belarun']);
        $update = route('valiant-shade.gods.profile.update', ['slug' => 'belarun']);
        $this->from($show)->put($update, [
            'summary' => 'Tanıtım',
            'profile_text' => '',
            'profile_image' => UploadedFile::fake()->createWithContent('file.txt', 'not an image'),
        ])->assertSessionHasErrors('profile_image');
        $this->from($show)->put($update, [
            'summary' => 'Tanıtım',
            'profile_text' => '',
            'profile_image' => UploadedFile::fake()->image('god.jpg'),
            'remove_image' => '1',
        ])->assertSessionHasErrors('profile_image');
        $this->assertDatabaseMissing('god_domain_overrides', ['slug' => 'belarun']);
        $this->assertSame([], Storage::disk('public')->allFiles('gods'));
    }

    public function test_legacy_gods_urls_redirect_to_panel_urls(): void
    {
        $this->get('/gods')->assertRedirect(route('valiant-shade.gods.index'));

        $this->get('/gods/siwion')
            ->assertRedirect(route('valiant-shade.gods.show', ['slug' => 'siwion']));
    }
}
