@extends('valiant-shade.layouts/layoutMaster')

@section('title', $selectedGod['name'] ?? 'Tanrılar')

@section('page-style')
    <style>
        .gods-page {
            width: 100%;
            max-width: 1440px;
            margin-inline: auto;
        }
        .gods-page__eyebrow {
            color: var(--bs-primary);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .gods-page__story {
            max-width: 90ch;
            line-height: 1.85;
        }
        .gods-page__story p {
            margin-bottom: 1.25rem;
        }
        .gods-page__story p:last-child {
            margin-bottom: 0;
        }
        .gods-page__table {
            min-width: 900px;
            table-layout: fixed;
        }
        .gods-page__table td {
            vertical-align: top;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .gods-page__table ul {
            margin: 0;
            padding-left: 1.15rem;
        }
        .gods-page__sidebar {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }
        .gods-page__link {
            border-radius: .375rem;
            color: inherit;
        }
        .gods-page__link:hover,
        .gods-page__link.active {
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .12);
        }
        .gods-page__section + .gods-page__section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--bs-border-color);
        }
        @media (max-width: 991.98px) {
            .gods-page__sidebar {
                position: static;
                max-height: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="gods-page">
        @if ($selectedGod)
            <div class="row g-4 align-items-start">
                <aside class="col-12 col-lg-3" aria-label="Tanrı listesi">
                    <div class="card gods-page__sidebar">
                        <div class="card-body">
                            <a class="d-inline-block mb-3" href="{{ route('valiant-shade.gods.index') }}">Tanrılara dön</a>
                            @foreach ($godGroups as $group)
                                <h2 class="h6 text-body-secondary mt-3">{{ $group['label'] }}</h2>
                                <nav class="nav flex-column gap-1" aria-label="{{ $group['label'] }}">
                                    @forelse ($group['gods'] as $godItem)
                                        <a
                                            class="nav-link gods-page__link {{ ($selectedGod['slug'] ?? null) === $godItem['slug'] ? 'active' : '' }}"
                                            href="{{ route('valiant-shade.gods.show', ['slug' => $godItem['slug']]) }}"
                                            @if (($selectedGod['slug'] ?? null) === $godItem['slug']) aria-current="page" @endif
                                        >{{ $godItem['name'] }}</a>
                                    @empty
                                        <span class="small text-body-secondary px-3 py-2">İçerik bekleniyor</span>
                                    @endforelse
                                </nav>
                            @endforeach
                        </div>
                    </div>
                </aside>
                <section class="col-12 col-lg-9">
                    <article class="card">
                        <div class="card-body p-4 p-xl-5">
                            <div class="gods-page__eyebrow">Tanrı profili</div>
                            <h1 class="display-6 mt-2">{{ $selectedGod['name'] }}</h1>
                            @if (! empty($selectedGod['epithet']))
                                <p class="h5 text-body-secondary">{{ $selectedGod['epithet'] }}</p>
                            @endif
                            <p class="gods-page__story mt-3">{{ $selectedGod['summary'] }}</p>
                            @foreach ($selectedGod['sections'] as $section)
                                <section class="gods-page__section">
                                    <h2 class="h5">{{ $section['title'] }}</h2>
                                    @foreach ($section['paragraphs'] as $paragraph)
                                        <p class="gods-page__story">{{ $paragraph }}</p>
                                    @endforeach
                                </section>
                            @endforeach
                            <div class="small text-body-secondary mt-4">
                                Kaynak: <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Mineria Wiki — İnanç</a>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        @else
            <article class="card mb-4">
                <div class="card-body p-4 p-xl-5">
                    <div class="gods-page__eyebrow">İnanç</div>
                    <h1 class="display-6 mt-2">Mineria Mitolojisi</h1>
                    <div class="gods-page__story mt-4">
                        <p>Mineria'da çok tanrılı bir inanç sistemi yaygındır. Genel kabul görmüş yaratılış mitolojisi ise şöyledir:</p>
                        <p>İlk ateş vardı. İnsanlar ısınsın, yaşayabilsinler diye. Ona koruyucu gözüyle bakan insanlar, ateşi kendi ruhlarıyla bezediler. Ruhlar birleşti, Bir ateş taşıyıcı uyandı. İlk tanrı ateşti, ilk tanrının adı <strong>Belarun'</strong>du.</p>
                        <p>Ormansoyu uyanmaya başladı. Doğada kendilerine yer buldular, doğaya isim verdiler, doğaya saygılarını sundular. <strong>Keia</strong> varoldu.</p>
                        <p>Cüceler gözlerini açtılar dağlarda, dağa minnet duydular, kendilerinden biriymiş gibi sevdiler, beraber çalıştılar. Dağların eteklerine hayran kaldılar, esen rüzgara, havanın ve kayanın gücüne. Bir gün <strong>Renvil</strong> doğdu.</p>
                        <p>Ka-hooli ve Shea'nın hikayeleri en uzunu. Yıllar, yüzyıllar geçti. İnsanlar medeniyet yarattı, kendi silahlarını, kendi toplulukları, kendi şehirlerini kurdular. İmparatorluk dediler yarattıkları ülkeye. Tüm Dünya tek imparatorluktu. Öyle bir şehir vardı ki ama, hepsinden büyük, hepsinden güzel; Sol.</p>
                        <p>Sol'da büyü bulundu. Kullananlarda kibir belirdi. İnsan değillerdi onlar artık, onlar büyücüydü. Bu güce inananlar, tapanlar ortaya çıktı. Onlar ortaya çıkınca da, büyü bir kadının vücudunda toplandı: <strong>Shea</strong>!</p>
                        <p>Shea ortaya çıkar çıkmaz yeni güneşi yarattı. Hala gökyüzünü aydınlatan güneşi. Karanlığı azalttı, soğuk topraklara daha da hayat getirdi. Shea'nın takipçileri güçlüydü, zengindi, insandan daha insandı. Artık onlar insan, diğerleriyse altsoydu. Büyücüler yeni başkenti taşıdılar, artık İmparatorluğa gerek de yoktu. Sadece Sol ve Shea yeterdi.</p>
                        <p>Altsoy rahatsızdı. Altsoy mutsuzdu. Doğuştan yapamadıkları şeyler için ceza çekmek istemiyordu. Aralarından bir tanesi çıktı, diğerleri ona baktı. Bir maske taktı, ve yemin etti. Uzun zamandır yaşanan büyücü baskısına, yarattıkları acıya son verecekti. Savaşlar uzun sürdü. Halk birbirine girerde, nasıl kısa sürer zaten. Nefret büyüdü, intikam isteği arttı. Kahooli kazandı, altsoy sazandı. Artık yeni bir tanrı vardı, <strong>Kahooli</strong>! Shea, Kahooli tarafından katledildi, savaş bitti.</p>
                        <p>Krallıklar doğmaya başladılar toprakta. Haksızlık bitmiş, her insan kendi kaderini çalışarak kontrol edebilir olmuştu. Onur, gurur, çalışkanlık her bireyin içinde doğdu büyüdü, beraberinde de <strong>Siwion'</strong>u getirdi.</p>
                        <p>Krallıklar büyüdükçe, zaman kendini tekrar etti. Krallar hep daha fazlasını istiyordu. Kendileri için savaşacak cesur insanlara ihtiyaçları vardı. Siwion'un öğrettiği gibi onur için değil, altının parıltısı için öleceklere. Yıllar böyle geçti. Rhen adındaki bir ölümlü tanrıları rahatsız etti, ve dünyayı karıştırdı. Uyanan tanrılar insanı küçülttü. Onurunu kırdı.</p>
                        <p>Krallıklar bitti, şehir devletler kaldı. Tabi onlar için savaşanlar. İnsanlar sarı metalin peşinde savaştı, ve toprağı suladı. Bir paralı asker <strong>Rorin Hiud</strong>, gerçek hazinenin topraklarda değil tanrılarda olduğunu söyledi. Binler ona güldü, ama ismi büyüdü. Yaptığı olağanüstü yolculuklar dilden dile dolandı. İnsanların onurunu geri getireceğine inandılar. Ona güvenenlerin yardımıyla tanrılardan altınları alıp, halka vereceğini söyledi. Peşinde binlerle yolculuğa çıktı. Dönmedi, ama ona güven hala bitmedi.</p>
                        <p>İnsanların tanrılara karşı olan hikayeleri büyüdü, anlatıldıkça güzelleşti. İnsanlar şarkıyı, hikayeyi hiç bu kadar derinden hissetmemişlerdi. <strong>Silvarin</strong> doğdu.</p>
                    </div>
                    <div class="small text-body-secondary mt-4">
                        Kaynak: <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Mineria Wiki — İnanç</a>
                    </div>
                </div>
            </article>

            <section class="card mb-4" aria-labelledby="gods-domains-title">
                <div class="card-header">
                    <h2 class="h4 mb-1" id="gods-domains-title">Tanrılar ve Domainler</h2>
                    <p class="text-body-secondary mb-0">Tanrıların bilinen domainleri, alt domainleri ve kutsal silahları.</p>
                    <form method="get" action="{{ route('valiant-shade.gods.index') }}#gods-domains-title" class="d-flex flex-wrap align-items-center gap-3 mt-4">
                        <input type="hidden" name="filter_applied" value="1">
                        <span class="fw-semibold">Tanrı türü:</span>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="groups[]" value="major" id="filter-major" @checked(in_array('major', $selectedGroups, true))>
                            <label class="form-check-label" for="filter-major">Major</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="groups[]" value="minor" id="filter-minor" @checked(in_array('minor', $selectedGroups, true))>
                            <label class="form-check-label" for="filter-minor">Minor</label>
                        </div>
                        <button class="btn btn-sm btn-label-primary" type="submit">Filtrele</button>
                    </form>
                    @if ($errors->any())
                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover gods-page__table">
                        <colgroup>
                            <col style="width: 16%">
                            <col style="width: 14%">
                            <col style="width: 17%">
                            <col style="width: 27%">
                            <col style="width: 14%">
                            <col style="width: 12%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Tanrı</th>
                                <th scope="col" class="text-center">Hizalanma</th>
                                <th scope="col">Domainler</th>
                                <th scope="col">Alt domainler</th>
                                <th scope="col" class="text-center">Kutsal silah</th>
                                <th scope="col"><span class="visually-hidden">İşlemler</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($visibleGods as $godItem)
                                <tr>
                                    <td class="text-center align-middle">
                                        <a class="fw-semibold" href="{{ route('valiant-shade.gods.show', ['slug' => $godItem['slug']]) }}">{{ $godItem['name'] }}</a>
                                        @if (! empty($godItem['epithet']))
                                            <div class="small text-body-secondary">{{ $godItem['epithet'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $godItem['alignment'] ?? '—' }}</td>
                                    <td>
                                        @if (! empty($godItem['domains']))
                                            <ul>
                                                @foreach ($godItem['domains'] as $domain)
                                                    <li>{{ $domain }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if (! empty($godItem['subdomains']))
                                            <ul>
                                                @foreach ($godItem['subdomains'] as $subdomain)
                                                    <li>{{ $subdomain }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $godItem['weapon'] ?? '—' }}</td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-label-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#god-edit-{{ $godItem['slug'] }}" aria-label="{{ $godItem['name'] }} bilgilerini düzenle">Düzenle</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-body-secondary">Seçilen filtreler için tanrı bulunamadı.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @foreach ($visibleGods as $godItem)
                @php($showOld = $errors->any() && old('editing_slug') === $godItem['slug'])
                <div class="modal fade" id="god-edit-{{ $godItem['slug'] }}" tabindex="-1" aria-labelledby="god-edit-title-{{ $godItem['slug'] }}" aria-hidden="true" @if ($showOld) data-gods-edit-errors @endif>
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="post" action="{{ route('valiant-shade.gods.update', ['slug' => $godItem['slug']]) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_slug" value="{{ $godItem['slug'] }}">
                                <div class="modal-header">
                                    <h2 class="modal-title h5" id="god-edit-title-{{ $godItem['slug'] }}">{{ $godItem['name'] }} bilgilerini düzenle</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($showOld)
                                        <div class="alert alert-danger" role="alert">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <div class="mb-3">
                                        <label class="form-label" for="category-{{ $godItem['slug'] }}">Tanrı türü</label>
                                        <select class="form-select" id="category-{{ $godItem['slug'] }}" name="category" required>
                                            <option value="major" @selected(($showOld ? old('category', $godItem['category']) : $godItem['category']) === 'major')>Major</option>
                                            <option value="minor" @selected(($showOld ? old('category', $godItem['category']) : $godItem['category']) === 'minor')>Minor</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="alignment-{{ $godItem['slug'] }}">Hizalanma</label>
                                        <input class="form-control" id="alignment-{{ $godItem['slug'] }}" name="alignment" maxlength="100" value="{{ $showOld ? (is_string(old('alignment')) ? old('alignment') : '') : ($godItem['alignment'] ?? '') }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="domains-{{ $godItem['slug'] }}">Domainler</label>
                                        <textarea class="form-control" id="domains-{{ $godItem['slug'] }}" name="domains" rows="4" maxlength="5000" aria-describedby="domains-help-{{ $godItem['slug'] }}">{{ $showOld ? (is_string(old('domains')) ? old('domains') : '') : implode("\n", $godItem['domains'] ?? []) }}</textarea>
                                        <div class="form-text" id="domains-help-{{ $godItem['slug'] }}">Her domaini ayrı bir satıra yazın.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="subdomains-{{ $godItem['slug'] }}">Alt domainler</label>
                                        <textarea class="form-control" id="subdomains-{{ $godItem['slug'] }}" name="subdomains" rows="4" maxlength="5000" aria-describedby="subdomains-help-{{ $godItem['slug'] }}">{{ $showOld ? (is_string(old('subdomains')) ? old('subdomains') : '') : implode("\n", $godItem['subdomains'] ?? []) }}</textarea>
                                        <div class="form-text" id="subdomains-help-{{ $godItem['slug'] }}">Her alt domaini ayrı bir satıra yazın.</div>
                                    </div>
                                    <div>
                                        <label class="form-label" for="weapon-{{ $godItem['slug'] }}">Kutsal silah</label>
                                        <input class="form-control" id="weapon-{{ $godItem['slug'] }}" name="weapon" maxlength="255" value="{{ $showOld ? (is_string(old('weapon')) ? old('weapon') : '') : ($godItem['weapon'] ?? '') }}">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">İptal</button>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection

@section('page-script')
    @if (! $selectedGod && $errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.querySelector('[data-gods-edit-errors]');
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endsection
