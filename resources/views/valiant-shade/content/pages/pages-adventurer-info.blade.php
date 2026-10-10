@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Adventurer Info & Guild General Summary') . ' - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  .maceraci-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .maceraci-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
  }
  .progress-bar-animated-smooth {
    transition: width 0.6s ease-in-out;
  }
  .cursor-pointer {
    cursor: pointer;
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')
<!-- Header & Action Toolbar (Executive Dashboard Style) -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h4 class="mb-1 fw-bold text-heading d-flex align-items-center gap-2">
      <span class="avatar avatar-sm rounded-3 bg-label-primary d-flex align-items-center justify-content-center shadow-xs">
        <i class="icon-base ti tabler-layout-dashboard fs-4"></i>
      </span>
      <span>{{ __('Adventurer Info & Guild General Summary') }}</span>
    </h4>
    <p class="text-body-secondary small mb-0">{{ __('Valiant Shade guild level, Bailiff council, rank financial pools, allowances, and inventory management cockpit.') }}</p>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Export / Print Dropdown -->
    <div class="dropdown">
      <button type="button" class="btn btn-label-secondary btn-sm rounded-pill dropdown-toggle waves-effect shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="icon-base ti tabler-upload me-1"></i> {{ __('Export') }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
          <a class="dropdown-item d-flex align-items-center" href="javascript:window.print();">
            <i class="icon-base ti tabler-printer me-2 text-info"></i> {{ __('Print Page') }}
          </a>
        </li>
      </ul>
    </div>

    @if($isAdmin)
      <!-- Edit Guild Level Modal Trigger -->
      <button type="button" class="btn btn-label-primary btn-sm rounded-pill waves-effect shadow-xs" data-bs-toggle="modal" data-bs-target="#modalGuildLevel" title="{{ __('Edit Guild Level') }}">
        <i class="icon-base ti tabler-crown me-1"></i> {{ __('Guild Level') }}
      </button>

      <!-- Reset to Master Defaults -->
      <button type="button" class="btn btn-label-warning btn-sm rounded-pill waves-effect shadow-xs" id="btnResetMaceraciDefaults" title="{{ __('Reset to Original Master Sheet Data') }}">
        <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i> {{ __('Reset (Master Sheet)') }}
      </button>
    @else
      <span class="badge bg-label-secondary rounded-pill d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
        <i class="icon-base ti tabler-lock fs-6 text-warning"></i>
        <span>{{ __('Read Only') }}</span>
      </span>
    @endif
  </div>
</div>

<!-- Master Tabs Card -->
<div class="card border shadow-none mb-4">
  <div class="card-header border-bottom p-3">
    <ul class="nav nav-pills card-header-pills flex-wrap gap-1" id="maceraciInfoTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="tab-roster" data-bs-toggle="pill" data-bs-target="#content-roster" type="button" role="tab">
          <i class="icon-base ti tabler-users fs-5"></i>
          <span class="fw-semibold">{{ __('Adventurer Roster (XP Ranking)') }}</span>
          <span class="badge rounded-pill bg-primary ms-1">{{ $roster->count() }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="tab-allowances" data-bs-toggle="pill" data-bs-target="#content-allowances" type="button" role="tab">
          <i class="icon-base ti tabler-coin fs-5"></i>
          <span class="fw-semibold">{{ __('Allowance & Salary Tracker (SP)') }}</span>
          <span class="badge rounded-pill bg-label-warning ms-1">{{ $allowances['count'] }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="tab-inventory" data-bs-toggle="pill" data-bs-target="#content-inventory" type="button" role="tab">
          <i class="icon-base ti tabler-shield-check fs-5"></i>
          <span class="fw-semibold">{{ __('Valuable Guild Inventory (Vault & Gear)') }}</span>
          <span class="badge rounded-pill bg-label-info ms-1">{{ $inventorySummary['items']->count() }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="tab-overview" data-bs-toggle="pill" data-bs-target="#content-overview" type="button" role="tab">
          <i class="icon-base ti tabler-dashboard fs-5"></i>
          <span class="fw-semibold">{{ __('Management Cockpit (Overview)') }}</span>
        </button>
      </li>
    </ul>
  </div>

  <div class="card-body p-4">
    <div class="tab-content p-0" id="maceraciInfoTabsContent">
      
      <!-- ================= TAB 1: MACERACI KADROSU (XP SIRALAMASI) ================= -->
      <div class="tab-pane fade show active" id="content-roster" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-0 fw-bold text-heading">{{ __('Adventurer Ranking & XP List') }}</h5>
            <small class="text-body-secondary">{{ __('All guild heroes ranked by XP in Master Sheet.') }}</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-merge input-group-sm" style="min-width: 220px;">
              <span class="input-group-text"><i class="icon-base ti tabler-search fs-6"></i></span>
              <input type="text" class="form-control" id="searchRosterTable" placeholder="{{ __('Search character...') }}">
            </div>
            <select class="form-select form-select-sm w-auto" id="filterRosterRank">
              <option value="">{{ __('All Ranks') }}</option>
              @foreach($ranks as $rk)
                <option value="{{ $rk }}">{{ $rk }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="table-responsive text-nowrap rounded border">
          <table class="table table-hover align-middle mb-0" id="tableMaceraciRoster">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;" class="text-center">{{ __('# Rank') }}</th>
                <th>{{ __('Adventurer') }}</th>
                <th class="text-center">{{ __('Rank') }}</th>
                <th class="text-center">{{ __('Level') }}</th>
                <th class="text-center">{{ __('XP Progress') }}</th>
                <th class="text-end">{{ __('Total XP') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($roster as $index => $adv)
                @php
                  $effectiveLevel = $adv->effective_level ?? $adv->level ?? 1;
                  $progressPercent = method_exists($adv, 'getProgressPercentage') 
                    ? $adv->getProgressPercentage() 
                    : ($adv->progress_percent ?? 50.0);
                  $nextLevelXp = method_exists($adv, 'getNextLevelXp') 
                    ? $adv->getNextLevelXp() 
                    : ($adv->next_level_xp ?? 145000);
                  $progressLabel = method_exists($adv, 'getXpProgressLabel') 
                    ? $adv->getXpProgressLabel() 
                    : ($adv->progress_label ?? (number_format($adv->total_xp) . ' / ' . number_format($nextLevelXp) . ' XP'));

                  $rankColor = match($adv->rank) {
                    'Clerk' => 'bg-label-info',
                    'Aday' => 'bg-label-secondary',
                    'Üye' => 'bg-label-primary',
                    'Uzman' => 'bg-label-success',
                    'Kıdemli' => 'bg-label-warning',
                    'Gezgin' => 'bg-label-info',
                    'Inaktif' => 'bg-label-dark',
                    default => 'bg-label-secondary'
                  };
                @endphp
                <tr class="roster-row" data-name="{{ strtolower($adv->character_name) }}" data-rank="{{ $adv->rank }}" data-level="{{ $effectiveLevel }}">
                  <td class="text-center font-monospace fw-bold {{ $index < 3 ? 'text-primary' : 'text-body-secondary' }}">
                    {{ $index + 1 }}
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-label-primary fw-bold text-uppercase">
                          {{ mb_substr($adv->character_name, 0, 2) }}
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-0 text-heading fw-bold">{{ $adv->character_name }}</h6>
                        <small class="text-body-secondary">{{ $adv->player_name ?: __('Unclaimed / GM') }}</small>
                      </div>
                    </div>
                  </td>
                  <!-- Clean Rank Badge, No Emojis -->
                  <td class="text-center">
                    <span class="badge {{ $rankColor }} rounded-pill px-3 py-1 font-monospace">
                      {{ __($adv->rank) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-body border font-monospace text-heading fw-bold">
                      Lv {{ $effectiveLevel }}
                    </span>
                  </td>
                  <td class="text-center" style="min-width: 180px;">
                    <div class="d-flex flex-column" style="min-width: 170px;">
                      <div class="d-flex justify-content-between align-items-center mb-1 font-monospace small" style="font-size: 0.76rem;">
                        <span>{{ $progressLabel }}</span>
                        <span>%{{ $progressPercent }}</span>
                      </div>
                      <div class="progress rounded-pill shadow-xs" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progressPercent }}%;"></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-end font-monospace fw-bold text-heading fs-6">
                    {{ number_format($adv->total_xp) }} <span class="text-body-secondary small">XP</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- ================= TAB 3: STIPEND & SALARY TRACKING (SP) ================= -->
      <div class="tab-pane fade" id="content-allowances" role="tabpanel">
        <div class="row g-4 mb-4">
          <div class="col-12 col-md-4">
            <div class="card border shadow-none bg-body">
              <div class="card-body p-3">
                <span class="text-body-secondary small">{{ __('Total Distributed Allowance:') }}</span>
                <h4 class="mb-0 fw-bold font-monospace text-warning mt-1">{{ number_format($allowances['total_sp']) }} SP</h4>
                <small class="text-body-secondary">({{ __('Equivalent in GP') }}: {{ number_format($allowances['total_gp']) }} GP)</small>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card border shadow-none bg-body">
              <div class="card-body p-3">
                <span class="text-body-secondary small">{{ __('Number of Characters Receiving Allowance:') }}</span>
                <h4 class="mb-0 fw-bold font-monospace text-primary mt-1">{{ $allowances['count'] }} {{ __('Adventurers') }}</h4>
                <small class="text-body-secondary">{{ __('Active fund assignees') }}</small>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card border shadow-none bg-body">
              <div class="card-body p-3">
                <span class="text-body-secondary small">{{ __('Average Allowance Per Person:') }}</span>
                <h4 class="mb-0 fw-bold font-monospace text-success mt-1">{{ number_format($allowances['average_sp']) }} SP</h4>
                <small class="text-body-secondary">{{ __('Average budget per adventurer') }}</small>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="input-group input-group-merge input-group-sm" style="min-width: 250px;">
            <span class="input-group-text"><i class="icon-base ti tabler-search fs-6"></i></span>
            <input type="text" class="form-control" id="searchAllowanceTable" placeholder="{{ __('Search character receiving allowance...') }}">
          </div>
          @if($isAdmin)
            <button type="button" class="btn btn-sm btn-primary rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEditAllowance">
              <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add / Update Allowance') }}
            </button>
          @endif
        </div>

        <div class="table-responsive text-nowrap rounded border">
          <table class="table table-hover align-middle mb-0" id="tableAllowances">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>{{ __('Character Name') }}</th>
                <th class="text-center">{{ __('Rank') }}</th>
                <th class="text-center">{{ __('Level') }}</th>
                <th class="text-end">{{ __('Allowance (SP)') }}</th>
                <th class="text-end">{{ __('GP Equivalent') }}</th>
                @if($isAdmin)
                  <th class="text-end">{{ __('Action') }}</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @foreach($allowances['items'] as $idx => $item)
                @php
                  $rankColor = match($item->rank) {
                    'Clerk' => 'bg-label-info',
                    'Aday' => 'bg-label-secondary',
                    'Üye' => 'bg-label-primary',
                    'Uzman' => 'bg-label-success',
                    'Kıdemli' => 'bg-label-warning',
                    'Gezgin' => 'bg-label-info',
                    'Inaktif' => 'bg-label-dark',
                    default => 'bg-label-secondary'
                  };
                @endphp
                <tr class="allowance-row" data-name="{{ strtolower($item->character_name) }}">
                  <td class="font-monospace text-body-secondary">{{ $idx + 1 }}</td>
                  <td>
                    <span class="fw-bold text-heading">{{ $item->character_name }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge {{ $rankColor }} rounded-pill px-3 py-1 font-monospace">
                      {{ $item->rank }}
                    </span>
                  </td>
                  <td class="text-center font-monospace">
                    Lv {{ $item->effective_level }}
                  </td>
                  <td class="text-end font-monospace fw-bold text-heading fs-6">
                    {{ number_format($item->allowance_sp) }} <span class="text-body-secondary small">SP</span>
                  </td>
                  <td class="text-end font-monospace text-success fw-semibold">
                    {{ number_format(round($item->allowance_sp / 10, 1)) }} GP
                  </td>
                  @if($isAdmin)
                    <td class="text-end">
                      <button type="button" class="btn btn-xs btn-label-primary rounded-pill btn-quick-edit-allowance"
                              data-name="{{ $item->character_name }}"
                              data-sp="{{ $item->allowance_sp }}">
                        <i class="icon-base ti tabler-edit me-1"></i> {{ __('Edit') }}
                      </button>
                    </td>
                  @endif
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- ================= TAB 4: VALUABLE GUILD INVENTORY (VAULT & EQUIPMENT) ================= -->
      <div class="tab-pane fade" id="content-inventory" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h5 class="mb-0 fw-bold text-heading">{{ __('Valuable Guild Inventory & Magical Equipment') }}</h5>
            <small class="text-body-secondary">{{ __('Summary of armors, weapons, rings, and magical items in Master Sheet.') }}</small>
          </div>
          <div class="input-group input-group-merge input-group-sm" style="min-width: 240px;">
            <span class="input-group-text"><i class="icon-base ti tabler-search fs-6"></i></span>
            <input type="text" class="form-control" id="searchInventoryTable" placeholder="{{ __('Search item or type...') }}">
          </div>
        </div>

        <div class="table-responsive text-nowrap rounded border">
          <table class="table table-hover align-middle mb-0" id="tableMaceraciInventory">
            <thead class="table-light">
              <tr>
                <th>{{ __('Item Name') }}</th>
                <th class="text-center">{{ __('Quality') }}</th>
                <th class="text-center">{{ __('Category (Type)') }}</th>
                <th class="text-center">{{ __('Quantity') }}</th>
                <th class="text-end">{{ __('Unit Price') }}</th>
                <th class="text-end">{{ __('Total (SP)') }}</th>
                <th class="text-end">{{ __('Resale Value') }}</th>
                <th class="text-end">{{ __('GP Value') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($inventorySummary['items'] as $it)
                @php
                  $typeBadge = match($it->type) {
                    'Weapon' => 'bg-label-danger',
                    'Armor' => 'bg-label-primary',
                    'Gear' => 'bg-label-info',
                    'Ammunition' => 'bg-label-warning',
                    'Wondrous Item' => 'bg-label-success',
                    default => 'bg-label-secondary'
                  };
                @endphp
                <tr class="inv-row" data-name="{{ strtolower($it->name) }}" data-type="{{ strtolower($it->type) }}">
                  <td>
                    <span class="fw-bold text-heading">{{ $it->name }}</span>
                  </td>
                  <td class="text-center font-monospace">
                    @if($it->quality)
                      <span class="badge bg-label-dark rounded-pill">{{ $it->quality }}</span>
                    @else
                      <span class="text-body-secondary small">—</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <span class="badge {{ $typeBadge }} rounded-pill font-monospace">
                      {{ $it->type }}
                    </span>
                  </td>
                  <td class="text-center font-monospace fw-bold">
                    {{ $it->quantity }}
                  </td>
                  <td class="text-end font-monospace text-body-secondary">
                    {{ number_format($it->unit_price) }}
                  </td>
                  <td class="text-end font-monospace fw-bold text-heading">
                    {{ number_format($it->total_price) }} SP
                  </td>
                  <td class="text-end font-monospace text-info">
                    {{ number_format($it->resale_price) }} SP
                  </td>
                  <td class="text-end font-monospace text-success fw-semibold">
                    {{ number_format($it->gp_value) }} GP
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- ================= TAB 4: GENEL SAYFA (GUILD LEVEL & BAILIFF'S) ================= -->
      <div class="tab-pane fade" id="content-overview" role="tabpanel">
        <div class="row g-4">
          <!-- 1. Guild Level Card -->
          <div class="col-12 col-lg-5 col-xl-4">
            <div class="card border shadow-none h-100">
              <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-crown text-primary fs-5"></i>
                  <span>{{ __('Guild Level') }}</span>
                </h6>
                <span class="badge bg-label-primary font-monospace">{{ __('Rank 12') }}</span>
              </div>
              <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                  <div class="avatar avatar-xl rounded-circle bg-label-primary mx-auto mb-3 d-flex align-items-center justify-content-center">
                    <i class="icon-base ti tabler-crown fs-1 text-primary"></i>
                  </div>
                  <h3 class="mb-1 fw-bold text-heading font-monospace">{{ __('Level :level Guild', ['level' => $guildLevel['level']]) }}</h3>
                  <p class="text-body-secondary small mb-3">{{ __('Valiant Shade guild rank and prestige level') }}</p>
                  
                  <div class="bg-body border rounded p-3 mb-3 text-start">
                    <div class="d-flex justify-content-between align-items-center mb-1 font-monospace small">
                      <span>{{ __('Current Progress:') }}</span>
                      <strong class="text-primary">{{ number_format($guildLevel['current_points']) }} / {{ number_format($guildLevel['current_points'] + $guildLevel['next_level_points']) }} {{ __('Points') }}</strong>
                    </div>
                    <div class="progress rounded-pill shadow-xs" style="height: 8px;">
                      <div class="progress-bar bg-primary progress-bar-animated-smooth" role="progressbar" style="width: {{ $guildLevel['progress_percent'] }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small font-monospace text-body-secondary">
                      <span>{{ __('Progress: :percent%', ['percent' => $guildLevel['progress_percent']]) }}</span>
                      <span>{{ __('Remaining to Level 13:') }} <strong class="text-danger">{{ number_format($guildLevel['next_level_points']) }} {{ __('Points') }}</strong></span>
                    </div>
                  </div>
                </div>

                @if($isAdmin)
                  <button type="button" class="btn btn-sm btn-label-primary rounded-pill w-100 mt-2" data-bs-toggle="modal" data-bs-target="#modalGuildLevel">
                    <i class="icon-base ti tabler-edit me-1"></i> {{ __('Update Level and Points') }}
                  </button>
                @endif
              </div>
            </div>
          </div>

          <!-- 2. Bailiff's Council Card & Table -->
          <div class="col-12 col-lg-7 col-xl-8">
            <div class="card border shadow-none h-100">
              <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-badges text-primary fs-5"></i>
                  <span>{{ __("Bailiff's") }}</span>
                </h6>
                <span class="badge bg-label-primary font-monospace">{{ $bailiffs->count() }} {{ __('Officers') }}</span>
              </div>
              <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>{{ __('Bailiff Official') }}</th>
                      <th>{{ __('Guild Role') }}</th>
                      <th class="text-center">{{ __('Start Date') }}</th>
                      <th class="text-center">{{ __('Status') }}</th>
                      @if($isAdmin)
                        <th class="text-end">{{ __('Action') }}</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($bailiffs as $b)
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-xs">
                              <span class="avatar-initial rounded-circle bg-label-primary fw-bold font-monospace">
                                {{ mb_substr($b->character_name, 0, 2) }}
                              </span>
                            </div>
                            <span class="fw-bold text-heading">{{ $b->character_name }}</span>
                          </div>
                        </td>
                        <td>
                          <span class="badge bg-label-info rounded-pill px-3 py-1 font-monospace">
                            {{ $b->role }}
                          </span>
                        </td>
                        <td class="text-center font-monospace text-body-secondary small">
                          {{ $b->assigned_date ?: '—' }}
                        </td>
                        <td class="text-center">
                          <span class="badge bg-label-success rounded-pill font-monospace" style="font-size: 0.72rem;">{{ __('Active') }}</span>
                        </td>
                        @if($isAdmin)
                          <td class="text-end">
                            <button type="button" class="btn btn-xs btn-icon btn-label-primary rounded-pill btn-edit-bailiff"
                                    data-id="{{ $b->id }}"
                                    data-name="{{ $b->character_name }}"
                                    data-role="{{ $b->role }}"
                                    data-date="{{ $b->assigned_date }}"
                                    data-status="{{ $b->status }}"
                                    title="{{ __('Edit Role') }}">
                              <i class="icon-base ti tabler-pencil fs-6"></i>
                            </button>
                          </td>
                        @endif
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ================= MODALS & OFFCANVAS DRAWERS ================= -->

<!-- 1. Modal: Edit Guild Level -->
<div class="modal fade" id="modalGuildLevel" tabindex="-1" aria-labelledby="modalGuildLevelLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-heading" id="modalGuildLevelLabel">{{ __('Edit Guild Level') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formGuildLevel">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold" for="inputGuildLevel">{{ __('Guild Level') }}</label>
            <input type="number" class="form-control font-monospace" id="inputGuildLevel" name="guild_level" value="{{ $guildLevel['level'] }}" min="1" max="30" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" for="inputNextPoints">{{ __('Next Level Point Requirement') }}</label>
            <input type="number" class="form-control font-monospace" id="inputNextPoints" name="next_level_points" value="{{ $guildLevel['next_level_points'] }}" min="0" required>
          </div>
        </div>
        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-primary rounded-pill" id="btnSubmitGuildLevel">{{ __('Save') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Offcanvas: Edit Allowance -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditAllowance" aria-labelledby="offcanvasEditAllowanceLabel" style="width: 400px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditAllowanceLabel" class="offcanvas-title fw-bold text-heading">{{ __('Update Character Allowance') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body p-4">
    <form id="formEditAllowance">
      @csrf
      <div class="mb-3">
        <label class="form-label fw-semibold" for="inputAllowanceChar">{{ __('Character Name') }}</label>
        <input type="text" class="form-control" id="inputAllowanceChar" name="character_name" placeholder="{{ __('e.g. Bart, Kerrik...') }}" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold" for="inputAllowanceSp">{{ __('Allowance Amount (SP)') }}</label>
        <div class="input-group">
          <input type="number" class="form-control font-monospace" id="inputAllowanceSp" name="allowance_sp" min="0" placeholder="0" required>
          <span class="input-group-text">SP</span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="btnSubmitAllowance">{{ __('Save Allowance') }}</button>
        <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Modal: Edit Bailiff Officer -->
<div class="modal fade" id="modalEditBailiff" tabindex="-1" aria-labelledby="modalEditBailiffLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold text-heading" id="modalEditBailiffLabel">{{ __('Edit Bailiff Role') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formEditBailiff">
        @csrf
        @method('PUT')
        <input type="hidden" id="editBailiffId" name="id">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold" for="editBailiffName">{{ __('Official Name') }}</label>
            <input type="text" class="form-control" id="editBailiffName" name="character_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" for="editBailiffRole">{{ __('Guild Role / Department') }}</label>
            <input type="text" class="form-control" id="editBailiffRole" name="role" placeholder="{{ __('e.g. Questmaster, Archivist...') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" for="editBailiffDate">{{ __('Role Start Date') }}</label>
            <input type="text" class="form-control font-monospace" id="editBailiffDate" name="assigned_date" placeholder="{{ __('e.g. 14/7') }}">
          </div>
        </div>
        <div class="modal-footer border-top p-3">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-primary rounded-pill" id="btnSubmitBailiff">{{ __('Save Role') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  
  // 1. Live Filter for Roster Table
  const searchRoster = document.getElementById('searchRosterTable');
  const filterRosterRank = document.getElementById('filterRosterRank');

  function filterRoster() {
    const term = (searchRoster ? searchRoster.value : '').toLowerCase().trim();
    const rank = filterRosterRank ? filterRosterRank.value : '';

    document.querySelectorAll('.roster-row').forEach(row => {
      const name = row.dataset.name || '';
      const rk = row.dataset.rank || '';
      const matchName = !term || name.includes(term);
      const matchRank = !rank || rk === rank;

      row.style.display = (matchName && matchRank) ? '' : 'none';
    });
  }

  if (searchRoster) searchRoster.addEventListener('input', filterRoster);
  if (filterRosterRank) filterRosterRank.addEventListener('change', filterRoster);

  // 2. Live Filter for Allowances Table
  const searchAllowance = document.getElementById('searchAllowanceTable');
  if (searchAllowance) {
    searchAllowance.addEventListener('input', function() {
      const term = this.value.toLowerCase().trim();
      document.querySelectorAll('.allowance-row').forEach(row => {
        const name = row.dataset.name || '';
        row.style.display = (!term || name.includes(term)) ? '' : 'none';
      });
    });
  }

  // 3. Live Filter for Inventory Table
  const searchInventory = document.getElementById('searchInventoryTable');
  if (searchInventory) {
    searchInventory.addEventListener('input', function() {
      const term = this.value.toLowerCase().trim();
      document.querySelectorAll('.inv-row').forEach(row => {
        const name = row.dataset.name || '';
        const type = row.dataset.type || '';
        row.style.display = (!term || name.includes(term) || type.includes(term)) ? '' : 'none';
      });
    });
  }

  // 4. Quick Edit Allowance Trigger
  document.querySelectorAll('.btn-quick-edit-allowance').forEach(btn => {
    btn.addEventListener('click', function() {
      const name = this.dataset.name;
      const sp = this.dataset.sp;
      document.getElementById('inputAllowanceChar').value = name;
      document.getElementById('inputAllowanceSp').value = sp;
      const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEditAllowance'));
      offcanvas.show();
    });
  });

  // 5. Submit Allowance Form
  const formAllowance = document.getElementById('formEditAllowance');
  if (formAllowance) {
    formAllowance.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitAllowance');
      btn.disabled = true;

      const payload = {
        character_name: document.getElementById('inputAllowanceChar').value,
        allowance_sp: parseInt(document.getElementById('inputAllowanceSp').value)
      };

      fetch("{{ route('valiant-shade.pages-adventurer-info.allowance') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: "{{ __('Allowance Updated!') }}",
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: "{{ __('A network error occurred.') }}" });
      });
    });
  }

  // 6. Edit Bailiff Officer Trigger
  document.querySelectorAll('.btn-edit-bailiff').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('editBailiffId').value = this.dataset.id;
      document.getElementById('editBailiffName').value = this.dataset.name;
      document.getElementById('editBailiffRole').value = this.dataset.role;
      document.getElementById('editBailiffDate').value = this.dataset.date;
      const modal = new bootstrap.Modal(document.getElementById('modalEditBailiff'));
      modal.show();
    });
  });

  // 7. Submit Bailiff Form
  const formBailiff = document.getElementById('formEditBailiff');
  if (formBailiff) {
    formBailiff.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('editBailiffId').value;
      const btn = document.getElementById('btnSubmitBailiff');
      btn.disabled = true;

      const payload = {
        character_name: document.getElementById('editBailiffName').value,
        role: document.getElementById('editBailiffRole').value,
        assigned_date: document.getElementById('editBailiffDate').value
      };

      fetch("{{ url('/valiant-shade/pages/adventurer-info/bailiff') }}/" + id, {
        method: 'PUT',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: "{{ __('Bailiff Role Updated!') }}",
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: "{{ __('A network error occurred.') }}" });
      });
    });
  }

  // 8. Submit Guild Level Form
  const formGuildLevel = document.getElementById('formGuildLevel');
  if (formGuildLevel) {
    formGuildLevel.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitGuildLevel');
      btn.disabled = true;

      const payload = {
        guild_level: parseInt(document.getElementById('inputGuildLevel').value),
        next_level_points: parseInt(document.getElementById('inputNextPoints').value)
      };

      fetch("{{ route('valiant-shade.pages-adventurer-info.guild-level') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: "{{ __('Guild Level Updated!') }}",
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: "{{ __('A network error occurred.') }}" });
      });
    });
  }

  // 9. Reset to Master Sheet Defaults
  const btnReset = document.getElementById('btnResetMaceraciDefaults');
  if (btnReset) {
    btnReset.addEventListener('click', function() {
      Swal.fire({
        title: "{{ __('Reset to Master Sheet Data?') }}",
        text: "{{ __('Bailiff council, guild level, and allowances will be reset to original Google Sheet values!') }}",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: "{{ __('Yes, Reset') }}",
        cancelButtonText: "{{ __('Dismiss') }}",
        customClass: {
          confirmButton: 'btn btn-warning rounded-pill me-2',
          cancelButton: 'btn btn-label-secondary rounded-pill'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch("{{ route('valiant-shade.pages-adventurer-info.reset-defaults') }}", {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: "{{ __('Reset Completed!') }}",
                text: data.message,
                timer: 1800,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: "{{ __('Error!') }}", text: data.message });
            }
          });
        }
      });
    });
  }

});
</script>
@endsection
