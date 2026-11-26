@php
    use App\Support\PublicSiteUrls;
    $activePortal = $activePortal ?? '';
@endphp
<div class="vs-newspaper-nav__cluster vs-newspaper-nav__cluster--main">
    <a href="{{ PublicSiteUrls::main() }}" class="vs-newspaper-nav__item{{ $activePortal === 'home' ? ' vs-newspaper-nav__item--active' : '' }}" aria-label="{{ __('Main page') }}">
        <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5z"/></svg>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Main page') }}</span>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Main page (nav short)') }}</span>
    </a>
    <a href="{{ PublicSiteUrls::gazette('/') }}" class="vs-newspaper-nav__item{{ $activePortal === 'gazette' ? ' vs-newspaper-nav__item--active' : '' }}" aria-label="{{ __('Gazette') }}">
        <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19h16M6 5h12v10H6z"/><path d="M8 9h8M8 12h5"/></svg>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Gazette') }}</span>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Gazette') }}</span>
    </a>
    <a href="{{ PublicSiteUrls::map('/feldan') }}" class="vs-newspaper-nav__item{{ $activePortal === 'map' ? ' vs-newspaper-nav__item--active' : '' }}" aria-label="{{ __('Map') }}">
        <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2V6z"/><path d="M9 4v14M15 6v14"/></svg>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Map') }}</span>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Map') }}</span>
    </a>
    <a href="{{ PublicSiteUrls::npcRoster('/') }}" class="vs-newspaper-nav__item{{ $activePortal === 'npc' ? ' vs-newspaper-nav__item--active' : '' }}" aria-label="{{ __('NPC Roster') }}">
        <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0"/><path d="M12 14v7"/><path d="M8 21h8"/><circle cx="12" cy="7" r="3"/></svg>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('NPC Roster') }}</span>
        <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">NPC</span>
    </a>
</div>