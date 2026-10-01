@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Guild Inventory - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')
<!-- Header & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h4 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
      <span class="avatar avatar-sm rounded-3 bg-label-primary d-flex align-items-center justify-content-center">
        <i class="icon-base ti tabler-package fs-4"></i>
      </span>
      <span>{{ __('Guild Inventory') }}</span>
    </h4>
  </div>
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Currency Switcher (Least to Greatest: CP, SP, GP, PP) -->
    <div class="d-flex align-items-center bg-body border rounded-pill p-1 shadow-xs" id="currencySwitcherGroup" role="group" aria-label="Currency Switcher">
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 currency-btn font-monospace fw-semibold" data-currency="CP" data-rate="100" title="1 GP = 100 CP">
        <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #ff9f43;"></span>{{ __('Copper (CP)') }}
      </button>
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 currency-btn active bg-primary text-white shadow-xs font-monospace fw-semibold" data-currency="SP" data-rate="10" title="1 GP = 10 SP">
        <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #cbd5e1;"></span>{{ __('Silver (SP)') }}
      </button>
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 currency-btn font-monospace fw-semibold" data-currency="GP" data-rate="1" title="1 GP = 1 GP">
        <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #fbbf24;"></span>{{ __('Gold (GP)') }}
      </button>
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 currency-btn font-monospace fw-semibold" data-currency="PP" data-rate="0.1" title="10 GP = 1 PP">
        <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #00cfe8;"></span>{{ __('Platinum (PP)') }}
      </button>
    </div>

    <button type="button" class="btn btn-label-secondary btn-sm rounded-pill waves-effect shadow-xs" id="btnExportCsv" title="{{ __('Export CSV') }}">
      <i class="icon-base ti tabler-file-spreadsheet me-1"></i> {{ __('Export CSV') }}
    </button>

    @if($isAdmin)
      <button type="button" class="btn btn-label-warning btn-sm rounded-pill waves-effect shadow-xs" id="btnResetDefaults" title="{{ __('Reset to Original Master Sheet Data') }}">
        <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i> {{ __('Reset') }}
      </button>
      <button type="button" class="btn btn-primary btn-sm rounded-pill waves-effect waves-light shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAddItem">
        <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add New Item') }}
      </button>
    @else
      <span class="badge bg-label-secondary rounded-pill d-flex align-items-center gap-1 py-2 px-3 shadow-xs" title="Deleting, updating, and adding items are restricted to Administrators.">
        <i class="icon-base ti tabler-lock fs-6 text-warning"></i>
        <span>{{ __('Read Only (Admin Access Required)') }}</span>
      </span>
    @endif
  </div>
</div>

<!-- Currency Conversion Information Banner (Vuexy Gradient Banner) -->
<div class="card mb-4 border-0 shadow-none" style="background: linear-gradient(72.47deg, rgba(115, 103, 240, 0.14) 0%, rgba(115, 103, 240, 0.03) 100%);">
  <div class="card-body py-2 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar avatar-xs rounded-2 bg-label-primary d-flex align-items-center justify-content-center">
        <i class="icon-base ti tabler-arrows-exchange fs-6"></i>
      </span>
      <span class="small">
        <span class="text-body-secondary">Active Currency:</span>
        <strong id="lblActiveCurrencyName" class="text-heading ms-1">Silver Piece (SP)</strong>
        <span class="text-body-secondary mx-2 opacity-50">&bull;</span>
        <span class="text-body-secondary">1 Platinum = 10 Gold = 100 Silver = 1,000 Copper</span>
      </span>
    </div>
    <span class="badge bg-primary text-white font-monospace px-3 py-1 shadow-xs" id="lblActiveRateText">1 GP = 10 SP</span>
  </div>
</div>

<!-- Statistical Metric Cards (Vuexy Dark Mode Architecture) -->
<div class="row g-4 mb-4">
  <!-- Card 1: Guild Vault (Cash - 30k Silver) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">Guild Vault (Cash)</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace" id="statVaultCash" data-base-sp="{{ $metrics['vault_cash_sp'] ?? 30000 }}">
                {{ number_format($metrics['vault_cash_converted'] ?? 30000, 2, '.', ',') }}
              </h3>
              <span class="badge bg-label-warning font-monospace curr-badge-label">SP</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ti tabler-building-bank icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
          <small class="text-body-secondary">Physical Cash Vault (30k Silver)</small>
          @if($isAdmin)
            <button type="button" class="btn btn-xs btn-label-warning rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#modalManageVault">
              <i class="icon-base ti tabler-pencil icon-xs me-1"></i> Manage
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Current Inventory Value (Base Purchase Value) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">Current Inventory Value</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace" id="statTotalPrice" data-base-val="{{ $metrics['grand_total_price'] ?? 0 }}">
                {{ number_format(($metrics['grand_total_price'] ?? 0) * 10, 2, '.', ',') }}
              </h3>
              <span class="badge bg-label-primary font-monospace curr-badge-label">SP</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ti tabler-package icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">Total market value of listed items</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 3: Sale / Market Value (55%) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">Market Value (55%)</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-success font-monospace" id="statMarketValue" data-base-val="{{ $metrics['grand_total_market_value'] ?? 0 }}">
                {{ number_format(($metrics['grand_total_market_value'] ?? 0) * 10, 2, '.', ',') }}
              </h3>
              <span class="badge bg-label-success font-monospace curr-badge-label">SP</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ti tabler-scale icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">Merchant liquidation cash yield</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 4: Total Guild Wealth & Volume -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">Total Guild Wealth</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-info font-monospace" id="statTotalWealth">
                {{ number_format((($metrics['vault_cash_converted'] ?? 30000) + (($metrics['grand_total_market_value'] ?? 0) * 10)), 2, '.', ',') }}
              </h3>
              <span class="badge bg-label-info font-monospace curr-badge-label">SP</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ti tabler-chart-pie icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
          <small class="text-body-secondary">Cash + 55% Item Value</small>
          <span class="badge bg-label-secondary font-monospace" id="statQtyCount">{{ $metrics['total_items_count'] ?? 0 }} Types / {{ $metrics['total_quantity'] ?? 0 }} Items</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Table Card -->
<div class="card shadow-none border">
  <!-- Filtering and Search Bar -->
  <div class="card-header border-bottom py-3 px-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
      <div>
        <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-layout-grid text-primary fs-4"></i>
          Item Catalog
        </h5>
        <small class="text-body-secondary">Filter by category or live search in the catalog</small>
      </div>

      <!-- Search Input -->
      <div class="input-group input-group-merge shadow-xs" style="max-width: 320px;">
        <span class="input-group-text"><i class="icon-base ti tabler-search text-body-secondary"></i></span>
        <input type="text" id="inventorySearchInput" class="form-control form-control-sm" placeholder="Search item name, quality or note..." />
        <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClearSearch" title="Clear">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
    </div>

    <!-- Category Pill Filters (All 11 Types) -->
    <div class="d-flex align-items-center gap-1_5 flex-wrap pt-2 border-top" id="categoryFilterGroup">
      <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1_5 filter-type-btn active" data-type="all">
        <i class="icon-base ti tabler-list me-1"></i> All ({{ $metrics['total_items_count'] ?? 0 }})
      </button>
      @foreach(\App\Models\InventoryItem::TYPES as $typeKey => $typeCfg)
        @php
          $typeCount = $metrics['type_breakdown'][$typeKey]['count'] ?? 0;
        @endphp
        <button type="button" class="btn btn-xs btn-label-secondary rounded-pill px-3 py-1_5 filter-type-btn d-inline-flex align-items-center gap-1_5" data-type="{{ $typeKey }}">
          <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 7px; height: 7px; background-color: {{ $typeCfg['dot_color'] }}; box-shadow: 0 0 5px {{ $typeCfg['dot_color'] }};"></span>
          <span>{{ $typeKey }}</span>
          @if($typeCount > 0)
            <span class="badge rounded-pill bg-body text-heading px-1_5 py-0" style="font-size: 0.65rem;">{{ $typeCount }}</span>
          @endif
        </button>
      @endforeach
    </div>
  </div>

  <!-- Inventory Table -->
  <div class="table-responsive text-nowrap">
    <table class="table table-hover align-middle mb-0" id="inventoryTable">
      <thead class="border-bottom">
        <tr>
          <th style="width: 50px;" class="text-center">#</th>
          <th>Inventory (Item Name)</th>
          <th style="width: 100px;" class="text-center">Quality</th>
          <th style="width: 130px;" class="text-center">Type</th>
          <th style="width: 70px;" class="text-center">Qty</th>
          <th style="width: 130px;" class="text-end col-header-unit">Unit (<span class="curr-col-label">SP</span>)</th>
          <th style="width: 140px;" class="text-end col-header-total">Total (<span class="curr-col-label">SP</span>)</th>
          <th style="width: 140px;" class="text-end col-header-market">Market (55% <span class="curr-col-label">SP</span>)</th>
          <th style="width: 130px;" class="text-end col-header-buy">Buy Price (<span class="curr-col-label">SP</span>)</th>
          <th style="width: 90px;" class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0" id="inventoryTableBody">
        @forelse ($items as $index => $item)
          <tr data-id="{{ $item->id }}"
              data-type="{{ $item->type }}"
              data-name="{{ strtolower($item->name) }}"
              data-quality="{{ strtolower($item->quality ?? '') }}"
              data-base-unit="{{ $item->unit_price }}"
              data-base-total="{{ $item->total_price }}"
              data-base-market="{{ $item->market_value }}"
              data-base-buy="{{ $item->buy_price }}">
            <td class="text-center text-body-secondary font-monospace">{{ $index + 1 }}</td>
            <td>
              <div>
                <span class="fw-semibold text-heading item-name-text">{{ $item->name }}</span>
                @if($item->notes)
                  <small class="text-body-secondary d-block text-truncate" style="max-width: 250px;">{{ $item->notes }}</small>
                @endif
              </div>
            </td>
            <td class="text-center">
              @if ($item->type === 'Wand')
                <span class="badge bg-label-info rounded-pill px-2 py-0_5 font-monospace fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;" title="Wand: {{ $item->wand_charges ?? 50 }}/50 charges">
                  <i class="icon-base ti tabler-wand fs-6"></i>
                  <span>{{ $item->quality ?? ($item->wand_charges . ' charges') }}</span>
                </span>
              @elseif ($item->quality)
                <span class="badge bg-label-warning rounded-pill px-2 py-0_5 font-monospace fw-semibold" style="font-size: 0.72rem;">
                  {{ $item->quality }}
                </span>
              @else
                <span class="text-body-secondary opacity-25">&mdash;</span>
              @endif
            </td>
            <td class="text-center">
              @php
                $tCfg = $item->type_config;
              @endphp
              <span class="badge rounded-pill px-2_5 py-1 font-monospace d-inline-flex align-items-center gap-1_5" style="{{ $tCfg['badge_style'] }}; font-size: 0.74rem;">
                <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 7px; height: 7px; background-color: {{ $tCfg['dot_color'] }}; box-shadow: 0 0 4px {{ $tCfg['dot_color'] }};"></span>
                <span>{{ $item->type }}</span>
              </span>
            </td>
            <td class="text-center fw-bold font-monospace text-heading">{{ $item->quantity }}</td>
            <!-- Values render in Silver by default (SP: Base GP * 10) -->
            <td class="text-end font-monospace cell-unit-price">
              <span class="cell-num">{{ number_format($item->unit_price * 10, 0, '.', ',') }}</span> <small class="text-body-secondary curr-unit-symbol">SP</small>
            </td>
            <td class="text-end font-monospace fw-bold text-heading cell-total-price">
              <span class="cell-num">{{ number_format($item->total_price * 10, 0, '.', ',') }}</span> <small class="text-body-secondary curr-unit-symbol">SP</small>
            </td>
            <td class="text-end font-monospace fw-semibold text-success cell-market-value">
              <span class="cell-num">{{ number_format($item->market_value * 10, 1, '.', ',') }}</span> <small class="text-success text-opacity-75 curr-unit-symbol">SP</small>
            </td>
            <td class="text-end font-monospace text-body cell-buy-price">
              <span class="cell-num">{{ number_format($item->buy_price * 10, 1, '.', ',') }}</span> <small class="text-body-secondary curr-unit-symbol">SP</small>
            </td>
            <td class="text-center">
              @if($isAdmin)
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <button type="button" class="btn btn-icon btn-text-warning btn-sm rounded-pill btn-edit-item"
                    data-id="{{ $item->id }}"
                    data-name="{{ $item->name }}"
                    data-quality="{{ $item->quality }}"
                    data-type="{{ $item->type }}"
                    data-quantity="{{ $item->quantity }}"
                    data-unit-price="{{ $item->unit_price }}"
                    data-notes="{{ $item->notes }}"
                    title="Edit Item">
                    <i class="icon-base ti tabler-edit fs-5"></i>
                  </button>
                  <button type="button" class="btn btn-icon btn-text-danger btn-sm rounded-pill btn-delete-item"
                    data-id="{{ $item->id }}"
                    data-name="{{ $item->name }}"
                    title="Delete Item">
                    <i class="icon-base ti tabler-trash fs-5"></i>
                  </button>
                </div>
              @else
                <span class="text-body-secondary opacity-50" title="Administrator access required"><i class="icon-base ti tabler-lock fs-5"></i></span>
              @endif
            </td>
          </tr>
        @empty
          <tr id="emptyTableRow">
            <td colspan="10" class="text-center py-5 text-body-secondary">
              <i class="icon-base ti tabler-package-off fs-1 mb-2 d-block text-muted"></i>
              <h6 class="fw-bold mb-1">No items found in inventory</h6>
              <p class="text-body-secondary small mb-3">Add a new item or restore the original inventory list.</p>
              @if($isAdmin)
                <button type="button" class="btn btn-sm btn-primary rounded-pill waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                  <i class="icon-base ti tabler-plus me-1"></i> Add New Item
                </button>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="border-top bg-body">
        <tr>
          <th colspan="4" class="text-end fw-bold text-heading py-3">TOTAL ({{ $metrics['total_items_count'] ?? 31 }} Items):</th>
          <th class="text-center font-monospace fw-bold text-heading py-3" id="footTotalQty">{{ $metrics['total_quantity'] ?? 0 }}</th>
          <th class="py-3"></th>
          <th class="text-end font-monospace fw-bold text-heading py-3" id="footTotalPrice">
            <span id="footTotalPriceVal">{{ number_format(($metrics['grand_total_price'] ?? 0) * 10, 0, '.', ',') }}</span> <small class="curr-unit-symbol text-body-secondary">SP</small>
          </th>
          <th class="text-end font-monospace fw-bold text-success py-3" id="footTotalMarket">
            <span id="footTotalMarketVal">{{ number_format(($metrics['grand_total_market_value'] ?? 0) * 10, 1, '.', ',') }}</span> <small class="curr-unit-symbol text-success text-opacity-75">SP</small>
          </th>
          <th colspan="2" class="py-3"></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@if($isAdmin)
<!-- MODAL: Add New Item (Admin Only) -->
<div class="modal fade" id="modalAddItem" tabindex="-1" aria-labelledby="modalAddItemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold text-heading" id="modalAddItemLabel">
          <i class="icon-base ti tabler-plus text-primary"></i> Add New Inventory Item
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddItem" method="POST" action="{{ route('valiant-shade.pages-inventory.store') }}">
        @csrf
        <div class="modal-body py-4">
          <!-- Item Name -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="addName">Item Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="addName" name="name" required placeholder="e.g. Half Plate, Bag of Holding, Goggles of Night..." />
          </div>

          <div class="row g-3 mb-3">
            <!-- Quality / Enchantment (Charges for Wands) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="addQuality" id="addQualityLabel">Quality / Enchantment</label>
              <input type="text" class="form-control font-monospace" id="addQuality" name="quality" placeholder="e.g. +1, +2, 50 charges..." />
              <small class="text-body-secondary mt-1 d-none" id="addQualityWandHint" style="font-size: 0.74rem;">
                <i class="icon-base ti tabler-wand me-1 text-warning"></i>Charges: max 50, min 1 (0 = 0 GP)
              </small>
            </div>

            <!-- Type / Category (11 Types) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="addType">Type (Category) <span class="text-danger">*</span></label>
              <select class="form-select" id="addType" name="type" required>
                @foreach(\App\Models\InventoryItem::TYPES as $tKey => $tCfg)
                  <option value="{{ $tKey }}" {{ $tKey === 'Armor' ? 'selected' : '' }}>{{ $tKey }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <!-- Quantity -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="addQuantity">Quantity <span class="text-danger">*</span></label>
              <input type="number" class="form-control font-monospace" id="addQuantity" name="quantity" min="1" value="1" required />
            </div>

            <!-- Unit Price (Base GP) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="addUnitPrice">Unit Price (GP) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control font-monospace" id="addUnitPrice" name="unit_price" min="0" value="1000" required />
            </div>
          </div>

          <!-- Live Price Preview Box (Based on Active Currency) -->
          <div class="p-3 rounded-2 border border-primary border-opacity-25 bg-label-primary bg-opacity-10 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.82rem;">
              <span id="addPreviewTotalLabel">Total (Qty &times; Unit):</span>
              <strong class="font-monospace text-heading" id="addPreviewTotal">10,000 SP</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.82rem;">
              <span>Market Value (55%):</span>
              <strong class="font-monospace text-success" id="addPreviewEderi">5,500 SP</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between" style="font-size: 0.82rem;">
              <span>Buy Price (Per Unit):</span>
              <strong class="font-monospace text-primary" id="addPreviewAlim">5,500 SP</strong>
            </div>
            <div class="text-body-secondary border-top pt-1 mt-1 d-none font-monospace" id="addPreviewFormulaHint" style="font-size: 0.74rem;"></div>
          </div>

          <!-- Notes -->
          <div class="mb-0">
            <label class="form-label fw-semibold" for="addNotes">Notes / Rules Details</label>
            <textarea class="form-control" id="addNotes" name="notes" rows="2" placeholder="Optional rule notes, mechanical stats, or magical properties..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary waves-effect waves-light">
            <i class="icon-base ti tabler-check me-1"></i> Save Item
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: Edit Item (Admin Only) -->
<div class="modal fade" id="modalEditItem" tabindex="-1" aria-labelledby="modalEditItemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold text-heading" id="modalEditItemLabel">
          <i class="icon-base ti tabler-pencil text-warning"></i> Edit Inventory Item
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditItem" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body py-4">
          <input type="hidden" id="editItemId" />

          <!-- Item Name -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="editName">Item Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editName" name="name" required />
          </div>

          <div class="row g-3 mb-3">
            <!-- Quality / Enchantment (Charges for Wands) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="editQuality" id="editQualityLabel">Quality / Enchantment</label>
              <input type="text" class="form-control font-monospace" id="editQuality" name="quality" />
              <small class="text-body-secondary mt-1 d-none" id="editQualityWandHint" style="font-size: 0.74rem;">
                <i class="icon-base ti tabler-wand me-1 text-warning"></i>Charges: max 50, min 1 (0 = 0 GP)
              </small>
            </div>

            <!-- Type / Category (11 Types) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="editType">Type (Category) <span class="text-danger">*</span></label>
              <select class="form-select" id="editType" name="type" required>
                @foreach(\App\Models\InventoryItem::TYPES as $tKey => $tCfg)
                  <option value="{{ $tKey }}">{{ $tKey }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <!-- Quantity -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="editQuantity">Quantity <span class="text-danger">*</span></label>
              <input type="number" class="form-control font-monospace" id="editQuantity" name="quantity" min="1" required />
            </div>

            <!-- Unit Price (GP) -->
            <div class="col-6">
              <label class="form-label fw-semibold" for="editUnitPrice">Unit Price (GP) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control font-monospace" id="editUnitPrice" name="unit_price" min="0" required />
            </div>
          </div>

          <!-- Live Price Preview Box -->
          <div class="p-3 rounded-2 border border-warning border-opacity-25 bg-label-warning bg-opacity-10 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.82rem;">
              <span id="editPreviewTotalLabel">New Total:</span>
              <strong class="font-monospace text-heading" id="editPreviewTotal">0 SP</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.82rem;">
              <span>New Market Value (55%):</span>
              <strong class="font-monospace text-success" id="editPreviewEderi">0 SP</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between" style="font-size: 0.82rem;">
              <span>New Buy Price:</span>
              <strong class="font-monospace text-primary" id="editPreviewAlim">0 SP</strong>
            </div>
            <div class="text-body-secondary border-top pt-1 mt-1 d-none font-monospace" id="editPreviewFormulaHint" style="font-size: 0.74rem;"></div>
          </div>

          <!-- Notes -->
          <div class="mb-0">
            <label class="form-label fw-semibold" for="editNotes">Notes / Rules Details</label>
            <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning waves-effect waves-light">
            <i class="icon-base ti tabler-check me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Manage Guild Vault Cash (Admin Only) -->
<div class="modal fade" id="modalManageVault" tabindex="-1" aria-labelledby="modalManageVaultLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-label-warning py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2" id="modalManageVaultLabel">
          <i class="icon-base ti tabler-building-bank text-warning fs-4"></i>
          Manage Guild Vault Cash
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formManageVault" action="{{ route('valiant-shade.pages-inventory.vault') }}" method="POST">
        @csrf
        <div class="modal-body py-4">
          <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3" style="font-size: 0.85rem;">
            <i class="icon-base ti tabler-info-circle fs-5 flex-shrink-0"></i>
            <div>
              Current Guild Vault: <strong class="font-monospace text-heading">{{ number_format($vault->balance_sp ?? 30000, 2, '.', ',') }} SP</strong> (Silver)
            </div>
          </div>

          <!-- Transaction Type -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="vaultOperation">Transaction Type</label>
            <select class="form-select font-monospace" id="vaultOperation" name="operation" required>
              <option value="set">Set Direct Balance (Exact Amount)</option>
              <option value="deposit">Deposit into Vault (+ Add)</option>
              <option value="withdraw">Withdraw from Vault (- Deduct)</option>
            </select>
          </div>

          <!-- Amount (Silver / SP) -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="vaultAmountSp">Transaction Amount (<span class="text-warning">Silver / SP</span>) <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.01" class="form-control font-monospace" id="vaultAmountSp" name="amount_sp" min="0" value="{{ $vault->balance_sp ?? 30000 }}" required />
              <span class="input-group-text fw-bold">SP</span>
            </div>
            <small class="text-muted d-block mt-1" id="vaultOperationHint">Will be set as the new total balance of the guild vault.</small>
          </div>

          <!-- Notes / Description -->
          <div class="mb-0">
            <label class="form-label fw-semibold" for="vaultNotes">Transaction Note / Description</label>
            <input type="text" class="form-control" id="vaultNotes" name="notes" placeholder="e.g. Dragon hoard loot, party gear purchases..." value="{{ $vault->notes ?? '' }}" />
          </div>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning waves-effect waves-light">
            <i class="icon-base ti tabler-check me-1"></i> Update Vault
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const searchInput = document.getElementById('inventorySearchInput');
  const btnClearSearch = document.getElementById('btnClearSearch');
  const tableBody = document.getElementById('inventoryTableBody');
  const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr[data-id]')) : [];
  const categoryBtns = document.querySelectorAll('.filter-type-btn');
  const currencyBtns = document.querySelectorAll('.currency-btn');

  // Currency rates, symbols, and labels (Default: Silver / SP)
  const currencyConfig = {
    'SP': { name: 'Silver Piece (SP)', rate: 10.0, symbol: 'SP', rateText: '1 GP = 10 SP' },
    'CP': { name: 'Copper Piece (CP)', rate: 100.0, symbol: 'CP', rateText: '1 GP = 100 CP' },
    'GP': { name: 'Gold Piece (GP)', rate: 1.0, symbol: 'GP', rateText: '1 GP = 1 GP' },
    'PP': { name: 'Platinum Piece (PP)', rate: 0.1, symbol: 'PP', rateText: '10 GP = 1 PP' }
  };

  let activeCurrency = 'SP'; // Default: SILVER
  let currentCategory = 'all';
  let currentQuery = '';

  // 1. Currency Switching Function
  function switchCurrency(targetCurr) {
    if (!currencyConfig[targetCurr]) return;
    activeCurrency = targetCurr;
    const cfg = currencyConfig[targetCurr];
    const rate = cfg.rate;

    // Update segmented pill button states
    currencyBtns.forEach(btn => {
      const c = btn.getAttribute('data-currency');
      if (c === targetCurr) {
        btn.className = 'btn btn-xs rounded-pill px-3 py-1_5 currency-btn active bg-primary text-white shadow-xs font-monospace fw-semibold';
      } else {
        btn.className = 'btn btn-xs rounded-pill px-3 py-1_5 currency-btn font-monospace fw-semibold text-body-secondary';
      }
    });

    // Update info bar labels
    const lblName = document.getElementById('lblActiveCurrencyName');
    if (lblName) lblName.textContent = cfg.name;

    const lblRate = document.getElementById('lblActiveRateText');
    if (lblRate) lblRate.textContent = cfg.rateText;

    // Update metric card badges
    document.querySelectorAll('.curr-badge-label').forEach(el => el.textContent = cfg.symbol);

    // Update metric card numeric values (en-US formatting)
    const elVaultCash = document.getElementById('statVaultCash');
    let convertedCash = 30000;
    if (elVaultCash) {
      const baseSp = parseFloat(elVaultCash.getAttribute('data-base-sp')) || 30000;
      const baseGp = baseSp / 10.0;
      convertedCash = baseGp * rate;
      elVaultCash.textContent = convertedCash.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const elTotal = document.getElementById('statTotalPrice');
    if (elTotal) {
      const baseTotal = parseFloat(elTotal.getAttribute('data-base-val')) || 0;
      elTotal.textContent = (baseTotal * rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const elMarket = document.getElementById('statMarketValue');
    let convertedMarket = 0;
    if (elMarket) {
      const baseMarket = parseFloat(elMarket.getAttribute('data-base-val')) || 0;
      convertedMarket = baseMarket * rate;
      elMarket.textContent = convertedMarket.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const elTotalWealth = document.getElementById('statTotalWealth');
    if (elTotalWealth) {
      const totalWealth = convertedCash + convertedMarket;
      elTotalWealth.textContent = totalWealth.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Update table header currency labels
    document.querySelectorAll('.curr-col-label').forEach(el => el.textContent = cfg.symbol);
    document.querySelectorAll('.curr-unit-symbol').forEach(el => el.textContent = cfg.symbol);

    // Update table rows numeric values
    rows.forEach(row => {
      const baseUnit = parseFloat(row.getAttribute('data-base-unit')) || 0;
      const baseTotal = parseFloat(row.getAttribute('data-base-total')) || 0;
      const baseMarket = parseFloat(row.getAttribute('data-base-market')) || 0;
      const baseBuy = parseFloat(row.getAttribute('data-base-buy')) || 0;

      const cellUnit = row.querySelector('.cell-unit-price .cell-num');
      if (cellUnit) cellUnit.textContent = (baseUnit * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

      const cellTotal = row.querySelector('.cell-total-price .cell-num');
      if (cellTotal) cellTotal.textContent = (baseTotal * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

      const cellMarket = row.querySelector('.cell-market-value .cell-num');
      if (cellMarket) cellMarket.textContent = (baseMarket * rate).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });

      const cellBuy = row.querySelector('.cell-buy-price .cell-num');
      if (cellBuy) cellBuy.textContent = (baseBuy * rate).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
    });

    // Update table footer values
    const footTotalVal = document.getElementById('footTotalPriceVal');
    if (footTotalVal && elTotal) {
      const baseTotal = parseFloat(elTotal.getAttribute('data-base-val')) || 0;
      footTotalVal.textContent = (baseTotal * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    const footMarketVal = document.getElementById('footTotalMarketVal');
    if (footMarketVal && elMarket) {
      const baseMarket = parseFloat(elMarket.getAttribute('data-base-val')) || 0;
      footMarketVal.textContent = (baseMarket * rate).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
    }

    // Update modal calculation previews
    updateAddPreview();
    updateEditPreview();
  }

  // Currency button click events
  currencyBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const curr = this.getAttribute('data-currency');
      switchCurrency(curr);
    });
  });

  // 2. Live Category Filtering and Text Search
  function filterTable() {
    let visibleCount = 0;

    rows.forEach(row => {
      const type = row.getAttribute('data-type') || '';
      const name = row.getAttribute('data-name') || '';
      const quality = row.getAttribute('data-quality') || '';

      const matchesType = (currentCategory === 'all' || type.toLowerCase() === currentCategory.toLowerCase());
      const matchesSearch = (!currentQuery || name.includes(currentQuery) || quality.includes(currentQuery));

      if (matchesType && matchesSearch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    const emptyRow = document.getElementById('emptyTableRow');
    if (emptyRow) {
      emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
    }
  }

  // Category filter button click events
  categoryBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      categoryBtns.forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-label-secondary');
      });

      this.classList.add('active', 'btn-primary');
      this.classList.remove('btn-label-secondary');

      currentCategory = this.getAttribute('data-type');
      filterTable();
    });
  });

  // Search input typing event
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentQuery = this.value.toLowerCase().trim();
      filterTable();
    });
  }

  if (btnClearSearch && searchInput) {
    btnClearSearch.addEventListener('click', function () {
      searchInput.value = '';
      currentQuery = '';
      filterTable();
    });
  }

  // Helper: Parse Wand Charges (Max 50, Min 0. If <=0, total price is 0)
  function parseWandCharges(qualityStr) {
    if (!qualityStr || typeof qualityStr !== 'string' || qualityStr.trim() === '') {
      return 50;
    }
    const match = qualityStr.match(/\d+/);
    if (match) {
      const val = parseInt(match[0], 10);
      if (val < 0) return 0;
      if (val > 50) return 50;
      return val;
    }
    return 50;
  }

  // 3. Live Price Preview in Add Item Modal
  const addQtyInput = document.getElementById('addQuantity');
  const addPriceInput = document.getElementById('addUnitPrice');
  const addQualityInput = document.getElementById('addQuality');
  const addTypeSelect = document.getElementById('addType');
  const addQualityLabel = document.getElementById('addQualityLabel');
  const addQualityWandHint = document.getElementById('addQualityWandHint');
  const addPrevTotal = document.getElementById('addPreviewTotal');
  const addPrevTotalLabel = document.getElementById('addPreviewTotalLabel');
  const addPrevEderi = document.getElementById('addPreviewEderi');
  const addPrevAlim = document.getElementById('addPreviewAlim');
  const addPreviewFormulaHint = document.getElementById('addPreviewFormulaHint');

  function handleAddTypeChange() {
    const isWand = (addTypeSelect && addTypeSelect.value === 'Wand');
    if (addQualityLabel) {
      addQualityLabel.innerHTML = isWand ? 'Charges (1 - 50) <span class="text-warning">*</span>' : 'Quality / Enchantment';
    }
    if (addQualityInput) {
      addQualityInput.placeholder = isWand ? 'e.g. 50 (or 1 - 50 charges)' : 'e.g. +1, +2, 50 charges...';
      if (isWand && (!addQualityInput.value || addQualityInput.value.trim() === '')) {
        addQualityInput.value = '50 charges';
      }
    }
    if (addQualityWandHint) {
      if (isWand) {
        addQualityWandHint.classList.remove('d-none');
      } else {
        addQualityWandHint.classList.add('d-none');
      }
    }
    updateAddPreview();
  }

  function updateAddPreview() {
    if (!addQtyInput || !addPriceInput) return;
    const rate = currencyConfig[activeCurrency]?.rate || 10.0;
    const sym = currencyConfig[activeCurrency]?.symbol || 'SP';

    const qty = Math.max(1, parseInt(addQtyInput.value, 10) || 1);
    const basePriceGP = Math.max(0, parseFloat(addPriceInput.value) || 0);
    const isWand = (addTypeSelect && addTypeSelect.value === 'Wand');

    let totalGP = 0;
    let wandCharges = 50;

    if (isWand) {
      wandCharges = parseWandCharges(addQualityInput ? addQualityInput.value : '');
      // Wand formula: IF(F51="Wand", E51*H51/50*G51, H51*G51)
      if (wandCharges <= 0) {
        totalGP = 0;
      } else {
        totalGP = (wandCharges / 50.0) * basePriceGP * qty;
      }
      if (addPrevTotalLabel) addPrevTotalLabel.textContent = `Total (Wand ${wandCharges}/50 charges):`;
      if (addPreviewFormulaHint) {
        addPreviewFormulaHint.textContent = `Wand formula: (${wandCharges} charges / 50) × ${basePriceGP.toLocaleString('en-US')} GP × ${qty} qty = ${totalGP.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} GP`;
        addPreviewFormulaHint.classList.remove('d-none');
      }
    } else {
      totalGP = qty * basePriceGP;
      if (addPrevTotalLabel) addPrevTotalLabel.textContent = 'Total (Qty × Unit):';
      if (addPreviewFormulaHint) addPreviewFormulaHint.classList.add('d-none');
    }

    const ederiGP = totalGP * 0.55;
    const alimGP = qty > 0 ? (ederiGP / qty) : 0;

    const totalConverted = totalGP * rate;
    const ederiConverted = ederiGP * rate;
    const alimConverted = alimGP * rate;

    if (addPrevTotal) addPrevTotal.textContent = totalConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
    if (addPrevEderi) addPrevEderi.textContent = ederiConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
    if (addPrevAlim) addPrevAlim.textContent = alimConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
  }

  if (addQtyInput && addPriceInput) {
    addQtyInput.addEventListener('input', updateAddPreview);
    addPriceInput.addEventListener('input', updateAddPreview);
  }
  if (addQualityInput) {
    addQualityInput.addEventListener('input', updateAddPreview);
  }
  if (addTypeSelect) {
    addTypeSelect.addEventListener('change', handleAddTypeChange);
  }

  // 4. Live Price Preview in Edit Item Modal
  const editQtyInput = document.getElementById('editQuantity');
  const editPriceInput = document.getElementById('editUnitPrice');
  const editQualityInput = document.getElementById('editQuality');
  const editTypeSelect = document.getElementById('editType');
  const editQualityLabel = document.getElementById('editQualityLabel');
  const editQualityWandHint = document.getElementById('editQualityWandHint');
  const editPrevTotal = document.getElementById('editPreviewTotal');
  const editPrevTotalLabel = document.getElementById('editPreviewTotalLabel');
  const editPrevEderi = document.getElementById('editPreviewEderi');
  const editPrevAlim = document.getElementById('editPreviewAlim');
  const editPreviewFormulaHint = document.getElementById('editPreviewFormulaHint');

  function handleEditTypeChange() {
    const isWand = (editTypeSelect && editTypeSelect.value === 'Wand');
    if (editQualityLabel) {
      editQualityLabel.innerHTML = isWand ? 'Charges (1 - 50) <span class="text-warning">*</span>' : 'Quality / Enchantment';
    }
    if (editQualityInput) {
      editQualityInput.placeholder = isWand ? 'e.g. 50 (or 1 - 50 charges)' : 'e.g. +1, +2, 50 charges...';
      if (isWand && (!editQualityInput.value || editQualityInput.value.trim() === '')) {
        editQualityInput.value = '50 charges';
      }
    }
    if (editQualityWandHint) {
      if (isWand) {
        editQualityWandHint.classList.remove('d-none');
      } else {
        editQualityWandHint.classList.add('d-none');
      }
    }
    updateEditPreview();
  }

  function updateEditPreview() {
    if (!editQtyInput || !editPriceInput) return;
    const rate = currencyConfig[activeCurrency]?.rate || 10.0;
    const sym = currencyConfig[activeCurrency]?.symbol || 'SP';

    const qty = Math.max(1, parseInt(editQtyInput.value, 10) || 1);
    const basePriceGP = Math.max(0, parseFloat(editPriceInput.value) || 0);
    const isWand = (editTypeSelect && editTypeSelect.value === 'Wand');

    let totalGP = 0;
    let wandCharges = 50;

    if (isWand) {
      wandCharges = parseWandCharges(editQualityInput ? editQualityInput.value : '');
      if (wandCharges <= 0) {
        totalGP = 0;
      } else {
        totalGP = (wandCharges / 50.0) * basePriceGP * qty;
      }
      if (editPrevTotalLabel) editPrevTotalLabel.textContent = `New Total (Wand ${wandCharges}/50 charges):`;
      if (editPreviewFormulaHint) {
        editPreviewFormulaHint.textContent = `Wand formula: (${wandCharges} charges / 50) × ${basePriceGP.toLocaleString('en-US')} GP × ${qty} qty = ${totalGP.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} GP`;
        editPreviewFormulaHint.classList.remove('d-none');
      }
    } else {
      totalGP = qty * basePriceGP;
      if (editPrevTotalLabel) editPrevTotalLabel.textContent = 'New Total:';
      if (editPreviewFormulaHint) editPreviewFormulaHint.classList.add('d-none');
    }

    const ederiGP = totalGP * 0.55;
    const alimGP = qty > 0 ? (ederiGP / qty) : 0;

    const totalConverted = totalGP * rate;
    const ederiConverted = ederiGP * rate;
    const alimConverted = alimGP * rate;

    if (editPrevTotal) editPrevTotal.textContent = totalConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
    if (editPrevEderi) editPrevEderi.textContent = ederiConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
    if (editPrevAlim) editPrevAlim.textContent = alimConverted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
  }

  if (editQtyInput && editPriceInput) {
    editQtyInput.addEventListener('input', updateEditPreview);
    editPriceInput.addEventListener('input', updateEditPreview);
  }
  if (editQualityInput) {
    editQualityInput.addEventListener('input', updateEditPreview);
  }
  if (editTypeSelect) {
    editTypeSelect.addEventListener('change', handleEditTypeChange);
  }

  // 5. Edit Button Handler (Admin Only)
  document.querySelectorAll('.btn-edit-item').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      const quality = this.getAttribute('data-quality') || '';
      const type = this.getAttribute('data-type') || 'Armor';
      const quantity = this.getAttribute('data-quantity') || '1';
      const unitPrice = this.getAttribute('data-unit-price') || '0';
      const notes = this.getAttribute('data-notes') || '';

      const editIdEl = document.getElementById('editItemId');
      if (!editIdEl) return;

      editIdEl.value = id;
      document.getElementById('editName').value = name;
      document.getElementById('editQuality').value = quality;
      document.getElementById('editType').value = type;
      document.getElementById('editQuantity').value = quantity;
      document.getElementById('editUnitPrice').value = unitPrice;
      document.getElementById('editNotes').value = notes;

      const form = document.getElementById('formEditItem');
      if (form) {
        form.action = `{{ url('/valiant-shade/pages/inventory') }}/${id}`;
      }

      handleEditTypeChange();

      const modalEl = document.getElementById('modalEditItem');
      if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      }
    });
  });

  // 6. Delete Button Handler (Admin Only)
  document.querySelectorAll('.btn-delete-item').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');

      if (!confirm(`Are you sure you want to delete "${name}" from the inventory?`)) {
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `{{ url('/valiant-shade/pages/inventory') }}/${id}`;

      const tokenInput = document.createElement('input');
      tokenInput.type = 'hidden';
      tokenInput.name = '_token';
      tokenInput.value = csrfToken;

      const methodInput = document.createElement('input');
      methodInput.type = 'hidden';
      methodInput.name = '_method';
      methodInput.value = 'DELETE';

      form.appendChild(tokenInput);
      form.appendChild(methodInput);
      document.body.appendChild(form);
      form.submit();
    });
  });

  // 7. Reset to Defaults (Admin Only)
  const btnReset = document.getElementById('btnResetDefaults');
  if (btnReset) {
    btnReset.addEventListener('click', function () {
      if (!confirm('All inventory items will be reset to the original 31 dataset. Do you confirm?')) {
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `{{ route('valiant-shade.pages-inventory.reset-defaults') }}`;

      const tokenInput = document.createElement('input');
      tokenInput.type = 'hidden';
      tokenInput.name = '_token';
      tokenInput.value = csrfToken;

      form.appendChild(tokenInput);
      document.body.appendChild(form);
      form.submit();
    });
  }

  // 8. CSV Export
  const btnExport = document.getElementById('btnExportCsv');
  if (btnExport) {
    btnExport.addEventListener('click', function () {
      const sym = currencyConfig[activeCurrency]?.symbol || 'SP';
      const exportRows = [
        ['#', 'Item Name', 'Quality', 'Type', 'Qty', `Unit Price (${sym})`, `Total (${sym})`, `Market Value (55% ${sym})`, `Buy Price (${sym})`]
      ];

      rows.forEach((r, idx) => {
        if (r.style.display !== 'none') {
          const cols = Array.from(r.querySelectorAll('td')).map(td => td.innerText.replace(/\n/g, ' ').trim());
          if (cols.length >= 9) {
            exportRows.push([
              idx + 1,
              `"${cols[1].replace(/"/g, '""')}"`,
              `"${cols[2]}"`,
              `"${cols[3]}"`,
              cols[4],
              cols[5].replace(` ${sym}`, ''),
              cols[6].replace(` ${sym}`, ''),
              cols[7].replace(` ${sym}`, ''),
              cols[8].replace(` ${sym}`, '')
            ]);
          }
        }
      });

      const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + exportRows.map(e => e.join(';')).join('\n');
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `valiant_shade_guild_inventory_${activeCurrency}_${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  // 9. Guild Vault Operation Hint Change
  const vaultOpSelect = document.getElementById('vaultOperation');
  const vaultHint = document.getElementById('vaultOperationHint');
  if (vaultOpSelect && vaultHint) {
    vaultOpSelect.addEventListener('change', function () {
      if (this.value === 'deposit') {
        vaultHint.textContent = 'The entered SP amount will be deposited into the guild vault (+).';
      } else if (this.value === 'withdraw') {
        vaultHint.textContent = 'The entered SP amount will be withdrawn from the guild vault (-).';
      } else {
        vaultHint.textContent = 'Will be set as the new total balance of the guild vault.';
      }
    });
  }

  // Initialize with Silver (SP) on page load
  switchCurrency('SP');
});
</script>
@endsection
