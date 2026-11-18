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
        .gods-page__profile-text {
            white-space: pre-line;
        }
        .gods-page__portrait {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
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
        .gods-page__table td.gods-page__domain-cell {
            vertical-align: middle;
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
        .gods-page .modal-dialog-scrollable form {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        @media (min-width: 992px) {
            .gods-page__sidebar-list.collapse:not(.show) {
                display: block;
            }
        }
        @media (max-width: 991.98px) {
            .gods-page__sidebar {
                position: static;
                max-height: none;
            }
        }
        @media (max-width: 767.98px) {
            .gods-page__table,
            .gods-page__table tbody,
            .gods-page__table tr,
            .gods-page__table td {
                display: block;
                width: 100%;
            }
            .gods-page__table {
                min-width: 0;
            }
            .gods-page__table thead {
                display: none;
            }
            .gods-page__table tr {
                margin: 1rem;
                width: auto;
                border: 1px solid var(--bs-border-color);
                border-radius: .5rem;
                overflow: hidden;
            }
            .gods-page__table td {
                text-align: center;
            }
            .gods-page__table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: .25rem;
                color: var(--bs-secondary-color);
                font-size: .75rem;
                font-weight: 600;
            }
            .gods-page__table ul {
                padding: 0;
                list-style: none;
            }
            .gods-page .modal-dialog {
                margin: .75rem;
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
                            <button class="btn btn-sm btn-label-secondary d-lg-none w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#gods-sidebar-list" aria-expanded="false" aria-controls="gods-sidebar-list">Tanrı listesini göster / gizle</button>
                            <div class="collapse gods-page__sidebar-list" id="gods-sidebar-list">
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
                    </div>
                </aside>
                <section class="col-12 col-lg-9">
                    <article class="card">
                        <div class="card-body p-4 p-xl-5">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="gods-page__eyebrow">Tanrı profili</div>
                                    <h1 class="display-6 mt-2">{{ $selectedGod['name'] }}</h1>
                                    @if (! empty($selectedGod['epithet']))
                                        <p class="h5 text-body-secondary">{{ $selectedGod['epithet'] }}</p>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#god-profile-edit">Profili düzenle</button>
                            </div>
                            <div class="row g-4 mt-2">
                                <div class="col-12 col-md-4">
                                    @if (! empty($selectedGod['profile_image']))
                                        <img class="gods-page__portrait rounded" src="{{ asset('storage/'.$selectedGod['profile_image']) }}" alt="{{ $selectedGod['name'] }} görseli">
                                    @else
                                        <div class="gods-page__portrait rounded bg-label-secondary d-flex align-items-center justify-content-center text-center p-3">Henüz görsel eklenmedi.</div>
                                    @endif
                                </div>
                                <div class="col-12 col-md-8">
                                    <p class="gods-page__story gods-page__profile-text">{{ $selectedGod['summary'] }}</p>
                                    @foreach ($selectedGod['sections'] as $section)
                                        <section class="gods-page__section">
                                            <h2 class="h5">{{ $section['title'] }}</h2>
                                            @foreach ($section['paragraphs'] as $paragraph)
                                                <p class="gods-page__story">{{ $paragraph }}</p>
                                            @endforeach
                                        </section>
                                    @endforeach
                                    @if (! empty($selectedGod['profile_text']))
                                        <div class="gods-page__story gods-page__profile-text mt-4">{{ $selectedGod['profile_text'] }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="small text-body-secondary mt-4">
                                Kaynak: <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Mineria Wiki — İnanç</a>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
            <div class="modal fade" id="god-profile-edit" tabindex="-1" aria-labelledby="god-profile-edit-title" aria-hidden="true" @if ($errors->any()) data-gods-edit-errors @endif>
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                        <form method="post" action="{{ route('valiant-shade.gods.profile.update', ['slug' => $selectedGod['slug']]) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h2 class="modal-title h5" id="god-profile-edit-title">Profili düzenle</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger" role="alert">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label" for="god-summary">Kısa tanıtım</label>
                                    <textarea class="form-control" id="god-summary" name="summary" rows="4" maxlength="10000">{{ $errors->any() ? (is_string(old('summary')) ? old('summary') : '') : $selectedGod['summary'] }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="god-profile-text">Profil metni</label>
                                    <textarea class="form-control" id="god-profile-text" name="profile_text" rows="8" maxlength="30000">{{ $errors->any() ? (is_string(old('profile_text')) ? old('profile_text') : '') : ($selectedGod['profile_text'] ?? '') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="god-profile-image">Profil görseli</label>
                                    <input class="form-control" id="god-profile-image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="god-profile-image-help">
                                    <div class="form-text" id="god-profile-image-help">JPG, PNG veya WebP; en fazla 5 MB ve 4096 × 4096 piksel.</div>
                                </div>
                                @if (! empty($selectedGod['profile_image']))
                                    <div class="form-check">
                                        <input class="form-check-input" id="god-remove-image" type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                                        <label class="form-check-label" for="god-remove-image">Mevcut görseli kaldır</label>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <article class="card mb-4">
                <div class="card-body p-4 p-xl-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="gods-page__eyebrow">İnanç</div>
                            <h1 class="display-6 mt-2">Mineria İnancı</h1>
                        </div>
                        <button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#religion-edit">Düzenle</button>
                    </div>
                    <div class="gods-page__story mt-4">{!! $religionHtml !!}</div>
                    <div class="small text-body-secondary mt-4">
                        Kaynak: <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Mineria Wiki — İnanç</a>
                    </div>
                </div>
            </article>

            <div class="modal fade" id="religion-edit" tabindex="-1" aria-labelledby="religion-edit-title" aria-hidden="true" @if ($errors->any() && old('editing_religion')) data-gods-edit-errors @endif>
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                        <form method="post" action="{{ route('valiant-shade.gods.religion.update') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="editing_religion" value="1">
                            <div class="modal-header">
                                <h2 class="modal-title h5" id="religion-edit-title">İnancı düzenle</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                @if ($errors->any() && old('editing_religion'))
                                    <div class="alert alert-danger" role="alert">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <label class="form-label" for="religion-text">İnanç metni</label>
                                <textarea class="form-control" id="religion-text" name="religion_text" rows="16" maxlength="50000" required>{{ old('editing_religion') && is_string(old('religion_text')) ? old('religion_text') : $religionText }}</textarea>
                                <div class="form-text">Paragrafları boş satırla ayırın. Bağlantı eklemek için önce metni seçin.</div>
                                <div class="mt-3">
                                    <label class="form-label" for="religion-link">Bağlantı adresi</label>
                                    <div class="d-flex gap-2">
                                        <input class="form-control" id="religion-link" type="url" placeholder="https://..." aria-describedby="religion-link-error">
                                        <button class="btn btn-label-primary text-nowrap" id="religion-link-add" type="button">Bağlantı ekle</button>
                                    </div>
                                    <div class="text-danger small mt-1" id="religion-link-error" role="alert"></div>
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

            <section class="card mb-4" aria-labelledby="gods-domains-title">
                <div class="card-header">
                    <h2 class="h4 mb-1" id="gods-domains-title">Tanrılar ve Domainler</h2>
                    <p class="text-body-secondary mb-0">Tanrıların bilinen domainleri, alt domainleri ve kutsal silahları.</p>
                    <form method="get" action="{{ route('valiant-shade.gods.index') }}#gods-domains-title" class="row g-3 align-items-end mt-2">
                        <input type="hidden" name="filter_applied" value="1">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="filter-group">Tanrı türü</label>
                            <select class="form-select" id="filter-group" name="group">
                                @if (empty($selectedGroups))
                                    <option value="" disabled selected>Tür seçilmedi</option>
                                @endif
                                <option value="" @selected(count($selectedGroups) > 1)>Tümü</option>
                                <option value="major" @selected(count($selectedGroups) === 1 && in_array('major', $selectedGroups, true))>Major</option>
                                <option value="minor" @selected(count($selectedGroups) === 1 && in_array('minor', $selectedGroups, true))>Minor</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                            <label class="form-label" for="filter-alignment">Hizalanma</label>
                            <select class="form-select" id="filter-alignment" name="alignment">
                                <option value="">Tümü</option>
                                @foreach ($alignments as $alignment)
                                    <option value="{{ $alignment }}" @selected($selectedAlignment === $alignment)>{{ $alignment }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                            <label class="form-label" for="filter-domain">Domain</label>
                            <select class="form-select" id="filter-domain" name="domain">
                                <option value="">Tümü</option>
                                @foreach ($domainOptions as $domainOption)
                                    <option value="{{ $domainOption }}" @selected($selectedDomain === $domainOption)>{{ $domainOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-xl-2 d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit">Filtrele</button>
                            <a class="btn btn-label-secondary" href="{{ route('valiant-shade.gods.index') }}#gods-domains-title">Sıfırla</a>
                        </div>
                    </form>
                    @if ($errors->any() && ! old('editing_religion'))
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
                                    <td class="text-center align-middle" data-label="Hizalanma">{{ $godItem['alignment'] ?? '—' }}</td>
                                    <td class="gods-page__domain-cell" data-label="Domainler">
                                        @if (! empty($godItem['domains']))
                                            <ul>
                                                @foreach ($godItem['domains'] as $domain)
                                                    <li>
                                                        @if (is_array($domain))
                                                            <a href="{{ $domain['url'] }}" target="_blank" rel="noopener noreferrer">{{ $domain['name'] }}</a>
                                                        @else
                                                            {{ $domain }}
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Alt domainler">
                                        @if (! empty($godItem['subdomains']))
                                            <ul>
                                                @foreach ($godItem['subdomains'] as $subdomain)
                                                    <li>
                                                        @if (is_array($subdomain))
                                                            <a href="{{ $subdomain['url'] }}" target="_blank" rel="noopener noreferrer">{{ $subdomain['name'] }}</a>
                                                        @else
                                                            {{ $subdomain }}
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center align-middle" data-label="Kutsal silah">{{ $godItem['weapon'] ?? '—' }}</td>
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
                @php
                    $showOld = $errors->any() && old('editing_slug') === $godItem['slug'];
                @endphp
                <div class="modal fade" id="god-edit-{{ $godItem['slug'] }}" tabindex="-1" aria-labelledby="god-edit-title-{{ $godItem['slug'] }}" aria-hidden="true" @if ($showOld) data-gods-edit-errors @endif>
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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
                                        <label class="form-label" for="name-{{ $godItem['slug'] }}">Tanrı adı</label>
                                        <input class="form-control" id="name-{{ $godItem['slug'] }}" name="name" maxlength="120" required value="{{ $showOld ? (is_string(old('name')) ? old('name') : '') : $godItem['name'] }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="epithet-{{ $godItem['slug'] }}">Unvan</label>
                                        <input class="form-control" id="epithet-{{ $godItem['slug'] }}" name="epithet" maxlength="255" value="{{ $showOld ? (is_string(old('epithet')) ? old('epithet') : '') : ($godItem['epithet'] ?? '') }}">
                                    </div>
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
                                    @foreach (['domain_entries' => ['label' => 'Domainler', 'singular' => 'Domain', 'values' => $godItem['domains'] ?? []], 'subdomain_entries' => ['label' => 'Alt domainler', 'singular' => 'Alt domain', 'values' => $godItem['subdomains'] ?? []]] as $field => $settings)
                                        @php
                                            $entries = $showOld && is_array(old($field)) ? old($field) : ($showOld && old($field.'_present') ? [] : $settings['values']);
                                            $entries = array_values($entries);
                                        @endphp
                                        <fieldset class="mb-4" data-entry-group>
                                            <legend class="form-label fs-6">{{ $settings['label'] }}</legend>
                                            <div class="form-text mb-2">Her birini ayrı ekleyin; bağlantı isteğe bağlıdır.</div>
                                            <input type="hidden" name="{{ $field }}_present" value="1">
                                            <div data-entry-list>
                                                @foreach ($entries as $entryIndex => $entry)
                                                    @include('valiant-shade.content.pages.partials.god-domain-entry', ['entry' => $entry, 'entryIndex' => $entryIndex])
                                                @endforeach
                                            </div>
                                            <template data-entry-template>
                                                @include('valiant-shade.content.pages.partials.god-domain-entry', ['entry' => '', 'entryIndex' => '__INDEX__'])
                                            </template>
                                            <button type="button" class="btn btn-sm btn-label-primary mt-2" data-entry-add>{{ $settings['singular'] }} ekle</button>
                                        </fieldset>
                                    @endforeach
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const religionText = document.getElementById('religion-text');
            const religionLink = document.getElementById('religion-link');
            const religionLinkAdd = document.getElementById('religion-link-add');
            if (religionText && religionLink && religionLinkAdd) {
                religionLinkAdd.addEventListener('click', function () {
                    const error = document.getElementById('religion-link-error');
                    const selected = religionText.value.substring(religionText.selectionStart, religionText.selectionEnd);
                    let url;
                    try {
                        url = new URL(religionLink.value);
                    } catch (exception) {
                        error.textContent = 'Geçerli bir HTTP veya HTTPS adresi girin.';
                        return;
                    }
                    if (!['http:', 'https:'].includes(url.protocol)) {
                        error.textContent = 'Geçerli bir HTTP veya HTTPS adresi girin.';
                        return;
                    }
                    if (!selected || selected.includes('\n')) {
                        error.textContent = 'Bağlantı için metin içinde tek satırlık bir bölüm seçin.';
                        return;
                    }
                    const label = selected.replace(/\\/g, '\\\\').replace(/([[\]])/g, '\\$1');
                    const link = '[' + label + '](' + url.href.replace(/[()]/g, '\\$&') + ')';
                    if (religionText.value.length - selected.length + link.length > religionText.maxLength) {
                        error.textContent = 'Bağlantı eklendiğinde metin uzunluk sınırını aşıyor.';
                        return;
                    }
                    religionText.setRangeText(link, religionText.selectionStart, religionText.selectionEnd, 'end');
                    religionText.focus();
                    religionLink.value = '';
                    error.textContent = '';
                });
            }
            document.querySelectorAll('[data-entry-group]').forEach(function (group) {
                const list = group.querySelector('[data-entry-list]');
                const template = group.querySelector('[data-entry-template]');
                const add = group.querySelector('[data-entry-add]');
                let nextIndex = list.querySelectorAll('[data-entry-row]').length;
                const updateAddButton = () => { add.disabled = list.querySelectorAll('[data-entry-row]').length >= 100; };
                add.addEventListener('click', function () {
                    const row = template.content.cloneNode(true);
                    row.querySelectorAll('[id], [for], [name]').forEach(function (element) {
                        ['id', 'for', 'name'].forEach(function (attribute) {
                            if (element.hasAttribute(attribute)) {
                                element.setAttribute(attribute, element.getAttribute(attribute).replace('__INDEX__', nextIndex));
                            }
                        });
                    });
                    nextIndex++;
                    list.appendChild(row);
                    updateAddButton();
                    list.lastElementChild.querySelector('input').focus();
                });
                list.addEventListener('click', function (event) {
                    const remove = event.target.closest('[data-entry-remove]');
                    if (remove) {
                        remove.closest('[data-entry-row]').remove();
                        updateAddButton();
                        add.focus();
                    }
                });
                updateAddButton();
            });
            @if ($errors->any())
                const modal = document.querySelector('[data-gods-edit-errors]');
                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            @endif
        });
    </script>
@endsection
