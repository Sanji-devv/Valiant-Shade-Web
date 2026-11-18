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

    @if(!$isAdmin)
      <span class="badge bg-label-secondary rounded-pill d-flex align-items-center gap-1 py-2 px-3 shadow-xs" title="Inventory management permissions are required to edit items.">
        <i class="icon-base ti tabler-lock fs-6 text-warning"></i>
        <span>{{ __('Read Only (Inventory Access Required)') }}</span>
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
            <span class="text-heading fw-semibold">{{ __('Guild Vault (Cash)') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace" id="statVaultCash" data-base-sp="{{ $metrics['vault_cash_sp'] ?? 30000 }}">
                {{ number_format($metrics['vault_cash_converted'] ?? 30000, 2, '.', ',') }}
              </h3>
              <span class="badge bg-label-warning font-monospace curr-badge-label">SP</span>
              @if($canUpdate)
                <div class="d-inline-flex align-items-center gap-1 ms-1">
                  <button type="button" class="btn btn-icon btn-xs btn-label-success rounded-circle shadow-xs btn-vault-trigger" data-vault-tab="deposit" title="{{ __('Deposit Cash (+)') }}" aria-label="{{ __('Deposit Cash (+)') }}">
                    <i class="icon-base ti tabler-plus"></i>
                  </button>
                  <button type="button" class="btn btn-icon btn-xs btn-label-danger rounded-circle shadow-xs btn-vault-trigger" data-vault-tab="withdraw" title="{{ __('Withdraw Cash (-)') }}" aria-label="{{ __('Withdraw Cash (-)') }}">
                    <i class="icon-base ti tabler-minus"></i>
                  </button>
                </div>
              @endif
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ti tabler-building-bank icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top flex-wrap gap-2">
          <small class="text-body-secondary">{{ __('Physical Cash Vault (30k Silver)') }}</small>
          @if($canUpdate)
            <div class="d-inline-flex align-items-center gap-1">
              <button type="button" class="btn btn-xs btn-label-success rounded-pill px-2 py-0_5 shadow-xs btn-vault-trigger" data-vault-tab="deposit" title="{{ __('Deposit Cash (+)') }}">
                <i class="icon-base ti tabler-plus icon-xs me-1"></i> {{ __('Deposit') }}
              </button>
              <button type="button" class="btn btn-xs btn-label-danger rounded-pill px-2 py-0_5 shadow-xs btn-vault-trigger" data-vault-tab="withdraw" title="{{ __('Withdraw Cash (-)') }}">
                <i class="icon-base ti tabler-minus icon-xs me-1"></i> {{ __('Withdraw') }}
              </button>
              <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2 py-0_5 shadow-xs btn-vault-trigger" data-vault-tab="transfer" title="{{ __('Player Character Transfer') }}">
                <i class="icon-base ti tabler-arrows-exchange icon-xs me-1"></i> {{ __('Transfer') }}
              </button>
            </div>
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

      <div class="d-flex flex-nowrap align-items-center gap-2 ms-auto">
        @if($canCreate)
          <button type="button" class="btn btn-success btn-sm rounded-pill waves-effect shadow-xs" id="btnAddItems" data-bs-toggle="modal" data-bs-target="#modalAddItems" title="{{ __('Add items') }}" aria-label="{{ __('Add items') }}">
            <i class="icon-base ti tabler-plus"></i>
          </button>
        @endif
        @if($canDelete)
          <button type="button" class="btn btn-success btn-sm rounded-pill waves-effect shadow-xs" id="btnSellSelected" title="{{ __('Sell selected items') }} (50%)" aria-label="{{ __('Sell selected items') }}" disabled>
            <i class="icon-base ti tabler-coins"></i>
          </button>
          <button type="button" class="btn btn-danger btn-sm rounded-pill waves-effect shadow-xs" id="btnDeleteSelected" title="{{ __('Delete selected items') }}" aria-label="{{ __('Delete selected items') }}" disabled>
            <i class="icon-base ti tabler-minus"></i>
          </button>
          <span class="visually-hidden" id="selectedItemCount">0</span>
        @endif

        <!-- Search Input -->
        <div class="input-group input-group-merge shadow-xs" style="max-width: 320px;">
          <span class="input-group-text"><i class="icon-base ti tabler-search text-body-secondary"></i></span>
          <input type="text" id="inventorySearchInput" class="form-control form-control-sm" placeholder="Search item name, quality or note..." />
          <button class="btn btn-outline-secondary btn-sm" type="button" id="btnClearSearch" title="Clear">
            <i class="icon-base ti tabler-x"></i>
          </button>
        </div>
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
          @if($canDelete)
            <th style="width: 42px;" class="text-center"><input type="checkbox" class="form-check-input" id="selectAllItems" aria-label="Select all items"></th>
          @endif
          <th style="width: 50px;" class="text-center">#</th>
          <th>Inventory (Item Name)</th>
          <th style="width: 100px;" class="text-center">Quality</th>
          <th style="width: 130px;" class="text-center">Type</th>
          <th style="width: 70px;" class="text-center">Qty</th>
          <th style="width: 130px;" class="text-end col-header-unit">Unit (<span class="curr-col-label">SP</span>)</th>
          <th style="width: 140px;" class="text-end col-header-total">Total (<span class="curr-col-label">SP</span>)</th>
          <th style="width: 130px;" class="text-end col-header-buy">Buy (55% / unit <span class="curr-col-label">SP</span>)</th>
          <th style="width: 130px;" class="text-end col-header-sell">Sell (50% / unit <span class="curr-col-label">SP</span>)</th>
          @if($canUpdate)
            <th style="width: 90px;" class="text-center">Actions</th>
          @endif
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
              data-base-buy="{{ $item->buy_price }}"
              data-base-sell="{{ $item->sell_price }}">
            @if($canDelete)
              <td class="text-center"><input type="checkbox" class="form-check-input item-select-checkbox" value="{{ $item->id }}" aria-label="Select {{ $item->name }}"></td>
            @endif
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
            <td class="text-end font-monospace text-body cell-buy-price">
              <span class="cell-num">{{ number_format($item->buy_price * 10, 1, '.', ',') }}</span> <small class="text-body-secondary curr-unit-symbol">SP</small>
            </td>
            <td class="text-end font-monospace text-body cell-sell-price">
              <span class="cell-num">{{ number_format($item->sell_price * 10, 1, '.', ',') }}</span> <small class="text-body-secondary curr-unit-symbol">SP</small>
            </td>
            @if($canUpdate)
            <td class="text-center">
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
              </div>
            </td>
            @endif
          </tr>
        @empty
          <tr id="emptyTableRow">
            <td colspan="{{ 9 + ($canDelete ? 1 : 0) + ($canUpdate ? 1 : 0) }}" class="text-center py-5 text-body-secondary">
              <i class="icon-base ti tabler-package-off fs-1 mb-2 d-block text-muted"></i>
              <h6 class="fw-bold mb-1">No items found in inventory</h6>
              <p class="text-body-secondary small mb-3">Add items to the inventory.</p>
              @if($canCreate)
                <button type="button" class="btn btn-sm btn-success rounded-pill waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalAddItems">
                Add Item
                </button>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="border-top bg-body">
        <tr>
          <th colspan="{{ 4 + ($canDelete ? 1 : 0) }}" class="text-end fw-bold text-heading py-3">TOTAL ({{ $metrics['total_items_count'] ?? 31 }} Items):</th>
          <th class="text-center font-monospace fw-bold text-heading py-3" id="footTotalQty">{{ $metrics['total_quantity'] ?? 0 }}</th>
          <th class="py-3"></th>
          <th class="text-end font-monospace fw-bold text-heading py-3" id="footTotalPrice">
            <span id="footTotalPriceVal">{{ number_format(($metrics['grand_total_price'] ?? 0) * 10, 0, '.', ',') }}</span> <small class="curr-unit-symbol text-body-secondary">SP</small>
          </th>
          <th class="py-3"></th>
          <th class="py-3"></th>
          @if($canUpdate)
            <th class="py-3"></th>
          @endif
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@if($canCreate)
<!-- MODAL: Add Items -->
<div class="modal fade" id="modalAddItems" tabindex="-1" aria-labelledby="modalAddItemsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold text-heading" id="modalAddItemsLabel">
          Add Inventory Items
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('valiant-shade.pages-inventory.store') }}" id="formAddItems">
        @csrf
        <div class="modal-body">
          <p class="text-body-secondary small">Add as many items as needed. Prices are entered in GP; totals and 55% buy / 50% sell prices are calculated automatically.</p>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Item Name</th><th>Quality</th><th>Type</th><th>Qty</th><th>Unit Price (GP)</th><th>Total (GP)</th><th>Buy 55% / unit</th><th>Sell 50% / unit</th><th></th>
                </tr>
              </thead>
              <tbody id="itemRows"></tbody>
              <template id="itemRowTemplate">
                <tr class="item-row">
                  <td><input type="text" class="form-control form-control-sm" data-field="name" maxlength="255" required></td>
                  <td><input type="text" class="form-control form-control-sm" data-field="quality" maxlength="50" placeholder="Optional"></td>
                  <td>
                    <select class="form-select form-select-sm" data-field="type" required>
                      @foreach(\App\Models\InventoryItem::TYPES as $tKey => $tCfg)
                        <option value="{{ $tKey }}" {{ $tKey === 'Armor' ? 'selected' : '' }}>{{ $tKey }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="number" class="form-control form-control-sm" data-field="quantity" min="1" value="1" required></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm" data-field="unit_price" min="0" value="0" required></td>
                  <td class="text-end font-monospace" data-preview="total">0.00</td>
                  <td class="text-end font-monospace" data-preview="buy">0.00</td>
                  <td class="text-end font-monospace" data-preview="sell">0.00</td>
                  <td><button type="button" class="btn btn-icon btn-text-danger btn-sm remove-item-row" title="Remove row"><i class="icon-base ti tabler-trash"></i></button></td>
                </tr>
              </template>
            </table>
          </div>
          <button type="button" class="btn btn-sm btn-label-primary" id="btnAddItemRow"><i class="icon-base ti tabler-plus me-1"></i>Add another item</button>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-check me-1"></i>Add all items</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@if($canUpdate)
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
              <span>Buy Price (55% / unit):</span>
              <strong class="font-monospace text-primary" id="editPreviewAlim">0 SP</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between" style="font-size: 0.82rem;">
              <span>Sell Price (50% / unit):</span>
              <strong class="font-monospace text-info" id="editPreviewSell">0 SP</strong>
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
@endif

@if($canUpdate)
<!-- Modal: Advanced Guild Vault & Cash Management (Admin Only) -->
<div class="modal fade" id="modalManageVault" tabindex="-1" aria-labelledby="modalManageVaultLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <!-- Modal Header -->
      <div class="modal-header bg-label-warning py-3">
        <div class="d-flex align-items-center gap-2">
          <span class="avatar avatar-sm rounded-3 bg-warning text-white d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-building-bank fs-4"></i>
          </span>
          <div>
            <h5 class="modal-title fw-bold text-heading mb-0" id="modalManageVaultLabel">
              {{ __('Guild Vault & Cash Management') }}
            </h5>
            <small class="text-body-secondary">{{ __('Physical cash reserves, player character transfers, and treasury transactions') }}</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4">
        <!-- Live Vault Balance Summary Banner -->
        <div class="card bg-label-secondary border-0 mb-4 shadow-none">
          <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm rounded-circle bg-warning text-white d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-vault fs-5"></i>
                </span>
                <div>
                  <small class="text-body-secondary text-uppercase fw-semibold" style="font-size: 0.75rem;">{{ __('Current Vault Liquid Reserves') }}</small>
                  <h4 class="mb-0 fw-bold font-monospace text-heading" id="modalVaultCurrentSp" data-base-sp="{{ $vault->balance_sp ?? 30000 }}">
                    {{ number_format($vault->balance_sp ?? 30000, 2, '.', ',') }} <span class="badge bg-warning text-dark font-monospace">SP</span>
                  </h4>
                </div>
              </div>

              <!-- Multi-Currency Breakdown Pills -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-body text-heading font-monospace border px-2_5 py-1_5 shadow-xs" title="Platinum (10 GP = 1 PP)">
                  <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #00cfe8;"></span><span id="modalVaultPpVal">{{ number_format(($vault->balance_sp ?? 30000) / 100, 2) }}</span> PP
                </span>
                <span class="badge bg-body text-heading font-monospace border px-2_5 py-1_5 shadow-xs" title="Gold (1 GP = 10 SP)">
                  <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #fbbf24;"></span><span id="modalVaultGpVal">{{ number_format(($vault->balance_sp ?? 30000) / 10, 2) }}</span> GP
                </span>
                <span class="badge bg-body text-heading font-monospace border px-2_5 py-1_5 shadow-xs" title="Silver (Base Standard)">
                  <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #cbd5e1;"></span><span id="modalVaultSpVal">{{ number_format($vault->balance_sp ?? 30000, 2) }}</span> SP
                </span>
                <span class="badge bg-body text-heading font-monospace border px-2_5 py-1_5 shadow-xs" title="Copper (1 SP = 10 CP)">
                  <span class="rounded-circle d-inline-block me-1" style="width: 7px; height: 7px; background: #ff9f43;"></span><span id="modalVaultCpVal">{{ number_format(($vault->balance_sp ?? 30000) * 10, 0) }}</span> CP
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills nav-fill mb-3 gap-1" id="vaultModalTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active py-2 d-flex align-items-center justify-content-center gap-1_5" id="tab-btn-deposit" data-bs-toggle="pill" data-bs-target="#pane-vault-deposit" type="button" role="tab" aria-controls="pane-vault-deposit" aria-selected="true">
              <i class="icon-base ti tabler-plus fs-5 text-success"></i>
              <span class="fw-semibold">{{ __('Deposit (+)') }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 d-flex align-items-center justify-content-center gap-1_5" id="tab-btn-withdraw" data-bs-toggle="pill" data-bs-target="#pane-vault-withdraw" type="button" role="tab" aria-controls="pane-vault-withdraw" aria-selected="false">
              <i class="icon-base ti tabler-minus fs-5 text-danger"></i>
              <span class="fw-semibold">{{ __('Withdraw (-)') }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 d-flex align-items-center justify-content-center gap-1_5" id="tab-btn-transfer" data-bs-toggle="pill" data-bs-target="#pane-vault-transfer" type="button" role="tab" aria-controls="pane-vault-transfer" aria-selected="false">
              <i class="icon-base ti tabler-arrows-exchange fs-5 text-primary"></i>
              <span class="fw-semibold">{{ __('Character Transfer') }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 d-flex align-items-center justify-content-center gap-1_5" id="tab-btn-set" data-bs-toggle="pill" data-bs-target="#pane-vault-set" type="button" role="tab" aria-controls="pane-vault-set" aria-selected="false">
              <i class="icon-base ti tabler-adjustments-horizontal fs-5 text-warning"></i>
              <span class="fw-semibold">{{ __('Set Balance (=)') }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-2 d-flex align-items-center justify-content-center gap-1_5" id="tab-btn-history" data-bs-toggle="pill" data-bs-target="#pane-vault-history" type="button" role="tab" aria-controls="pane-vault-history" aria-selected="false">
              <i class="icon-base ti tabler-history fs-5 text-info"></i>
              <span class="fw-semibold">{{ __('History') }}</span>
            </button>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content border rounded p-3 bg-body" id="vaultModalTabContent">
          <!-- 1. DEPOSIT TAB -->
          <div class="tab-pane fade show active" id="pane-vault-deposit" role="tabpanel" aria-labelledby="tab-btn-deposit">
            <form id="formVaultDeposit" action="{{ route('valiant-shade.pages-inventory.vault') }}" method="POST">
              @csrf
              <input type="hidden" name="operation" value="deposit" />

              <div class="mb-3">
                <label class="form-label fw-semibold d-flex justify-content-between align-items-center" for="depositAmount">
                  <span>{{ __('Deposit Amount') }} <span class="text-danger">*</span></span>
                  <small class="text-body-secondary">{{ __('Quick Presets') }}</small>
                </label>
                <!-- Quick Amount Presets -->
                <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                  <button type="button" class="btn btn-xs btn-label-success rounded-pill preset-deposit-btn" data-amount="100" data-curr="SP">+100 SP</button>
                  <button type="button" class="btn btn-xs btn-label-success rounded-pill preset-deposit-btn" data-amount="500" data-curr="SP">+500 SP</button>
                  <button type="button" class="btn btn-xs btn-label-success rounded-pill preset-deposit-btn" data-amount="1000" data-curr="SP">+1,000 SP</button>
                  <button type="button" class="btn btn-xs btn-label-success rounded-pill preset-deposit-btn" data-amount="5000" data-curr="SP">+5,000 SP</button>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill preset-deposit-btn" data-amount="100" data-curr="GP">+100 GP</button>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill preset-deposit-btn" data-amount="500" data-curr="GP">+500 GP</button>
                </div>
                <!-- Amount & Currency Input -->
                <div class="input-group">
                  <input type="number" step="0.01" min="0.01" class="form-control font-monospace" id="depositAmount" name="amount" placeholder="0.00" required />
                  <select class="form-select fw-bold font-monospace" id="depositCurrency" name="currency" style="max-width: 100px;">
                    <option value="SP" selected>SP</option>
                    <option value="GP">GP</option>
                    <option value="CP">CP</option>
                    <option value="PP">PP</option>
                  </select>
                </div>
              </div>

              <!-- Deposit Live Preview -->
              <div class="alert alert-label-success d-flex align-items-center justify-content-between py-2 px-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-arrow-up-right fs-4 text-success"></i>
                  <div>
                    <span class="small d-block text-body-secondary">{{ __('Calculated Addition') }}:</span>
                    <strong class="font-monospace text-success fs-6" id="previewDepositSp">+0.00 SP</strong>
                  </div>
                </div>
                <div class="text-end">
                  <span class="small d-block text-body-secondary">{{ __('New Vault Balance') }}:</span>
                  <strong class="font-monospace text-heading fs-6" id="previewDepositNewVault">{{ number_format($vault->balance_sp ?? 30000, 2) }} SP</strong>
                </div>
              </div>

              <!-- Notes / Description -->
              <div class="mb-3">
                <label class="form-label fw-semibold" for="depositNotes">{{ __('Transaction Note / Reason') }}</label>
                <input type="text" class="form-control" id="depositNotes" name="notes" placeholder="{{ __('e.g. Dungeon treasure share, guild quest rewards, monster bounty...') }}" />
              </div>

              <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-success waves-effect waves-light">
                  <i class="icon-base ti tabler-plus me-1"></i> {{ __('Confirm Deposit (+)') }}
                </button>
              </div>
            </form>
          </div>

          <!-- 2. WITHDRAW TAB -->
          <div class="tab-pane fade" id="pane-vault-withdraw" role="tabpanel" aria-labelledby="tab-btn-withdraw">
            <form id="formVaultWithdraw" action="{{ route('valiant-shade.pages-inventory.vault') }}" method="POST">
              @csrf
              <input type="hidden" name="operation" value="withdraw" />

              <div class="mb-3">
                <label class="form-label fw-semibold d-flex justify-content-between align-items-center" for="withdrawAmount">
                  <span>{{ __('Withdrawal Amount') }} <span class="text-danger">*</span></span>
                  <small class="text-body-secondary">{{ __('Quick Presets') }}</small>
                </label>
                <!-- Quick Amount Presets -->
                <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                  <button type="button" class="btn btn-xs btn-label-danger rounded-pill preset-withdraw-btn" data-amount="100" data-curr="SP">-100 SP</button>
                  <button type="button" class="btn btn-xs btn-label-danger rounded-pill preset-withdraw-btn" data-amount="500" data-curr="SP">-500 SP</button>
                  <button type="button" class="btn btn-xs btn-label-danger rounded-pill preset-withdraw-btn" data-amount="1000" data-curr="SP">-1,000 SP</button>
                  <button type="button" class="btn btn-xs btn-label-danger rounded-pill preset-withdraw-btn" data-amount="5000" data-curr="SP">-5,000 SP</button>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill preset-withdraw-btn" data-amount="100" data-curr="GP">-100 GP</button>
                  <button type="button" class="btn btn-xs btn-label-secondary rounded-pill preset-withdraw-percent-btn" data-percent="25">25%</button>
                  <button type="button" class="btn btn-xs btn-label-secondary rounded-pill preset-withdraw-percent-btn" data-percent="50">50%</button>
                </div>
                <!-- Amount & Currency Input -->
                <div class="input-group">
                  <input type="number" step="0.01" min="0.01" class="form-control font-monospace" id="withdrawAmount" name="amount" placeholder="0.00" required />
                  <select class="form-select fw-bold font-monospace" id="withdrawCurrency" name="currency" style="max-width: 100px;">
                    <option value="SP" selected>SP</option>
                    <option value="GP">GP</option>
                    <option value="CP">CP</option>
                    <option value="PP">PP</option>
                  </select>
                </div>
              </div>

              <!-- Withdraw Live Preview -->
              <div class="alert alert-label-danger d-flex align-items-center justify-content-between py-2 px-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-arrow-down-right fs-4 text-danger"></i>
                  <div>
                    <span class="small d-block text-body-secondary">{{ __('Calculated Deduction') }}:</span>
                    <strong class="font-monospace text-danger fs-6" id="previewWithdrawSp">-0.00 SP</strong>
                  </div>
                </div>
                <div class="text-end">
                  <span class="small d-block text-body-secondary">{{ __('Remaining Vault Balance') }}:</span>
                  <strong class="font-monospace text-heading fs-6" id="previewWithdrawRemainingVault">{{ number_format($vault->balance_sp ?? 30000, 2) }} SP</strong>
                </div>
              </div>

              <div class="alert alert-danger d-none py-2 px-3 mb-3" id="alertWithdrawInsufficient">
                <i class="icon-base ti tabler-alert-triangle me-1"></i> {{ __('Insufficient vault funds for this withdrawal amount!') }}
              </div>

              <!-- Notes / Description -->
              <div class="mb-3">
                <label class="form-label fw-semibold" for="withdrawNotes">{{ __('Transaction Note / Reason') }}</label>
                <input type="text" class="form-control" id="withdrawNotes" name="notes" placeholder="{{ __('e.g. Guild supplies, gear repair, expedition expenses...') }}" />
              </div>

              <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-danger waves-effect waves-light" id="btnSubmitWithdraw">
                  <i class="icon-base ti tabler-minus me-1"></i> {{ __('Confirm Withdrawal (-)') }}
                </button>
              </div>
            </form>
          </div>

          <!-- 3. CHARACTER TRANSFER TAB -->
          <div class="tab-pane fade" id="pane-vault-transfer" role="tabpanel" aria-labelledby="tab-btn-transfer">
            <form id="formVaultTransfer" action="{{ route('valiant-shade.pages-inventory.vault') }}" method="POST">
              @csrf
              <input type="hidden" id="transferOperationInput" name="operation" value="transfer_to_character" />

              <!-- Transfer Direction Segmented Toggle -->
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Transfer Direction') }}</label>
                <div class="btn-group w-100 shadow-xs" role="group" aria-label="Transfer Direction">
                  <input type="radio" class="btn-check" name="transferDirectionRadio" id="dirVaultToChar" value="transfer_to_character" checked autocomplete="off">
                  <label class="btn btn-outline-primary btn-sm py-2" for="dirVaultToChar">
                    <i class="icon-base ti tabler-arrow-down-left me-1"></i> {{ __('Vault → Character') }}
                  </label>

                  <input type="radio" class="btn-check" name="transferDirectionRadio" id="dirCharToVault" value="transfer_from_character" autocomplete="off">
                  <label class="btn btn-outline-primary btn-sm py-2" for="dirCharToVault">
                    <i class="icon-base ti tabler-arrow-up-right me-1"></i> {{ __('Character → Vault') }}
                  </label>

                  <input type="radio" class="btn-check" name="transferDirectionRadio" id="dirDistributeAll" value="distribute_characters" autocomplete="off">
                  <label class="btn btn-outline-primary btn-sm py-2" for="dirDistributeAll">
                    <i class="icon-base ti tabler-users-group me-1"></i> {{ __('Split to All Players') }}
                  </label>
                </div>
              </div>

              <!-- Character Select Container (Single character modes) -->
              <div class="mb-3" id="containerSelectCharacter">
                <label class="form-label fw-semibold" for="transferCharacterSelect">{{ __('Select Player Character') }} <span class="text-danger">*</span></label>
                <select class="form-select font-monospace" id="transferCharacterSelect" name="character_id">
                  @forelse($characters ?? [] as $char)
                    <option value="{{ $char->id }}" data-balance-sp="{{ $char->allowance_sp ?? 0 }}" data-name="{{ $char->name }}" data-level="{{ $char->current_level }}" data-player="{{ $char->user?->name ?? 'Unassigned' }}">
                      {{ $char->name }} (Lv.{{ $char->current_level }}) — {{ $char->user?->name ?? 'No Player' }} [{{ __('Balance') }}: {{ number_format($char->allowance_sp ?? 0, 2) }} SP]
                    </option>
                  @empty
                    <option value="" disabled>{{ __('No active player characters found') }}</option>
                  @endforelse
                </select>
              </div>

              <!-- Dynamic Character Info & Projected Balance Box -->
              <div class="card bg-label-primary border-0 mb-3 shadow-none" id="cardCharacterTransferPreview">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                      <span class="avatar avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                        <i class="icon-base ti tabler-user fs-6"></i>
                      </span>
                      <div>
                        <strong class="text-heading" id="prevCharName">Character</strong>
                        <small class="text-body-secondary ms-1">(<span id="prevCharLevel">Lv. 1</span> &bull; <span id="prevCharPlayer">Player</span>)</small>
                      </div>
                    </div>
                    <div class="font-monospace">
                      <small class="text-body-secondary">{{ __('Current Allowance') }}:</small>
                      <strong class="text-heading ms-1" id="prevCharCurrentBalance">0.00 SP</strong>
                    </div>
                  </div>

                  <div class="row g-2 text-center">
                    <div class="col-6">
                      <div class="p-2 rounded bg-body">
                        <small class="text-body-secondary d-block">{{ __('Character Balance After') }}</small>
                        <strong class="font-monospace text-primary fs-6" id="prevCharProjectedBalance">0.00 SP</strong>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="p-2 rounded bg-body">
                        <small class="text-body-secondary d-block">{{ __('Guild Vault Balance After') }}</small>
                        <strong class="font-monospace text-heading fs-6" id="prevVaultProjectedBalance">{{ number_format($vault->balance_sp ?? 30000, 2) }} SP</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Distribute Evenly Info Box (Hidden unless Split to All Players chosen) -->
              <div class="alert alert-label-info d-none mb-3 py-2 px-3" id="cardDistributeInfo">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="icon-base ti tabler-users-group fs-5 text-info"></i>
                  <span class="fw-semibold">{{ __('Equal Distribution Mode') }}</span>
                </div>
                <div class="small">
                  {{ __('Active characters') }}: <strong id="distributeCharCount">{{ count($characters ?? []) }}</strong> {{ __('heroes') }} &bull;
                  {{ __('Per-character share') }}: <strong class="font-monospace text-info fs-6" id="distributeSharePreview">0.00 SP</strong>
                </div>
              </div>

              <!-- Amount & Currency Input -->
              <div class="mb-3">
                <label class="form-label fw-semibold d-flex justify-content-between align-items-center" for="transferAmount">
                  <span>{{ __('Transfer Amount') }} <span class="text-danger">*</span></span>
                  <small class="text-body-secondary">{{ __('Quick Presets') }}</small>
                </label>
                <!-- Quick Amount Presets -->
                <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                  <button type="button" class="btn btn-xs btn-label-primary rounded-pill preset-transfer-btn" data-amount="50" data-curr="SP">50 SP</button>
                  <button type="button" class="btn btn-xs btn-label-primary rounded-pill preset-transfer-btn" data-amount="100" data-curr="SP">100 SP</button>
                  <button type="button" class="btn btn-xs btn-label-primary rounded-pill preset-transfer-btn" data-amount="250" data-curr="SP">250 SP</button>
                  <button type="button" class="btn btn-xs btn-label-primary rounded-pill preset-transfer-btn" data-amount="500" data-curr="SP">500 SP</button>
                  <button type="button" class="btn btn-xs btn-label-primary rounded-pill preset-transfer-btn" data-amount="1000" data-curr="SP">1,000 SP</button>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill preset-transfer-btn" data-amount="50" data-curr="GP">50 GP</button>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill preset-transfer-btn" data-amount="100" data-curr="GP">100 GP</button>
                </div>
                <div class="input-group">
                  <input type="number" step="0.01" min="0.01" class="form-control font-monospace" id="transferAmount" name="amount" placeholder="0.00" required />
                  <select class="form-select fw-bold font-monospace" id="transferCurrency" name="currency" style="max-width: 100px;">
                    <option value="SP" selected>SP</option>
                    <option value="GP">GP</option>
                    <option value="CP">CP</option>
                    <option value="PP">PP</option>
                  </select>
                </div>
              </div>

              <!-- Transfer Error Alert -->
              <div class="alert alert-danger d-none py-2 px-3 mb-3" id="alertTransferInsufficient">
                <i class="icon-base ti tabler-alert-triangle me-1"></i> <span id="transferInsufficientMsg">{{ __('Insufficient funds for this transfer!') }}</span>
              </div>

              <!-- Notes / Description -->
              <div class="mb-3">
                <label class="form-label fw-semibold" for="transferNotes">{{ __('Transaction Note / Reason') }}</label>
                <input type="text" class="form-control" id="transferNotes" name="notes" placeholder="{{ __('e.g. Quest reward split, personal allowance, equipment reimbursement...') }}" />
              </div>

              <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary waves-effect waves-light" id="btnSubmitTransfer">
                  <i class="icon-base ti tabler-arrows-exchange me-1"></i> <span id="btnTransferLabel">{{ __('Execute Transfer') }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- 4. SET BALANCE TAB -->
          <div class="tab-pane fade" id="pane-vault-set" role="tabpanel" aria-labelledby="tab-btn-set">
            <form id="formVaultSet" action="{{ route('valiant-shade.pages-inventory.vault') }}" method="POST">
              @csrf
              <input type="hidden" name="operation" value="set" />

              <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3">
                <i class="icon-base ti tabler-alert-triangle fs-5 flex-shrink-0 text-warning"></i>
                <div class="small">
                  {{ __('Direct Balance Adjustment sets the total liquid reserve in the vault directly. Use for audit corrections.') }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold" for="setAmount">{{ __('New Exact Vault Balance') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="number" step="0.01" min="0" class="form-control font-monospace" id="setAmount" name="amount" value="{{ $vault->balance_sp ?? 30000 }}" required />
                  <select class="form-select fw-bold font-monospace" id="setCurrency" name="currency" style="max-width: 100px;">
                    <option value="SP" selected>SP</option>
                    <option value="GP">GP</option>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold" for="setNotes">{{ __('Audit Note / Reason') }}</label>
                <input type="text" class="form-control" id="setNotes" name="notes" placeholder="{{ __('e.g. Master sheet balance audit, treasury correction...') }}" value="{{ $vault->notes ?? '' }}" />
              </div>

              <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-warning waves-effect waves-light">
                  <i class="icon-base ti tabler-check me-1"></i> {{ __('Set Direct Balance') }}
                </button>
              </div>
            </form>
          </div>

          <!-- 5. HISTORY / AUDIT LOG TAB -->
          <div class="tab-pane fade" id="pane-vault-history" role="tabpanel" aria-labelledby="tab-btn-history">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 fw-semibold text-heading">{{ __('Recent Vault & Character Transactions') }}</h6>
              <span class="badge bg-label-info">{{ __('Last 10 Records') }}</span>
            </div>

            <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
              <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top">
                  <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Character / Entity') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th class="text-end">{{ __('Balance After') }}</th>
                    <th>{{ __('Description') }}</th>
                  </tr>
                </thead>
                <tbody class="font-monospace">
                  @forelse($recentTransactions ?? [] as $tx)
                    @php
                      $isCredit = (float) $tx->amount_sp > 0;
                      $badgeClass = match($tx->transaction_type) {
                        'DEPOSIT' => 'bg-label-success',
                        'WITHDRAWAL' => 'bg-label-danger',
                        'QUEST_SHARE' => 'bg-label-primary',
                        default => 'bg-label-secondary'
                      };
                    @endphp
                    <tr>
                      <td class="text-body-secondary" style="font-size: 0.78rem;">
                        {{ $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-' }}
                      </td>
                      <td>
                        <span class="badge {{ $badgeClass }} py-0_5 px-1_5" style="font-size: 0.7rem;">
                          {{ $tx->transaction_type }}
                        </span>
                      </td>
                      <td class="text-heading fw-semibold">
                        {{ $tx->character?->name ?? __('Guild Reserve') }}
                      </td>
                      <td class="text-end fw-bold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                        {{ $isCredit ? '+' : '' }}{{ number_format($tx->amount_sp, 2) }} SP
                      </td>
                      <td class="text-end text-heading">
                        {{ number_format($tx->balance_after_sp, 2) }} SP
                      </td>
                      <td class="text-truncate text-body-secondary font-sans-serif" style="max-width: 180px;" title="{{ $tx->description }}">
                        {{ $tx->description ?: '-' }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center py-4 text-muted font-sans-serif">
                        <i class="icon-base ti tabler-receipt-off fs-4 d-block mb-1 opacity-50"></i>
                        {{ __('No financial transactions recorded yet.') }}
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
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
  const itemRows = document.getElementById('itemRows');
  const itemRowTemplate = document.getElementById('itemRowTemplate');

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
      if (typeof updateVaultSummaryPills === 'function') {
        updateVaultSummaryPills(baseSp);
      }
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
      const baseBuy = parseFloat(row.getAttribute('data-base-buy')) || 0;
      const baseSell = parseFloat(row.getAttribute('data-base-sell')) || 0;

      const cellUnit = row.querySelector('.cell-unit-price .cell-num');
      if (cellUnit) cellUnit.textContent = (baseUnit * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

      const cellTotal = row.querySelector('.cell-total-price .cell-num');
      if (cellTotal) cellTotal.textContent = (baseTotal * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

      const cellBuy = row.querySelector('.cell-buy-price .cell-num');
      if (cellBuy) cellBuy.textContent = (baseBuy * rate).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });

      const cellSell = row.querySelector('.cell-sell-price .cell-num');
      if (cellSell) cellSell.textContent = (baseSell * rate).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
    });

    // Update table footer values
    const footTotalVal = document.getElementById('footTotalPriceVal');
    if (footTotalVal && elTotal) {
      const baseTotal = parseFloat(elTotal.getAttribute('data-base-val')) || 0;
      footTotalVal.textContent = (baseTotal * rate).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    // Update modal calculation preview
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
    updateSelectedItems();
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

  // Preview prices for each row; the server remains the source of truth.
  function updateItemRowPreview(row) {
    const quantity = Math.max(1, parseInt(row.querySelector('[data-field="quantity"]').value, 10) || 1);
    const unitPrice = Math.max(0, parseFloat(row.querySelector('[data-field="unit_price"]').value) || 0);
    const type = row.querySelector('[data-field="type"]').value;
    const quality = row.querySelector('[data-field="quality"]').value;
    const total = type === 'Wand' ? (parseWandCharges(quality) / 50) * unitPrice * quantity : unitPrice * quantity;
    row.querySelector('[data-preview="total"]').textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    row.querySelector('[data-preview="buy"]').textContent = (quantity ? total * 0.55 / quantity : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    row.querySelector('[data-preview="sell"]').textContent = (quantity ? total * 0.5 / quantity : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function addItemRow() {
    if (!itemRows || !itemRowTemplate) return;
    const rowIndex = itemRows.children.length;
    const row = itemRowTemplate.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('[data-field]').forEach(input => {
      input.name = `items[${rowIndex}][${input.getAttribute('data-field')}]`;
      input.addEventListener('input', () => updateItemRowPreview(row));
      input.addEventListener('change', () => updateItemRowPreview(row));
    });
    row.querySelector('.remove-item-row').addEventListener('click', () => {
      row.remove();
      Array.from(itemRows.children).forEach((itemRow, index) => {
        itemRow.querySelectorAll('[data-field]').forEach(input => {
          input.name = `items[${index}][${input.getAttribute('data-field')}]`;
        });
      });
    });
    itemRows.appendChild(row);
    updateItemRowPreview(row);
  }

  const btnAddItemRow = document.getElementById('btnAddItemRow');
  if (btnAddItemRow) btnAddItemRow.addEventListener('click', addItemRow);
  const addItemsModal = document.getElementById('modalAddItems');
  if (addItemsModal) {
    addItemsModal.addEventListener('show.bs.modal', () => {
      if (itemRows && itemRows.children.length === 0) addItemRow();
    });
  }

  // Keep selected-item actions and the select-all control in sync with filtered rows.
  const selectAllItems = document.getElementById('selectAllItems');
  const selectedDeleteButton = document.getElementById('btnDeleteSelected');
  const selectedSellButton = document.getElementById('btnSellSelected');
  const selectedItemCount = document.getElementById('selectedItemCount');
  const itemCheckboxes = Array.from(document.querySelectorAll('.item-select-checkbox'));
  function updateSelectedItems() {
    const checkedCount = itemCheckboxes.filter(checkbox => checkbox.checked).length;
    if (selectedItemCount) selectedItemCount.textContent = checkedCount;
    if (selectedDeleteButton) selectedDeleteButton.disabled = checkedCount === 0;
    if (selectedSellButton) selectedSellButton.disabled = checkedCount === 0;
    if (selectAllItems) {
      const visibleCheckboxes = itemCheckboxes.filter(checkbox => checkbox.closest('tr').style.display !== 'none');
      selectAllItems.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(checkbox => checkbox.checked);
      selectAllItems.indeterminate = visibleCheckboxes.some(checkbox => checkbox.checked) && !selectAllItems.checked;
    }
  }
  itemCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateSelectedItems));
  if (selectAllItems) {
    selectAllItems.addEventListener('change', () => {
      itemCheckboxes.forEach(checkbox => {
        if (checkbox.closest('tr').style.display !== 'none') checkbox.checked = selectAllItems.checked;
      });
      updateSelectedItems();
    });
  }
  function submitSelectedItems(action, selected, methodOverride = null) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = csrfToken;
    form.appendChild(tokenInput);
    if (methodOverride) {
      const methodInput = document.createElement('input');
      methodInput.type = 'hidden';
      methodInput.name = '_method';
      methodInput.value = methodOverride;
      form.appendChild(methodInput);
    }
    selected.forEach(checkbox => {
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'ids[]';
      idInput.value = checkbox.value;
      form.appendChild(idInput);
    });
    document.body.appendChild(form);
    form.submit();
  }
  if (selectedSellButton) {
    selectedSellButton.addEventListener('click', () => {
      const selected = itemCheckboxes.filter(checkbox => checkbox.checked);
      if (!selected.length || !confirm(@json(__('Sell selected items for 50% of their value and add proceeds to the Guild Vault?')))) return;
      submitSelectedItems(`{{ route('valiant-shade.pages-inventory.sell') }}`, selected);
    });
  }
  if (selectedDeleteButton) {
    selectedDeleteButton.addEventListener('click', () => {
      const selected = itemCheckboxes.filter(checkbox => checkbox.checked);
      if (!selected.length || !confirm(`Delete ${selected.length} selected inventory items?`)) return;
      submitSelectedItems(`{{ route('valiant-shade.pages-inventory.bulk-destroy') }}`, selected, 'DELETE');
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

  // Live Price Preview in Edit Item Modal
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
  const editPrevSell = document.getElementById('editPreviewSell');
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
    if (editPrevSell) editPrevSell.textContent = (totalGP * 0.5 / qty * rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + sym;
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

  // 6. Advanced Guild Vault & Cash Management System
  const modalVaultEl = document.getElementById('modalManageVault');
  const vaultTriggers = document.querySelectorAll('.btn-vault-trigger');

  function getCurrentVaultSp() {
    const el = document.getElementById('statVaultCash') || document.getElementById('modalVaultCurrentSp');
    return parseFloat(el?.getAttribute('data-base-sp')) || 30000;
  }

  function convertToSp(amount, currency) {
    const amt = parseFloat(amount) || 0;
    switch ((currency || 'SP').toUpperCase()) {
      case 'PP': return amt * 100.0;
      case 'GP': return amt * 10.0;
      case 'SP': return amt * 1.0;
      case 'CP': return amt * 0.1;
      default: return amt;
    }
  }

  function updateVaultSummaryPills(vaultSp) {
    const ppEl = document.getElementById('modalVaultPpVal');
    const gpEl = document.getElementById('modalVaultGpVal');
    const spEl = document.getElementById('modalVaultSpVal');
    const cpEl = document.getElementById('modalVaultCpVal');
    if (ppEl) ppEl.textContent = (vaultSp / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (gpEl) gpEl.textContent = (vaultSp / 10).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (spEl) spEl.textContent = vaultSp.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (cpEl) cpEl.textContent = (vaultSp * 10).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  if (modalVaultEl) {
    const vaultModal = bootstrap.Modal.getOrCreateInstance(modalVaultEl);

    vaultTriggers.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const targetTab = this.getAttribute('data-vault-tab') || 'deposit';
        const tabBtn = document.getElementById('tab-btn-' + targetTab);
        if (tabBtn) {
          bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
        vaultModal.show();
        setTimeout(() => {
          if (targetTab === 'deposit') document.getElementById('depositAmount')?.focus();
          else if (targetTab === 'withdraw') document.getElementById('withdrawAmount')?.focus();
          else if (targetTab === 'transfer') document.getElementById('transferAmount')?.focus();
          else if (targetTab === 'set') document.getElementById('setAmount')?.focus();
        }, 200);
      });
    });

    // A. Deposit calculation
    const depositAmtInput = document.getElementById('depositAmount');
    const depositCurrSelect = document.getElementById('depositCurrency');
    const prevDepositSp = document.getElementById('previewDepositSp');
    const prevDepositNewVault = document.getElementById('previewDepositNewVault');

    function updateDepositPreview() {
      if (!depositAmtInput) return;
      const currentVault = getCurrentVaultSp();
      const amt = parseFloat(depositAmtInput.value) || 0;
      const curr = depositCurrSelect?.value || 'SP';
      const spValue = convertToSp(amt, curr);
      const newVault = currentVault + spValue;

      if (prevDepositSp) {
        prevDepositSp.textContent = (spValue > 0 ? '+' : '') + spValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
      }
      if (prevDepositNewVault) {
        prevDepositNewVault.textContent = newVault.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
      }
    }

    depositAmtInput?.addEventListener('input', updateDepositPreview);
    depositCurrSelect?.addEventListener('change', updateDepositPreview);

    document.querySelectorAll('.preset-deposit-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const amt = this.getAttribute('data-amount');
        const curr = this.getAttribute('data-curr');
        if (depositAmtInput) depositAmtInput.value = amt;
        if (depositCurrSelect) depositCurrSelect.value = curr;
        updateDepositPreview();
      });
    });

    // B. Withdraw calculation
    const withdrawAmtInput = document.getElementById('withdrawAmount');
    const withdrawCurrSelect = document.getElementById('withdrawCurrency');
    const prevWithdrawSp = document.getElementById('previewWithdrawSp');
    const prevWithdrawRemaining = document.getElementById('previewWithdrawRemainingVault');
    const alertWithdrawInsufficient = document.getElementById('alertWithdrawInsufficient');
    const btnSubmitWithdraw = document.getElementById('btnSubmitWithdraw');

    function updateWithdrawPreview() {
      if (!withdrawAmtInput) return;
      const currentVault = getCurrentVaultSp();
      const amt = parseFloat(withdrawAmtInput.value) || 0;
      const curr = withdrawCurrSelect?.value || 'SP';
      const spValue = convertToSp(amt, curr);
      const remainingVault = currentVault - spValue;

      if (prevWithdrawSp) {
        prevWithdrawSp.textContent = (spValue > 0 ? '-' : '') + spValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
      }
      if (prevWithdrawRemaining) {
        prevWithdrawRemaining.textContent = remainingVault.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
      }

      const isInsufficient = spValue > currentVault;
      if (alertWithdrawInsufficient) {
        alertWithdrawInsufficient.classList.toggle('d-none', !isInsufficient);
      }
      if (btnSubmitWithdraw) {
        btnSubmitWithdraw.disabled = isInsufficient && spValue > 0;
      }
    }

    withdrawAmtInput?.addEventListener('input', updateWithdrawPreview);
    withdrawCurrSelect?.addEventListener('change', updateWithdrawPreview);

    document.querySelectorAll('.preset-withdraw-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const amt = this.getAttribute('data-amount');
        const curr = this.getAttribute('data-curr');
        if (withdrawAmtInput) withdrawAmtInput.value = amt;
        if (withdrawCurrSelect) withdrawCurrSelect.value = curr;
        updateWithdrawPreview();
      });
    });

    document.querySelectorAll('.preset-withdraw-percent-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const percent = parseFloat(this.getAttribute('data-percent')) || 25;
        const currentVault = getCurrentVaultSp();
        const amtSp = (currentVault * (percent / 100.0));
        if (withdrawAmtInput) withdrawAmtInput.value = amtSp.toFixed(2);
        if (withdrawCurrSelect) withdrawCurrSelect.value = 'SP';
        updateWithdrawPreview();
      });
    });

    // C. Character Transfer calculation & direction handling
    const transferOpInput = document.getElementById('transferOperationInput');
    const dirRadios = document.querySelectorAll('input[name="transferDirectionRadio"]');
    const containerSelectChar = document.getElementById('containerSelectCharacter');
    const cardCharPreview = document.getElementById('cardCharacterTransferPreview');
    const cardDistributeInfo = document.getElementById('cardDistributeInfo');
    const charSelect = document.getElementById('transferCharacterSelect');
    const transferAmtInput = document.getElementById('transferAmount');
    const transferCurrSelect = document.getElementById('transferCurrency');
    const btnTransferLabel = document.getElementById('btnTransferLabel');
    const btnSubmitTransfer = document.getElementById('btnSubmitTransfer');
    const alertTransferInsufficient = document.getElementById('alertTransferInsufficient');
    const transferInsufficientMsg = document.getElementById('transferInsufficientMsg');

    const prevCharName = document.getElementById('prevCharName');
    const prevCharLevel = document.getElementById('prevCharLevel');
    const prevCharPlayer = document.getElementById('prevCharPlayer');
    const prevCharCurrentBalance = document.getElementById('prevCharCurrentBalance');
    const prevCharProjected = document.getElementById('prevCharProjectedBalance');
    const prevVaultProjected = document.getElementById('prevVaultProjectedBalance');
    const distributeSharePreview = document.getElementById('distributeSharePreview');

    function updateCharacterTransferPreview() {
      const currentVault = getCurrentVaultSp();
      const direction = transferOpInput?.value || 'transfer_to_character';
      const amt = parseFloat(transferAmtInput?.value) || 0;
      const curr = transferCurrSelect?.value || 'SP';
      const spValue = convertToSp(amt, curr);

      let isInsufficient = false;
      let errorMsg = '';

      if (direction === 'distribute_characters') {
        if (containerSelectChar) containerSelectChar.classList.add('d-none');
        if (cardCharPreview) cardCharPreview.classList.add('d-none');
        if (cardDistributeInfo) cardDistributeInfo.classList.remove('d-none');
        if (btnTransferLabel) btnTransferLabel.textContent = @json(__('Distribute to All Players'));

        const charCount = parseInt(document.getElementById('distributeCharCount')?.textContent, 10) || 1;
        const perCharShare = charCount > 0 ? (spValue / charCount) : 0;
        if (distributeSharePreview) {
          distributeSharePreview.textContent = perCharShare.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
        }

        if (spValue > currentVault) {
          isInsufficient = true;
          errorMsg = @json(__('Insufficient vault funds for distribution! Current vault: ')) + currentVault.toLocaleString('en-US') + ' SP';
        }
      } else {
        if (containerSelectChar) containerSelectChar.classList.remove('d-none');
        if (cardCharPreview) cardCharPreview.classList.remove('d-none');
        if (cardDistributeInfo) cardDistributeInfo.classList.add('d-none');

        const selectedOpt = charSelect?.selectedOptions[0];
        const charName = selectedOpt?.getAttribute('data-name') || 'Character';
        const charLevel = selectedOpt?.getAttribute('data-level') || '1';
        const charPlayer = selectedOpt?.getAttribute('data-player') || 'Unassigned';
        const charBalance = parseFloat(selectedOpt?.getAttribute('data-balance-sp')) || 0;

        if (prevCharName) prevCharName.textContent = charName;
        if (prevCharLevel) prevCharLevel.textContent = 'Lv. ' + charLevel;
        if (prevCharPlayer) prevCharPlayer.textContent = charPlayer;
        if (prevCharCurrentBalance) prevCharCurrentBalance.textContent = charBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';

        let projectedChar = charBalance;
        let projectedVault = currentVault;

        if (direction === 'transfer_to_character') {
          if (btnTransferLabel) btnTransferLabel.textContent = @json(__('Transfer to Character'));
          projectedChar = charBalance + spValue;
          projectedVault = currentVault - spValue;

          if (spValue > currentVault) {
            isInsufficient = true;
            errorMsg = @json(__('Insufficient vault funds! Available in vault: ')) + currentVault.toLocaleString('en-US') + ' SP';
          }
        } else {
          if (btnTransferLabel) btnTransferLabel.textContent = @json(__('Receive into Vault'));
          projectedChar = charBalance - spValue;
          projectedVault = currentVault + spValue;

          if (spValue > charBalance) {
            isInsufficient = true;
            errorMsg = charName + ' ' + @json(__('has insufficient funds! Character balance: ')) + charBalance.toLocaleString('en-US') + ' SP';
          }
        }

        if (prevCharProjected) {
          prevCharProjected.textContent = projectedChar.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
        }
        if (prevVaultProjected) {
          prevVaultProjected.textContent = projectedVault.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' SP';
        }
      }

      if (alertTransferInsufficient && transferInsufficientMsg) {
        transferInsufficientMsg.textContent = errorMsg;
        alertTransferInsufficient.classList.toggle('d-none', !isInsufficient);
      }
      if (btnSubmitTransfer) {
        btnSubmitTransfer.disabled = isInsufficient && spValue > 0;
      }
    }

    dirRadios.forEach(radio => {
      radio.addEventListener('change', function () {
        if (transferOpInput) transferOpInput.value = this.value;
        updateCharacterTransferPreview();
      });
    });

    charSelect?.addEventListener('change', updateCharacterTransferPreview);
    transferAmtInput?.addEventListener('input', updateCharacterTransferPreview);
    transferCurrSelect?.addEventListener('change', updateCharacterTransferPreview);

    document.querySelectorAll('.preset-transfer-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const amt = this.getAttribute('data-amount');
        const curr = this.getAttribute('data-curr');
        if (transferAmtInput) transferAmtInput.value = amt;
        if (transferCurrSelect) transferCurrSelect.value = curr;
        updateCharacterTransferPreview();
      });
    });

    updateDepositPreview();
    updateWithdrawPreview();
    updateCharacterTransferPreview();
  }

  // Initialize with Silver (SP) on page load
  switchCurrency('SP');
});
</script>
@endsection
