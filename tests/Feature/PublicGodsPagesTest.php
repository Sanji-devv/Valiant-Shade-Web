<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicGodsPagesTest extends TestCase
{
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
            ->assertSeeInOrder(['Büyük Tanrılar', 'Siwion', 'Küçük Tanrılar', 'Küçük Örnek']);

        $this->get(route('valiant-shade.gods.show', ['slug' => 'siwion']))
            ->assertOk()
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
        $this->get(route('valiant-shade.gods.index'))
            ->assertOk()
            ->assertSee('Tanrı listesi, kaynak bilgileri eklendiğinde burada görünecek.')
            ->assertSee('Mineria Wiki — İnanç');
    }

    public function test_legacy_gods_urls_redirect_to_panel_urls(): void
    {
        $this->get('/gods')->assertRedirect(route('valiant-shade.gods.index'));

        $this->get('/gods/siwion')
            ->assertRedirect(route('valiant-shade.gods.show', ['slug' => 'siwion']));
    }
}
