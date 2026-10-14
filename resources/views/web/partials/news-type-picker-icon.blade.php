@switch($typeKey)
    @case('royal_news')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 20h12M9 20V10l-2-2V6a3 3 0 0 1 6 0v2l-2 2v10"/><path d="M12 6V4"/><path d="M8 4h8"/></svg>
        @break
    @case('guild_news')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v6c0 4.5-3.5 7.5-7 8-3.5-.5-7-3.5-7-8V7l7-4z"/></svg>
        @break
    @case('royal_rumor')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M9 21v-6h6v6"/><path d="M9 10h6"/></svg>
        @break
    @case('guild_rumor')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17V9"/><path d="M7 9h10l-1-4H8L7 9z"/><path d="M6 9c0 2.5 1.5 4 3 5s3 2.5 3 5"/></svg>
        @break
    @case('environment_rumor')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21c-4-3-7-6-7-10a7 7 0 0 1 14 0c0 4-3 7-7 10z"/><path d="M12 11v0"/></svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2"/></svg>
@endswitch
